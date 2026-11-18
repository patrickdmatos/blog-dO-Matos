<?php

namespace Tests\Unit;

use App\Blog\Markdown\MarkdownRenderer;
use PHPUnit\Framework\TestCase;

class MarkdownRendererTest extends TestCase
{
    private MarkdownRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new MarkdownRenderer('blog.test');
    }

    public function test_le_front_matter_e_gera_html(): void
    {
        $result = $this->renderer->render("---\ntitle: Olá\ntags: [a, b]\n---\n\nTexto **forte**.");

        $this->assertSame('Olá', $result->frontMatter['title']);
        $this->assertSame(['a', 'b'], $result->frontMatter['tags']);
        $this->assertSame('<p>Texto <strong>forte</strong>.</p>', $result->html);
    }

    public function test_titulos_ganham_ancoras_ascii_e_sumario(): void
    {
        $result = $this->renderer->render("## Configuração\n\n### Detalhes\n\n## Configuração");

        $this->assertStringContainsString('<h2 id="configuracao">', $result->html);
        $this->assertStringContainsString('id="configuracao-1"', $result->html);
        $this->assertSame([
            ['level' => 2, 'id' => 'configuracao', 'text' => 'Configuração'],
            ['level' => 3, 'id' => 'detalhes', 'text' => 'Detalhes'],
            ['level' => 2, 'id' => 'configuracao-1', 'text' => 'Configuração'],
        ], $result->toc);
    }

    public function test_codigo_e_realcado_no_servidor_e_acessivel_por_teclado(): void
    {
        $result = $this->renderer->render("```php\n\$a = 1;\n```");

        $this->assertStringContainsString('<pre tabindex="0" role="region" aria-label="Código em php" data-lang="php">', $result->html);
        $this->assertStringContainsString('hl-variable', $result->html);
    }

    public function test_links_externos_e_imagens(): void
    {
        $result = $this->renderer->render('[fora](https://example.org) [dentro](https://blog.test/x) ![](a.png) ![Gato](b.png)');

        $this->assertMatchesRegularExpression('/<a rel="noopener noreferrer" class="external" href="https:\/\/example.org">/', $result->html);
        $this->assertStringNotContainsString('target="_blank"', $result->html);
        $this->assertStringContainsString('<a href="https://blog.test/x">', $result->html);
        $this->assertStringContainsString('loading="lazy"', $result->html);
        $this->assertSame(['a.png'], $result->imagesWithoutAlt);
    }

    public function test_gfm_e_notas_de_rodape(): void
    {
        $result = $this->renderer->render("| a | b |\n|---|---|\n| 1 | 2 |\n\n- [x] feito\n\nTexto[^1]\n\n[^1]: Nota.");

        $this->assertStringContainsString('<table>', $result->html);
        $this->assertStringContainsString('type="checkbox"', $result->html);
        $this->assertStringContainsString('role="doc-noteref"', $result->html);
    }

    public function test_links_javascript_sao_bloqueados(): void
    {
        $result = $this->renderer->render('[x](javascript:alert(1))');

        $this->assertStringNotContainsString('javascript:', $result->html);
    }
}
