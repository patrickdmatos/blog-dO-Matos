@props(['posts', 'headingLevel' => 2])
@if (count($posts))
    <ul class="post-list" role="list">
        @foreach ($posts as $post)
            <li>
                <article class="post-card" aria-labelledby="post-{{ $post->slug }}">
                    <h{{ $headingLevel }} id="post-{{ $post->slug }}" class="post-card__title">
                        <a href="{{ route('posts.show', $post->slug, false) }}">{{ $post->title }}</a>
                    </h{{ $headingLevel }}>
                    <x-post-meta :post="$post" />
                    <p>{{ $post->description }}</p>
                    <x-tag-list :tags="$post->tags" :label="'Tags de '.$post->title" />
                </article>
            </li>
        @endforeach
    </ul>
@else
    <p>Nenhum post publicado ainda.</p>
@endif
