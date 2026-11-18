<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identidade do blog
    |--------------------------------------------------------------------------
    */

    'name' => env('BLOG_NAME', 'Blog do Matos'),

    'description' => env('BLOG_DESCRIPTION', 'Um blog leve, acessível e escrito em Markdown.'),

    'author' => env('BLOG_AUTHOR', 'Matos'),

    // Idioma usado no atributo lang do HTML e no feed.
    'locale' => env('BLOG_LOCALE', 'pt-BR'),

    // URL pública final (ex.: https://blog.exemplo.com.br). Usada em
    // canonical, Open Graph, feed e sitemap. Pode ser sobrescrita no build.
    'url' => env('BLOG_URL', env('APP_URL', 'http://localhost:8000')),

    /*
    |--------------------------------------------------------------------------
    | Conteúdo
    |--------------------------------------------------------------------------
    |
    | Posts ficam em content/posts e páginas avulsas (ex.: "sobre") em
    | content/pages. Cada arquivo .md pode ter front matter YAML.
    |
    */

    'paths' => [
        'posts' => env('BLOG_POSTS_PATH', base_path('content/posts')),
        'pages' => env('BLOG_PAGES_PATH', base_path('content/pages')),
    ],

    'per_page' => (int) env('BLOG_PER_PAGE', 10),

    // Palavras por minuto usadas no cálculo do tempo de leitura.
    'words_per_minute' => 200,

    // Exibe rascunhos (draft: true) durante o desenvolvimento local.
    // O build estático nunca publica rascunhos.
    'show_drafts' => (bool) env('BLOG_SHOW_DRAFTS', false),

    // Cache do conteúdo já interpretado (invalida sozinho quando um .md muda).
    'cache' => (bool) env('BLOG_CACHE', true),

    /*
    |--------------------------------------------------------------------------
    | Navegação principal
    |--------------------------------------------------------------------------
    */

    'menu' => [
        ['label' => 'Início', 'route' => 'home'],
        ['label' => 'Tags', 'route' => 'tags.index'],
        ['label' => 'Sobre', 'url' => '/sobre'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Build estático (Cloudflare Pages)
    |--------------------------------------------------------------------------
    */

    'build' => [
        'output' => env('BLOG_BUILD_OUTPUT', base_path('dist')),
    ],

];
