<?php

use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;

Route::controller(BlogController::class)->group(function () {
    Route::get('/', 'index')->name('home');
    Route::get('/pagina/{page}', 'index')->whereNumber('page')->name('home.page');

    Route::get('/posts/{slug}', 'show')->where('slug', '[a-z0-9-]+')->name('posts.show');

    Route::get('/tags', 'tags')->name('tags.index');
    Route::get('/tags/{tag}', 'tag')->where('tag', '[a-z0-9-]+')->name('tags.show');

    Route::get('/feed.xml', 'feed')->name('feed');
    Route::get('/sitemap.xml', 'sitemap')->name('sitemap');
    Route::get('/robots.txt', 'robots')->name('robots');

    // Páginas avulsas em content/pages (ex.: /sobre). Fica por último.
    Route::get('/{slug}', 'page')->where('slug', '[a-z0-9-]+')->name('pages.show');
});
