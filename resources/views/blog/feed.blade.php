{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
@php($site = rtrim($blog['url'], '/'))
<feed xmlns="http://www.w3.org/2005/Atom" xml:lang="{{ $blog['locale'] }}">
    <title>{{ $blog['name'] }}</title>
    <subtitle>{{ $blog['description'] }}</subtitle>
    <link href="{{ $site }}/feed.xml" rel="self" type="application/atom+xml"/>
    <link href="{{ $site }}/" rel="alternate" type="text/html"/>
    <id>{{ $site }}/</id>
    <updated>{{ ($posts->first()?->lastModified() ?? now())->toAtomString() }}</updated>
    <author><name>{{ $blog['author'] }}</name></author>
@foreach ($posts as $post)
    @php($url = $site.route('posts.show', $post->slug, false))
    <entry>
        <title>{{ $post->title }}</title>
        <link href="{{ $url }}" rel="alternate" type="text/html"/>
        <id>{{ $url }}</id>
        <published>{{ $post->date->toAtomString() }}</published>
        <updated>{{ $post->lastModified()->toAtomString() }}</updated>
        <summary>{{ $post->description }}</summary>
        <content type="html">{{ $post->html }}</content>
@foreach ($post->tags as $tag)
        <category term="{{ $tag }}"/>
@endforeach
    </entry>
@endforeach
</feed>
