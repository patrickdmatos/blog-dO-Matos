<x-layout :title="'#'.$tag['name']" :description="'Posts sobre '.$tag['name'].'.'">
    <header class="page-header">
        <p class="eyebrow"><a href="{{ route('tags.index', absolute: false) }}">Tags</a></p>
        <h1>#{{ $tag['name'] }}</h1>
        <p class="lead">{{ $tag['count'] }} {{ $tag['count'] === 1 ? 'post' : 'posts' }}</p>
    </header>

    <x-post-list :posts="$posts" />
</x-layout>
