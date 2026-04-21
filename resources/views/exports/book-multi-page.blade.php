@extends('layouts.export')

@section('title', $currentEntity->name)

@section('topbar')
    <header class="export-topbar" data-export-topbar>
        <div class="export-topbar-inner">
            <div class="export-topbar-brand">
                <a href="./{{ $entityLinks['book-' . $book->id] ?? 'index.html' }}" class="export-topbar-book-link">
                    {{ $book->name }}
                </a>
            </div>

            <div class="export-topbar-search">
                <label class="sr-only" for="export-nav-search">Search exported navigation</label>
                <input
                    id="export-nav-search"
                    type="search"
                    class="export-topbar-search-input"
                    placeholder="Search chapters and pages"
                    autocomplete="off"
                    spellcheck="false"
                    data-export-nav-search
                >
                <button type="button" class="export-topbar-search-clear" data-export-nav-clear hidden>Clear</button>
                <div class="export-topbar-search-meta" data-export-nav-status hidden></div>
            </div>
        </div>
    </header>
@endsection

@section('content')
    <div class="export-book-layout export-book-layout-{{ $contentType }}">
        <aside class="export-book-menu">
            <div class="export-book-menu-scroll">
                @include('exports.parts.book-contents-menu-multi', ['children' => $bookChildren, 'entityLinks' => $entityLinks])
            </div>
        </aside>

        <div class="export-book-body export-book-body-{{ $contentType }}">
            @if($contentType === 'book')
                <section class="export-cover-page">
                    <div class="export-cover-kicker">Application User Manual</div>
                    <h1 class="export-hero-title">{{ $book->name }}</h1>

                    @if($book->prepared_by || $book->document_version)
                        <div class="export-cover-meta export-cover-meta-stacked">
                            @if($book->prepared_by)
                                <div class="export-cover-meta-row">
                                    <span class="export-cover-meta-label">Prepared By</span>
                                    <span class="export-cover-meta-value">{{ $book->prepared_by }}</span>
                                </div>
                            @endif
                            @if($book->document_version)
                                <div class="export-cover-meta-row">
                                    <span class="export-cover-meta-label">Document Version</span>
                                    <span class="export-cover-meta-value">{{ $book->document_version }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="export-hero-copy">{!! $book->descriptionInfo()->getHtml() !!}</div>
                </section>
            @elseif($contentType === 'chapter')
                <h1 class="export-hero-title">{{ $currentEntity->name }}</h1>
                <div class="export-hero-copy">{!! $currentEntity->descriptionInfo()->getHtml() !!}</div>

                @include('exports.parts.chapter-contents-menu', ['pages' => $currentEntity->visible_pages, 'entityLinks' => $entityLinks])
            @elseif($contentType === 'page')
                @if($currentChapter)
                    <div class="chapter-hint">{{ $currentChapter->name }}</div>
                @endif

                @include('pages.parts.page-display', ['page' => $currentEntity])
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (() => {
            const topbar = document.querySelector('[data-export-topbar]');
            const searchInput = document.querySelector('[data-export-nav-search]');
            const clearButton = document.querySelector('[data-export-nav-clear]');
            const status = document.querySelector('[data-export-nav-status]');
            const exportRoot = document.body;

            if (!topbar || !searchInput || !clearButton || !status || !exportRoot) {
                return;
            }

            const menuShell = document.querySelector('.export-book-menu');
            let menu = document.querySelector('.export-book-menu-scroll');
            if (!menuShell || !menu) {
                return;
            }

            const rebuildMenu = () => {
                const freshMenu = menu.cloneNode(true);
                menu.replaceWith(freshMenu);
                menu = freshMenu;
            };

            rebuildMenu();

            const getLists = () => Array.from(menu.querySelectorAll('ul.contents'));
            const getLinks = () => Array.from(menu.querySelectorAll('a'));
            const syncTopbarHeight = () => {
                const height = Math.ceil(topbar.getBoundingClientRect().height);
                exportRoot.style.setProperty('--export-topbar-height', `${height}px`);
            };
            const keepActiveItemVisible = () => {
                const activeLink = menu.querySelector('a.active');
                if (!activeLink) {
                    return;
                }

                requestAnimationFrame(() => {
                    activeLink.scrollIntoView({
                        block: 'center',
                        inline: 'nearest',
                    });

                    // Keep the active item slightly below the top edge for easier scanning.
                    menu.scrollTop = Math.max(0, menu.scrollTop - 24);
                });
            };

            const updateSearch = () => {
                const links = getLinks();
                const lists = getLists();
                const query = searchInput.value.trim().toLowerCase();
                let matchCount = 0;

                links.forEach(link => {
                    const text = (link.textContent || '').trim().toLowerCase();
                    const matched = query === '' || text.includes(query);
                    const item = link.closest('li');

                    link.hidden = !matched;
                    if (item) {
                        item.hidden = !matched;
                    }

                    if (matched) {
                        matchCount++;
                    }
                });

                lists.forEach(list => {
                    const hasVisibleLink = Array.from(list.querySelectorAll('a')).some(link => !link.hidden);
                    list.hidden = !hasVisibleLink;
                });

                clearButton.hidden = query === '';

                if (query === '') {
                    status.hidden = true;
                    status.textContent = '';
                    return;
                }

                status.hidden = false;
                status.textContent = matchCount > 0
                    ? `${matchCount} result${matchCount === 1 ? '' : 's'} found`
                    : 'No matching chapters or pages';
            };

            syncTopbarHeight();
            keepActiveItemVisible();
            searchInput.addEventListener('input', updateSearch);
            clearButton.addEventListener('click', () => {
                searchInput.value = '';
                updateSearch();
                searchInput.focus();
            });

            searchInput.addEventListener('keydown', event => {
                if (event.key === 'Enter') {
                    const links = getLinks();
                    const firstVisibleLink = links.find(link => !link.hidden);
                    if (firstVisibleLink) {
                        window.location.href = firstVisibleLink.href;
                    }
                }

                if (event.key === 'Escape') {
                    searchInput.value = '';
                    updateSearch();
                }
            });

            document.addEventListener('keydown', event => {
                const isTypingTarget = event.target instanceof HTMLElement
                    && ['INPUT', 'TEXTAREA'].includes(event.target.tagName);

                if (!isTypingTarget && event.key === '/') {
                    event.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                }
            });

            window.addEventListener('resize', syncTopbarHeight);
            window.addEventListener('load', keepActiveItemVisible);
            window.addEventListener('pageshow', keepActiveItemVisible);
        })();
    </script>
@endsection
