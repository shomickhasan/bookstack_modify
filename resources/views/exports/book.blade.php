@extends('layouts.export')

@section('title', $book->name)

@section('content')

    <div class="export-book-layout">
        <aside class="export-book-menu">
            @include('exports.parts.book-contents-menu', ['children' => $bookChildren])
        </aside>

        <div class="export-book-body">
            <h1 style="font-size: 4.8em">{{$book->name}}</h1>
            <div>{!! $book->descriptionInfo()->getHtml() !!}</div>

            @foreach($bookChildren as $bookChild)
                @if($bookChild->isA('chapter'))
                    @include('exports.parts.chapter-item', ['chapter' => $bookChild])
                @else
                    @include('exports.parts.page-item', ['page' => $bookChild, 'chapter' => null])
                @endif
            @endforeach
        </div>
    </div>

@endsection
