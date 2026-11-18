<?php

namespace App\Blog\Markdown;

final readonly class RenderedMarkdown
{
    /**
     * @param  array<string, mixed>  $frontMatter
     * @param  list<array{level:int, id:string, text:string}>  $toc
     * @param  list<string>  $imagesWithoutAlt
     */
    public function __construct(
        public array $frontMatter,
        public string $html,
        public array $toc,
        public string $plainText,
        public string $summary = '',
        public array $imagesWithoutAlt = [],
    ) {}
}
