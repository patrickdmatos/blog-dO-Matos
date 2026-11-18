<?php

namespace App\Providers;

use App\Blog\ContentRepository;
use App\Blog\Markdown\MarkdownRenderer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MarkdownRenderer::class, fn () => new MarkdownRenderer(
            parse_url((string) config('blog.url'), PHP_URL_HOST) ?: null,
        ));

        $this->app->singleton(ContentRepository::class, fn ($app) => new ContentRepository(
            $app->make(MarkdownRenderer::class),
            $app['cache']->store(),
        ));
    }

    public function boot(): void
    {
        CarbonImmutable::setLocale(str_replace('-', '_', config('blog.locale')));

        // Composer (e não View::share) para refletir mudanças em tempo de
        // execução, como o --url do comando blog:build.
        View::composer('*', fn ($view) => $view->with('blog', config('blog')));
    }
}
