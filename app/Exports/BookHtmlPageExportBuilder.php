<?php

namespace BookStack\Exports;

use BookStack\Entities\Models\Book;
use BookStack\Entities\Models\Chapter;
use BookStack\Entities\Models\Entity;
use BookStack\Entities\Models\Page;
use BookStack\Entities\Tools\BookContents;
use BookStack\Exceptions\ZipExportException;
use BookStack\Uploads\ImageService;
use BookStack\Uploads\ImageStorage;
use BookStack\Util\CspService;
use BookStack\Util\HtmlDocument;
use Illuminate\Support\Collection;
use ZipArchive;

class BookHtmlPageExportBuilder
{
    protected array $assetMap = [];
    protected array $assetContents = [];

    public function __construct(
        protected ExportFormatter $exportFormatter,
        protected CspService $cspService,
        protected ImageStorage $imageStorage,
        protected ImageService $imageService,
    ) {
    }

    /**
     * Build a multi-file HTML ZIP export for the given book.
     *
     * @throws ZipExportException
     */
    public function build(Book $book): string
    {
        $bookChildren = (new BookContents($book))->getTree(false, true);
        $entityLinks = $this->getEntityLinks($book, $bookChildren);

        $zipFile = tempnam(sys_get_temp_dir(), 'bshtmlzip-');
        $zip = new ZipArchive();
        $opened = $zip->open($zipFile, ZipArchive::OVERWRITE);
        if ($opened !== true) {
            throw new ZipExportException('Failed to create zip file for HTML export.');
        }

        $zip->addFromString('index.html', $this->renderBookHtml($book, $bookChildren, $entityLinks));

        foreach ($bookChildren as $bookChild) {
            if ($bookChild instanceof Chapter) {
                $zip->addFromString($entityLinks[$this->getEntityRef($bookChild)], $this->renderChapterHtml($book, $bookChild, $bookChildren, $entityLinks));

                foreach ($bookChild->visible_pages as $page) {
                    $zip->addFromString($entityLinks[$this->getEntityRef($page)], $this->renderPageHtml($book, $page, $bookChild, $bookChildren, $entityLinks));
                }
                continue;
            }

            if ($bookChild instanceof Page) {
                $zip->addFromString($entityLinks[$this->getEntityRef($bookChild)], $this->renderPageHtml($book, $bookChild, null, $bookChildren, $entityLinks));
            }
        }

        $zip->addFromString('manifest.json', json_encode([
            'book' => [
                'id' => $book->id,
                'name' => $book->name,
                'slug' => $book->slug,
            ],
            'files' => $entityLinks,
        ], JSON_PRETTY_PRINT));

        foreach ($this->assetContents as $path => $content) {
            $zip->addFromString($path, $content);
        }

        $zip->close();

        return $zipFile;
    }

    protected function renderBookHtml(Book $book, Collection $bookChildren, array $entityLinks): string
    {
        $html = view('exports.book-multi-page', [
            'book' => $book,
            'bookChildren' => $bookChildren,
            'entityLinks' => $entityLinks,
            'contentType' => 'book',
            'currentEntity' => $book,
            'currentChapter' => null,
            'format' => 'html',
            'cspContent' => $this->cspService->getCspMetaTagValue(),
            'locale' => user()->getLocale(),
        ])->render();

        return $this->prepareHtml($html);
    }

    protected function renderChapterHtml(Book $book, Chapter $chapter, Collection $bookChildren, array $entityLinks): string
    {
        $html = view('exports.book-multi-page', [
            'book' => $book,
            'bookChildren' => $bookChildren,
            'entityLinks' => $entityLinks,
            'contentType' => 'chapter',
            'currentEntity' => $chapter,
            'currentChapter' => $chapter,
            'format' => 'html',
            'cspContent' => $this->cspService->getCspMetaTagValue(),
            'locale' => user()->getLocale(),
        ])->render();

        return $this->prepareHtml($html);
    }

    protected function renderPageHtml(Book $book, Page $page, ?Chapter $chapter, Collection $bookChildren, array $entityLinks): string
    {
        $html = view('exports.book-multi-page', [
            'book' => $book,
            'bookChildren' => $bookChildren,
            'entityLinks' => $entityLinks,
            'contentType' => 'page',
            'currentEntity' => $page,
            'currentChapter' => $chapter,
            'format' => 'html',
            'cspContent' => $this->cspService->getCspMetaTagValue(),
            'locale' => user()->getLocale(),
        ])->render();

        return $this->prepareHtml($html);
    }

    protected function getEntityLinks(Book $book, Collection $bookChildren): array
    {
        $links = [
            'book-' . $book->id => 'index.html',
        ];

        foreach ($bookChildren as $bookChild) {
            $links[$this->getEntityRef($bookChild)] = $this->getFileName($bookChild);

            if ($bookChild instanceof Chapter) {
                foreach ($bookChild->visible_pages as $page) {
                    $links[$this->getEntityRef($page)] = $this->getFileName($page);
                }
            }
        }

        return $links;
    }

    protected function getEntityRef(Entity $entity): string
    {
        return $entity->getType() . '-' . $entity->id;
    }

    protected function getFileName(Entity $entity): string
    {
        $slug = preg_replace('/[^A-Za-z0-9_-]+/', '-', $entity->slug);
        $slug = trim($slug ?: 'export', '-');

        return "{$entity->getType()}-{$entity->id}-{$slug}.html";
    }

    protected function prepareHtml(string $html): string
    {
        $doc = new HtmlDocument();
        $doc->loadCompleteHtml($html);

        foreach ($doc->queryXPath('//img[@src]') as $image) {
            $src = $image->attributes?->getNamedItem('src')?->nodeValue;
            if (!$src || str_starts_with($src, 'data:')) {
                continue;
            }

            $assetPath = $this->getAssetPathForImage($src);
            if ($assetPath === null) {
                continue;
            }

            $image->setAttribute('src', $assetPath);
        }

        return $doc->getHtml();
    }

    protected function getAssetPathForImage(string $src): ?string
    {
        if (isset($this->assetMap[$src])) {
            return $this->assetMap[$src];
        }

        $storagePath = $this->imageStorage->urlToPath($src);
        if ($storagePath === null || !$this->imageService->pathAccessible($storagePath)) {
            return null;
        }

        $extension = strtolower(pathinfo(parse_url($src, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        $extension = $extension ?: 'img';
        $fileName = preg_replace('/[^A-Za-z0-9_-]+/', '-', pathinfo($storagePath, PATHINFO_FILENAME));
        $fileName = trim($fileName ?: 'image', '-');
        $hash = substr(sha1($storagePath), 0, 12);
        $assetPath = "assets/{$fileName}-{$hash}.{$extension}";

        if (!isset($this->assetContents[$assetPath])) {
            $content = $this->imageStorage->getDisk()->get($storagePath);
            if ($content === null) {
                return null;
            }

            $this->assetContents[$assetPath] = $content;
        }

        $this->assetMap[$src] = $assetPath;

        return $assetPath;
    }
}
