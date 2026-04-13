@extends('layouts.export')

@section('title', $currentEntity->name)

@section('content')
    <div class="export-book-layout">
        <aside class="export-book-menu">
            @include('exports.parts.book-contents-menu-multi', ['children' => $bookChildren, 'entityLinks' => $entityLinks])
        </aside>

        <div class="export-book-body">
            @if($contentType === 'book')
                <h1 style="font-size: 4.8em">{{ $book->name }}</h1>
                <div>{!! $book->descriptionInfo()->getHtml() !!}</div>
            @elseif($contentType === 'chapter')
                <h1 style="font-size: 4.8em">{{ $currentEntity->name }}</h1>
                <div>{!! $currentEntity->descriptionInfo()->getHtml() !!}</div>

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
