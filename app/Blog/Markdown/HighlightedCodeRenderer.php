<?php

namespace App\Blog\Markdown;

use InvalidArgumentException;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Tempest\Highlight\Highlighter;

/**
 * Realce de sintaxe feito no servidor (zero JavaScript no cliente).
 *
 * O bloco recebe tabindex="0" para que quem navega pelo teclado consiga
 * rolar horizontalmente códigos longos, e um rótulo com a linguagem.
 */
final class HighlightedCodeRenderer implements NodeRendererInterface
{
    public function __construct(
        private readonly Highlighter $highlighter = new Highlighter,
    ) {}

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (! $node instanceof FencedCode) {
            throw new InvalidArgumentException('Esperado um bloco '.FencedCode::class);
        }

        $info = $node->getInfoWords()[0] ?? '';
        $language = preg_match('/^[\w+-]+/', $info, $m) ? strtolower($m[0]) : '';

        try {
            $code = $language !== ''
                ? $this->highlighter->parse($node->getLiteral(), $language)
                : e($node->getLiteral());
        } catch (\Throwable) {
            $code = e($node->getLiteral());
        }

        $label = $language !== '' ? 'Código em '.$language : 'Bloco de código';
        $langAttr = $language !== '' ? ' data-lang="'.e($language).'"' : '';
        $class = $language !== '' ? ' class="language-'.e($language).'"' : '';

        return '<pre tabindex="0" role="region" aria-label="'.e($label).'"'.$langAttr.'>'
            .'<code'.$class.'>'.rtrim($code, "\n").'</code></pre>'."\n";
    }
}
