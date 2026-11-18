<x-layout :title="$post->title" :description="$post->description" type="article" :image="$post->cover" :published="$post->date">
    <article class="post" aria-labelledby="titulo-post">
        <header class="page-header">
            <h1 id="titulo-post">{{ $post->title }}</h1>
            <x-post-meta :post="$post" />
            @if ($post->updated)
                <p class="post-meta">Atualizado em <time datetime="{{ $post->updated->toDateString() }}">{{ $post->updated->translatedFormat('j \d\e F \d\e Y') }}</time></p>
            @endif
            <x-tag-list :tags="$post->tags" />
        </header>

        @if ($post->cover)
            <figure class="cover">
                <img src="{{ $post->cover }}" alt="{{ $post->coverAlt ?? '' }}" decoding="async">
            </figure>
        @endif

        @if (count($post->toc) >= 3)
            <nav class="toc" aria-labelledby="toc-titulo">
                <details open>
                    <summary id="toc-titulo">Nesta página</summary>
                    <ol>
                        @foreach ($post->toc as $item)
                            <li class="toc__item toc__item--h{{ $item['level'] }}"><a href="#{{ $item['id'] }}">{{ $item['text'] }}</a></li>
                        @endforeach
                    </ol>
                </details>
            </nav>
        @endif

        <div class="prose">
            {!! $post->html !!}
        </div>
    </article>

    @if ($previous || $next)
        <nav class="post-nav" aria-label="Outros posts">
            @if ($previous)
                <a href="{{ route('posts.show', $previous->slug, false) }}" rel="prev">
                    <small>Anterior</small>
                    <span>{{ $previous->title }}</span>
                </a>
            @endif
            @if ($next)
                <a href="{{ route('posts.show', $next->slug, false) }}" rel="next" class="post-nav__next">
                    <small>Próximo</small>
                    <span>{{ $next->title }}</span>
                </a>
            @endif
        </nav>
    @endif
</x-layout>
