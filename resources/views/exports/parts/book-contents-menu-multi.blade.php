@if(count($children) > 0)
    <ul class="contents">
        <li>
            <a href="./{{ $entityLinks['book-' . $book->id] ?? 'index.html' }}"
               class="{{ $currentEntity->isA('book') && $currentEntity->id === $book->id ? 'active' : '' }}">
                {{ $book->name }}
            </a>
        </li>
        @foreach($children as $bookChild)
            <li>
                <a href="./{{ $entityLinks[$bookChild->getType() . '-' . $bookChild->id] }}"
                   class="{{ $currentEntity->getType() === $bookChild->getType() && $currentEntity->id === $bookChild->id ? 'active' : '' }}">
                    {{ $bookChild->name }}
                </a>
                @if($bookChild->isA('chapter') && count($bookChild->visible_pages) > 0)
                    @include('exports.parts.chapter-contents-menu', ['pages' => $bookChild->visible_pages, 'entityLinks' => $entityLinks])
                @endif
            </li>
        @endforeach
    </ul>
@endif
