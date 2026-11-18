<?php

namespace App\Blog;

use App\Blog\Markdown\MarkdownRenderer;
use App\Blog\Markdown\RenderedMarkdown;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

/**
 * Lê os arquivos Markdown de content/ e os transforma em objetos Post.
 *
 * Não há banco de dados: o sistema de arquivos é a fonte da verdade.
 * Cada arquivo interpretado fica em cache, com chave baseada no caminho,
 * data de modificação e tamanho, então editar um .md invalida só ele.
 */
class ContentRepository
{
    /** @var array<string, Collection<int, Post>> */
    private array $loaded = [];

    public function __construct(
        private readonly MarkdownRenderer $renderer,
        private readonly Cache $cache,
    ) {}

    /**
     * Posts publicados, do mais recente para o mais antigo.
     *
     * @return Collection<int, Post>
     */
    public function posts(?bool $includeDrafts = null): Collection
    {
        $includeDrafts ??= (bool) config('blog.show_drafts', false);

        return $this->load('posts', dated: true)
            ->when(! $includeDrafts, fn (Collection $posts) => $posts->reject(fn (Post $p) => $p->draft))
            ->sortByDesc(fn (Post $p) => $p->date?->getTimestamp() ?? 0)
            ->values();
    }

    public function post(string $slug): ?Post
    {
        return $this->posts()->first(fn (Post $p) => $p->slug === $slug);
    }

    /**
     * @return Collection<int, Post>
     */
    public function pages(?bool $includeDrafts = null): Collection
    {
        $includeDrafts ??= (bool) config('blog.show_drafts', false);

        return $this->load('pages', dated: false)
            ->when(! $includeDrafts, fn (Collection $pages) => $pages->reject(fn (Post $p) => $p->draft))
            ->values();
    }

    public function page(string $slug): ?Post
    {
        return $this->pages()->first(fn (Post $p) => $p->slug === $slug);
    }

    /**
     * Tags em uso: [slug => ['name' => ..., 'count' => ...]], por nome.
     *
     * @return Collection<string, array{name:string, slug:string, count:int}>
     */
    public function tags(?bool $includeDrafts = null): Collection
    {
        return $this->posts($includeDrafts)
            ->flatMap(fn (Post $p) => $p->tags)
            ->groupBy(fn (string $tag) => Tag::slug($tag))
            ->map(fn (Collection $group, string $slug) => [
                'name' => $group->first(),
                'slug' => $slug,
                'count' => $group->count(),
            ])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);
    }

    /**
     * @return Collection<int, Post>
     */
    public function postsWithTag(string $tagSlug, ?bool $includeDrafts = null): Collection
    {
        return $this->posts($includeDrafts)->filter(fn (Post $p) => $p->hasTag($tagSlug))->values();
    }

    /**
     * Posts vizinhos (para navegação "anterior / próximo").
     *
     * @return array{previous: ?Post, next: ?Post}
     */
    public function neighbours(Post $post): array
    {
        $posts = $this->posts();
        $index = $posts->search(fn (Post $p) => $p->slug === $post->slug);

        if ($index === false) {
            return ['previous' => null, 'next' => null];
        }

        return [
            'previous' => $posts->get($index + 1), // mais antigo
            'next' => $index > 0 ? $posts->get($index - 1) : null, // mais novo
        ];
    }

    /**
     * Avisos de acessibilidade encontrados no conteúdo (ex.: imagem sem alt).
     *
     * @return list<string>
     */
    public function accessibilityWarnings(): array
    {
        $warnings = [];

        foreach (['posts', 'pages'] as $type) {
            foreach ($this->files($type) as $file) {
                foreach ($this->renderFile($file)->imagesWithoutAlt as $src) {
                    $warnings[] = sprintf('%s: imagem "%s" sem texto alternativo', $this->relative($file), $src);
                }
            }
        }

        return $warnings;
    }

    /**
     * @return Collection<int, Post>
     */
    private function load(string $type, bool $dated): Collection
    {
        if (isset($this->loaded[$type])) {
            return $this->loaded[$type];
        }

        $items = collect($this->files($type))
            ->map(fn (string $file) => $this->makePost($file, $this->renderFile($file), $dated))
            ->keyBy('slug') // slugs duplicados: o último vence
            ->values();

        return $this->loaded[$type] = $items;
    }

    /**
     * @return list<string>
     */
    private function files(string $type): array
    {
        $path = config("blog.paths.{$type}");

        if (! $path || ! is_dir($path)) {
            return [];
        }

        $finder = Finder::create()->files()->in($path)->name(['*.md', '*.markdown'])->sortByName();

        return array_values(array_map(fn ($f) => $f->getPathname(), iterator_to_array($finder, false)));
    }

    private function renderFile(string $file): RenderedMarkdown
    {
        if (! config('blog.cache', true)) {
            return $this->renderer->render((string) file_get_contents($file));
        }

        $key = 'blog:md:v'.MarkdownRenderer::VERSION.':'.md5($file.'|'.filemtime($file).'|'.filesize($file));

        // Guardamos arrays (não objetos) porque o cache do Laravel não
        // desserializa classes por padrão.
        $data = $this->cache->rememberForever($key, function () use ($file) {
            $r = $this->renderer->render((string) file_get_contents($file));

            return [
                'frontMatter' => $r->frontMatter,
                'html' => $r->html,
                'toc' => $r->toc,
                'plainText' => $r->plainText,
                'summary' => $r->summary,
                'imagesWithoutAlt' => $r->imagesWithoutAlt,
            ];
        });

        return new RenderedMarkdown(...$data);
    }

    private function makePost(string $file, RenderedMarkdown $doc, bool $dated): Post
    {
        $meta = $doc->frontMatter;
        $basename = pathinfo($file, PATHINFO_FILENAME);

        // Arquivos podem seguir o padrão "2026-11-18-meu-post.md".
        $fileDate = null;
        if (preg_match('/^(\d{4}-\d{2}-\d{2})-(.+)$/', $basename, $m)) {
            $fileDate = $m[1];
            $basename = $m[2];
        }

        $slug = Str::slug((string) ($meta['slug'] ?? $basename));
        $title = (string) ($meta['title'] ?? Str::headline($basename));

        $date = $this->parseDate($meta['date'] ?? $fileDate);
        if ($dated && $date === null) {
            $date = CarbonImmutable::createFromTimestamp(filemtime($file));
        }

        $tags = $meta['tags'] ?? [];
        $tags = is_string($tags) ? array_map('trim', explode(',', $tags)) : (array) $tags;
        $tags = array_values(array_filter(array_map('strval', $tags)));

        // Sem description no front matter, usa o primeiro parágrafo.
        $description = (string) ($meta['description'] ?? Str::limit($doc->summary ?: $doc->plainText, 160));

        $words = preg_match_all('/[\p{L}\p{N}]+/u', $doc->plainText);
        $wpm = max(1, (int) config('blog.words_per_minute', 200));

        return new Post(
            slug: $slug,
            title: $title,
            description: $description,
            date: $date,
            updated: $this->parseDate($meta['updated'] ?? null),
            tags: $tags,
            draft: (bool) ($meta['draft'] ?? false),
            html: $doc->html,
            toc: ($meta['toc'] ?? true) ? $doc->toc : [],
            readingTime: max(1, (int) ceil($words / $wpm)),
            cover: isset($meta['cover']) ? (string) $meta['cover'] : null,
            coverAlt: isset($meta['cover_alt']) ? (string) $meta['cover_alt'] : null,
            sourcePath: $this->relative($file),
            meta: $meta,
        );
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        return match (true) {
            $value === null, $value === '' => null,
            // O parser YAML transforma datas (2026-11-18) em timestamp.
            // Mantemos o "horário de parede" (00:00) no fuso da aplicação.
            is_int($value) => CarbonImmutable::createFromTimestampUTC($value)->shiftTimezone(config('app.timezone')),
            $value instanceof DateTimeInterface => CarbonImmutable::instance($value),
            default => rescue(fn () => CarbonImmutable::parse((string) $value), null, false),
        };
    }

    private function relative(string $file): string
    {
        return ltrim(Str::after(str_replace('\\', '/', $file), str_replace('\\', '/', base_path())), '/');
    }
}
