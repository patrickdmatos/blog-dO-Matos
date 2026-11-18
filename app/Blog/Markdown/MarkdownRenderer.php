<?php

namespace App\Blog\Markdown;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\DescriptionList\DescriptionListExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalink;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\StringContainerHelper;

/**
 * Converte Markdown (com front matter YAML) em HTML semântico e acessível.
 *
 * Suporta: GFM (tabelas, task lists, strikethrough, autolink), notas de
 * rodapé, listas de definição, atributos ({.classe #id}), âncoras nos
 * títulos e realce de sintaxe no servidor (sem JavaScript no navegador).
 */
final class MarkdownRenderer
{
    // Incremente ao mudar a configuração do parser: invalida o cache.
    public const VERSION = 1;

    private MarkdownConverter $converter;

    public function __construct(?string $siteHost = null)
    {
        $environment = new Environment([
            // O conteúdo vem do próprio repositório, então HTML embutido é
            // permitido (útil para <figure>, <details>, embeds etc.).
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
            'slug_normalizer' => [
                'instance' => new AsciiSlugNormalizer,
                'unique' => 'document',
            ],
            'heading_permalink' => [
                'apply_id_to_heading' => true,
                'id_prefix' => '',
                'fragment_prefix' => '',
                'insert' => 'after',
                'min_heading_level' => 2,
                'max_heading_level' => 4,
                'symbol' => '#',
                'title' => 'Link permanente para esta seção',
                'html_class' => 'heading-anchor',
                'aria_hidden' => true,
            ],
            'external_link' => [
                'internal_hosts' => array_filter([$siteHost]),
                // Abrir em nova aba prejudica a navegação de quem usa leitor
                // de tela; mantemos a decisão com quem está lendo.
                'open_in_new_window' => false,
                'noopener' => 'external',
                'noreferrer' => 'external',
                'html_class' => 'external',
            ],
            'footnote' => [
                'backref_symbol' => '↩',
                'container_add_hr' => false,
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new FrontMatterExtension);
        $environment->addExtension(new HeadingPermalinkExtension);
        $environment->addExtension(new ExternalLinkExtension);
        $environment->addExtension(new FootnoteExtension);
        $environment->addExtension(new DescriptionListExtension);
        $environment->addExtension(new AttributesExtension);

        $environment->addRenderer(FencedCode::class, new HighlightedCodeRenderer, 10);

        // Imagens: carregamento preguiçoso e decodificação assíncrona.
        $environment->addEventListener(DocumentParsedEvent::class, function (DocumentParsedEvent $event): void {
            foreach ($event->getDocument()->iterator() as $node) {
                if ($node instanceof Image) {
                    $node->data->set('attributes/loading', 'lazy');
                    $node->data->set('attributes/decoding', 'async');
                }
            }
        });

        $this->converter = new MarkdownConverter($environment);
    }

    public function render(string $markdown): RenderedMarkdown
    {
        $result = $this->converter->convert($markdown);

        $frontMatter = $result instanceof RenderedContentWithFrontMatter
            ? ($result->getFrontMatter() ?? [])
            : [];

        $document = $result->getDocument();
        $html = trim($result->getContent());

        return new RenderedMarkdown(
            frontMatter: is_array($frontMatter) ? $frontMatter : [],
            html: $html,
            toc: $this->tableOfContents($document),
            plainText: $this->toText($html),
            summary: preg_match('/<p>(.*?)<\/p>/s', $html, $m) ? $this->toText($m[1]) : '',
            imagesWithoutAlt: $this->imagesWithoutAlt($document),
        );
    }

    /**
     * HTML -> texto corrido, preservando a separação entre blocos.
     */
    private function toText(string $html): string
    {
        $html = preg_replace('/<a [^>]*class="heading-anchor"[^>]*>.*?<\/a>/s', '', $html);
        $html = preg_replace('/<sup [^>]*>.*?<\/sup>/s', '', $html); // marcadores de nota
        $html = preg_replace('/<\/?(p|h\d|li|pre|td|th|tr|blockquote|div|dt|dd|br)\b[^>]*>/i', ' ', $html);

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * @return list<array{level:int, id:string, text:string}>
     */
    private function tableOfContents(Document $document): array
    {
        $toc = [];

        foreach ($document->iterator() as $node) {
            if (! $node instanceof Heading || $node->getLevel() < 2 || $node->getLevel() > 3) {
                continue;
            }

            $permalink = null;
            foreach ($node->children() as $child) {
                if ($child instanceof HeadingPermalink) {
                    $permalink = $child;
                }
            }

            if ($permalink === null) {
                continue;
            }

            $toc[] = [
                'level' => $node->getLevel(),
                'id' => $permalink->getSlug(),
                'text' => trim(StringContainerHelper::getChildText($node, [HeadingPermalink::class])),
            ];
        }

        return $toc;
    }

    /**
     * Lista as imagens sem texto alternativo, para avisar no build.
     *
     * @return list<string>
     */
    private function imagesWithoutAlt(Document $document): array
    {
        $missing = [];

        foreach ($document->iterator() as $node) {
            if ($node instanceof Image && trim(StringContainerHelper::getChildText($node)) === '') {
                $missing[] = $node->getUrl();
            }
        }

        return $missing;
    }
}
