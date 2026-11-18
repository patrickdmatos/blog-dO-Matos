@props(['tags', 'label' => 'Tags'])
@if (count($tags))
    <ul class="tag-list" aria-label="{{ $label }}">
        @foreach ($tags as $tag)
            <li><a href="{{ route('tags.show', \App\Blog\Tag::slug($tag), false) }}">#{{ $tag }}</a></li>
        @endforeach
    </ul>
@endif
