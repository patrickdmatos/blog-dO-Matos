<?php

namespace Tests;

use App\Blog\ContentRepository;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Os testes usam um conteúdo fixo, independente de content/.
        config([
            'blog.paths.posts' => __DIR__.'/Fixtures/content/posts',
            'blog.paths.pages' => __DIR__.'/Fixtures/content/pages',
            'blog.url' => 'https://blog.test',
            'blog.show_drafts' => false,
            'blog.cache' => false,
            'blog.per_page' => 10,
        ]);

        $this->app->forgetInstance(ContentRepository::class);
    }
}
