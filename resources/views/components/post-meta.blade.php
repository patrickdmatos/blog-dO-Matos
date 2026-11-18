@props(['post'])
<p class="post-meta">
    @if ($post->date)
        <time datetime="{{ $post->date->toDateString() }}">{{ $post->date->translatedFormat('j \d\e F \d\e Y') }}</time>
        <span aria-hidden="true">·</span>
    @endif
    <span>{{ $post->readingTime }} min de leitura</span>
    @if ($post->draft)
        <span aria-hidden="true">·</span> <strong class="badge">Rascunho</strong>
    @endif
</p>
