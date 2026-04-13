@if(count($children) > 0)
    <ul class="contents">
        <li><a href="{{ $entityLinks['book-' . $book->id] ?? 'index.html' }}">{{ $book->name }}</a></li>
        @foreach($children as $bookChild)
            <li><a href="{{ $entityLinks[$bookChild->getType() . '-' . $bookChild->id] }}">{{ $bookChild->name }}</a></li>
            @if($bookChild->isA('chapter') && count($bookChild->visible_pages) > 0)
                @include('exports.parts.chapter-contents-menu', ['pages' => $bookChild->visible_pages, 'entityLinks' => $entityLinks])
            @endif
        @endforeach
    </ul>
@endif
