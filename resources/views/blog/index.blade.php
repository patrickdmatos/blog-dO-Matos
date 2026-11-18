@php($current = $posts->currentPage())
<x-layout :title="$current > 1 ? 'Página '.$current : null">
    <header class="page-header">
        <h1>{{ $current > 1 ? 'Posts — página '.$current : 'Últimos posts' }}</h1>
        @if ($current === 1)
            <p class="lead">{{ $blog['description'] }}</p>
        @endif
    </header>

    <x-post-list :posts="$posts" />

    @if ($previousUrl || $nextUrl)
        <nav class="pagination" aria-label="Paginação">
            @if ($nextUrl)
                <a href="{{ $nextUrl }}" rel="next">Posts mais antigos <span aria-hidden="true">→</span></a>
            @endif
            <span>Página {{ $current }} de {{ $posts->lastPage() }}</span>
            @if ($previousUrl)
                <a href="{{ $previousUrl }}" rel="prev"><span aria-hidden="true">←</span> Posts mais recentes</a>
            @endif
        </nav>
    @endif
</x-layout>
