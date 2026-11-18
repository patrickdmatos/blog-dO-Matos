@props([
    'title' => null,
    'description' => null,
    'type' => 'website',
    'image' => null,
    'published' => null,
])
@php
    $siteUrl = rtrim($blog['url'], '/');
    $path = '/'.ltrim(request()->path(), '/');
    $canonical = $siteUrl.($path === '/' ? '/' : $path);
    $pageTitle = $title ? $title.' · '.$blog['name'] : $blog['name'];
    $metaDescription = $description ?: $blog['description'];
    $imageUrl = $image ? (str_starts_with($image, 'http') ? $image : $siteUrl.'/'.ltrim($image, '/')) : null;
@endphp
<!DOCTYPE html>
<html lang="{{ $blog['locale'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="alternate" type="application/atom+xml" title="{{ $blog['name'] }}" href="{{ route('feed', absolute: false) }}">
    <link rel="icon" href="/favicon.ico" sizes="any">
    {{-- ?v=hash: permite cache "immutable" na Cloudflare sem servir CSS velho --}}
    <link rel="stylesheet" href="/css/app.css?v={{ substr(md5_file(public_path('css/app.css')), 0, 10) }}">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#16181d" media="(prefers-color-scheme: dark)">

    <meta property="og:site_name" content="{{ $blog['name'] }}">
    <meta property="og:locale" content="{{ str_replace('-', '_', $blog['locale']) }}">
    <meta property="og:type" content="{{ $type }}">
    <meta property="og:title" content="{{ $title ?? $blog['name'] }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    @if ($imageUrl)
        <meta property="og:image" content="{{ $imageUrl }}">
        <meta name="twitter:card" content="summary_large_image">
    @else
        <meta name="twitter:card" content="summary">
    @endif
    @if ($published)
        <meta property="article:published_time" content="{{ $published->toIso8601String() }}">
    @endif
    {{ $head ?? '' }}
</head>
<body>
    <a class="skip-link" href="#conteudo">Pular para o conteúdo</a>

    <header class="site-header">
        <div class="container">
            <a class="site-title" href="{{ route('home', absolute: false) }}" @if ($path === '/') aria-current="page" @endif>
                {{ $blog['name'] }}
            </a>
            <nav aria-label="Principal">
                <ul class="menu">
                    @foreach ($blog['menu'] as $item)
                        @php
                            $href = isset($item['route']) ? route($item['route'], absolute: false) : $item['url'];
                            $active = $href === '/' ? $path === '/' : str_starts_with($path, $href);
                        @endphp
                        <li><a href="{{ $href }}" @if ($active) aria-current="page" @endif>{{ $item['label'] }}</a></li>
                    @endforeach
                    <li><a href="{{ route('feed', absolute: false) }}">RSS</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main id="conteudo" class="container" tabindex="-1">
        {{ $slot }}
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; {{ now()->year }} {{ $blog['author'] }}. Escrito em Markdown, servido pela Cloudflare.</p>
        </div>
    </footer>
</body>
</html>
