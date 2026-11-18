<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BuildStaticSiteTest extends TestCase
{
    private string $output;

    protected function setUp(): void
    {
        parent::setUp();
        $this->output = sys_get_temp_dir().'/blog-build-'.uniqid();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->output);
        parent::tearDown();
    }

    public function test_gera_site_estatico_para_cloudflare_pages(): void
    {
        $this->artisan('blog:build', ['--output' => $this->output, '--url' => 'https://meu.blog'])
            ->expectsOutputToContain('sem texto alternativo')
            ->assertSuccessful();

        foreach ([
            'index.html', '404.html', 'tags.html', 'sobre.html', 'feed.xml', 'sitemap.xml',
            'robots.txt', '_headers', 'css/app.css',
            'posts/primeiro.html', 'posts/segundo.html', 'tags/laravel.html',
        ] as $file) {
            $this->assertFileExists($this->output.'/'.$file);
        }

        $this->assertFileDoesNotExist($this->output.'/index.php');
        $this->assertFileDoesNotExist($this->output.'/posts/rascunho.html');

        $this->assertStringContainsString('https://meu.blog/posts/primeiro', File::get($this->output.'/feed.xml'));
        $this->assertStringContainsString('<link rel="canonical" href="https://meu.blog/posts/primeiro">', File::get($this->output.'/posts/primeiro.html'));
        $this->assertStringContainsString('Página não encontrada', File::get($this->output.'/404.html'));
    }

    public function test_opcao_drafts_inclui_rascunhos(): void
    {
        $this->artisan('blog:build', ['--output' => $this->output, '--drafts' => true])->assertSuccessful();

        $this->assertFileExists($this->output.'/posts/rascunho.html');
    }
}
