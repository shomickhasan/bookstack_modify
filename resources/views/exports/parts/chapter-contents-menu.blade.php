@if (count($pages) > 0)
        <ul class="contents">
            @foreach($pages as $page)
                <li>
                    <a href="./{{ $entityLinks['page-' . $page->id] ?? '#page-' . $page->id }}"
                       class="{{ isset($currentEntity) && $currentEntity->isA('page') && $currentEntity->id === $page->id ? 'active' : '' }}">
                        {{ $page->name }}
                    </a>
                </li>
            @endforeach
        </ul>
@endif
