{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
@php($site = rtrim($blog['url'], '/'))
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ $site }}/</loc>
        @if ($posts->first())<lastmod>{{ $posts->first()->lastModified()->toDateString() }}</lastmod>@endif
    </url>
@foreach ($posts as $post)
    <url>
        <loc>{{ $site.route('posts.show', $post->slug, false) }}</loc>
        <lastmod>{{ $post->lastModified()->toDateString() }}</lastmod>
    </url>
@endforeach
@foreach ($pages as $page)
    <url><loc>{{ $site.route('pages.show', $page->slug, false) }}</loc></url>
@endforeach
    <url><loc>{{ $site }}/tags</loc></url>
@foreach ($tags as $tag)
    <url><loc>{{ $site.route('tags.show', $tag['slug'], false) }}</loc></url>
@endforeach
</urlset>
