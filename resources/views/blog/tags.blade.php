<x-layout title="Tags" description="Todos os assuntos do blog.">
    <header class="page-header">
        <h1>Tags</h1>
    </header>

    @if ($tags->isEmpty())
        <p>Nenhuma tag ainda.</p>
    @else
        <ul class="tag-cloud">
            @foreach ($tags as $tag)
                <li>
                    <a href="{{ route('tags.show', $tag['slug'], false) }}">
                        #{{ $tag['name'] }}
                        <span class="count">{{ $tag['count'] }} {{ $tag['count'] === 1 ? 'post' : 'posts' }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-layout>
