<?php

namespace App\Blog;

use Carbon\CarbonImmutable;

/**
 * Um documento Markdown já interpretado (post ou página).
 */
final readonly class Post
{
    /**
     * @param  list<string>  $tags
     * @param  list<array{level:int, id:string, text:string}>  $toc
     * @param  array<string, mixed>  $meta  Front matter completo, para campos extras.
     */
    public function __construct(
        public string $slug,
        public string $title,
        public string $description,
        public ?CarbonImmutable $date,
        public ?CarbonImmutable $updated,
        public array $tags,
        public bool $draft,
        public string $html,
        public array $toc,
        public int $readingTime,
        public ?string $cover,
        public ?string $coverAlt,
        public string $sourcePath,
        public array $meta = [],
    ) {}

    public function lastModified(): ?CarbonImmutable
    {
        return $this->updated ?? $this->date;
    }

    public function hasTag(string $tag): bool
    {
        return in_array(Tag::slug($tag), array_map(Tag::slug(...), $this->tags), true);
    }
}
