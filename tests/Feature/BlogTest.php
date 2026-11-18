<?php

namespace Tests\Feature;

use App\Blog\ContentRepository;
use Tests\TestCase;

class BlogTest extends TestCase
{
    public function test_home_lista_posts_publicados_do_mais_novo_para_o_mais_antigo(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Segundo post', 'Primeiro post'])
            ->assertDontSee('Rascunho secreto')
            ->assertSee('<html lang="pt-BR">', false)
            ->assertSee('href="#conteudo"', false)
            ->assertSee('<link rel="canonical" href="https://blog.test/">', false);
    }

    public function test_repositorio_extrai_metadados(): void
    {
        $posts = app(ContentRepository::class)->posts();
        $primeiro = $posts->firstWhere('slug', 'primeiro');
        $segundo = $posts->firstWhere('slug', 'segundo');

        $this->assertSame('2026-01-10', $primeiro->date->toDateString());
        $this->assertSame('2026-02-01', $segundo->date->toDateString());
        $this->assertSame(['laravel', 'markdown'], $segundo->tags);
        $this->assertSame('Descrição personalizada.', $segundo->description);
        $this->assertSame('Introdução do post.', $primeiro->description);
        $this->assertSame(1, $primeiro->readingTime);
    }

    public function test_pagina_do_post(): void
    {
        $this->get('/posts/primeiro')
            ->assertOk()
            ->assertSee('<h1 id="titulo-post">Primeiro post</h1>', false)
            ->assertSee('<time datetime="2026-01-10">10 de janeiro de 2026</time>', false)
            ->assertSee('Nesta página')
            ->assertSee('href="#configuracao-inicial"', false)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee('href="/posts/segundo" rel="next"', false);
    }

    public function test_rascunho_nao_e_publicado(): void
    {
        $this->get('/posts/rascunho')->assertNotFound();

        config(['blog.show_drafts' => true]);
        $this->app->forgetInstance(ContentRepository::class);

        $this->get('/posts/rascunho')->assertOk()->assertSee('Rascunho');
    }

    public function test_tags(): void
    {
        $this->get('/tags')->assertOk()->assertSee('#laravel')->assertSee('2 posts');

        $this->get('/tags/laravel')
            ->assertOk()
            ->assertSee('Primeiro post')
            ->assertSee('Segundo post');

        $this->get('/tags/inexistente')->assertNotFound();
    }

    public function test_paginas_avulsas_e_404(): void
    {
        $this->get('/sobre')->assertOk()->assertSee('Sobre mim');

        $this->get('/nao-existe')
            ->assertNotFound()
            ->assertSee('Página não encontrada');
    }

    public function test_paginacao_por_segmento(): void
    {
        config(['blog.per_page' => 1]);

        $this->get('/')->assertOk()->assertSee('href="/pagina/2" rel="next"', false);
        $this->get('/pagina/2')->assertOk()->assertSee('Primeiro post')->assertDontSee('Segundo post');
        $this->get('/pagina/3')->assertNotFound();
    }

    public function test_feed_sitemap_e_robots(): void
    {
        $feed = $this->get('/feed.xml')->assertOk()->assertHeader('Content-Type', 'application/atom+xml; charset=UTF-8');
        $xml = simplexml_load_string($feed->getContent());
        $this->assertSame('https://blog.test/posts/segundo', (string) $xml->entry[0]->id);

        $sitemap = simplexml_load_string($this->get('/sitemap.xml')->assertOk()->getContent());
        $locs = array_map('strval', $sitemap->xpath('//*[local-name()="loc"]'));
        $this->assertContains('https://blog.test/posts/primeiro', $locs);
        $this->assertContains('https://blog.test/sobre', $locs);
        $this->assertNotContains('https://blog.test/posts/rascunho', $locs);

        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: https://blog.test/sitemap.xml');
    }

    public function test_nenhuma_resposta_define_cookies(): void
    {
        $this->assertEmpty($this->get('/')->headers->getCookies());
    }
}
