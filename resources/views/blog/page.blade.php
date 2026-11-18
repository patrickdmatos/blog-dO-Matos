<x-layout :title="$page->title" :description="$page->description" :image="$page->cover">
    <article class="post" aria-labelledby="titulo-pagina">
        <header class="page-header">
            <h1 id="titulo-pagina">{{ $page->title }}</h1>
        </header>
        <div class="prose">
            {!! $page->html !!}
        </div>
    </article>
</x-layout>
