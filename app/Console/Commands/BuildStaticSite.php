<?php

namespace App\Console\Commands;

use App\Blog\ContentRepository;
use App\Blog\Post;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use RuntimeException;

/**
 * Gera o blog inteiro como HTML estático, pronto para o Cloudflare Pages.
 *
 * Cada rota é renderizada pelo próprio kernel HTTP do Laravel (as mesmas
 * views do modo dinâmico) e gravada em disco. Ex.:
 *   /                 -> dist/index.html
 *   /posts/bem-vindo  -> dist/posts/bem-vindo.html  (Pages serve sem ".html")
 *   /feed.xml         -> dist/feed.xml
 */
class BuildStaticSite extends Command
{
    protected $signature = 'blog:build
        {--output= : Pasta de saída (padrão: dist)}
        {--url= : URL pública do site (sobrescreve BLOG_URL)}
        {--drafts : Inclui rascunhos (útil para pré-visualização)}';

    protected $description = 'Gera o blog como site estático para o Cloudflare Pages';

    public function handle(Filesystem $files, Kernel $kernel): int
    {
        $output = rtrim($this->option('output') ?: config('blog.build.output'), '/\\');

        if ($url = $this->option('url')) {
            config(['blog.url' => rtrim($url, '/')]);
        }

        config([
            'blog.show_drafts' => (bool) $this->option('drafts'),
            'blog.cache' => false, // o build sempre interpreta o Markdown do zero
        ]);
        // Instância nova do repositório com a configuração do build.
        app()->forgetInstance(ContentRepository::class);

        $siteUrl = (string) config('blog.url');
        URL::forceRootUrl($siteUrl);

        $content = app(ContentRepository::class);

        $this->components->info("Gerando site estático em [{$output}] para {$siteUrl}");

        $files->deleteDirectory($output);
        $files->ensureDirectoryExists($output);

        $this->copyPublicAssets($files, $output);

        $failures = 0;
        foreach ($this->uris($content) as $uri) {
            $failures += $this->renderTo($kernel, $files, $output, $uri, 200) ? 0 : 1;
        }

        // Página 404 usada automaticamente pelo Cloudflare Pages.
        $this->renderTo($kernel, $files, $output, '/__pagina-inexistente__', 404, '404.html');

        $this->writeHeaders($files, $output);

        foreach ($content->accessibilityWarnings() as $warning) {
            $this->components->warn($warning);
        }

        if ($failures > 0) {
            $this->components->error("{$failures} página(s) falharam.");

            return self::FAILURE;
        }

        $this->components->success(sprintf(
            '%d posts, %d páginas e %d tags publicados.',
            $content->posts()->count(),
            $content->pages()->count(),
            $content->tags()->count(),
        ));

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function uris(ContentRepository $content): array
    {
        $posts = $content->posts();
        $pages = (int) max(1, ceil($posts->count() / max(1, (int) config('blog.per_page'))));

        $uris = ['/', '/tags', '/feed.xml', '/sitemap.xml', '/robots.txt'];

        for ($i = 2; $i <= $pages; $i++) {
            $uris[] = route('home.page', ['page' => $i], false);
        }

        $posts->each(function (Post $post) use (&$uris) {
            $uris[] = route('posts.show', $post->slug, false);
        });

        $content->pages()->each(function (Post $page) use (&$uris) {
            $uris[] = route('pages.show', $page->slug, false);
        });

        foreach ($content->tags() as $tag) {
            $uris[] = route('tags.show', $tag['slug'], false);
        }

        return array_values(array_unique($uris));
    }

    private function renderTo(Kernel $kernel, Filesystem $files, string $output, string $uri, int $expected, ?string $target = null): bool
    {
        $request = Request::create($uri, 'GET');
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        if ($response->getStatusCode() !== $expected) {
            $this->components->error("{$uri} retornou HTTP {$response->getStatusCode()} (esperado {$expected})");

            if ($response->exception ?? null) {
                $this->line('  '.$response->exception->getMessage());
            }

            return false;
        }

        $path = $output.'/'.($target ?? $this->targetFor($uri));
        $files->ensureDirectoryExists(dirname($path));
        $files->put($path, $response->getContent());

        $this->components->twoColumnDetail($uri, str_replace($output.'/', '', $path));

        return true;
    }

    private function targetFor(string $uri): string
    {
        $uri = trim($uri, '/');

        if ($uri === '') {
            return 'index.html';
        }

        // Arquivos com extensão (feed.xml, robots.txt) mantêm o nome.
        return pathinfo($uri, PATHINFO_EXTENSION) !== '' ? $uri : $uri.'.html';
    }

    private function copyPublicAssets(Filesystem $files, string $output): void
    {
        $public = public_path();
        $skip = ['index.php', '.htaccess', 'hot', 'storage'];

        foreach ($files->allFiles($public, true) as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());

            if (in_array(explode('/', $relative)[0], $skip, true)) {
                continue;
            }

            $target = $output.'/'.$relative;
            $files->ensureDirectoryExists(dirname($target));

            if (! $files->copy($file->getPathname(), $target)) {
                throw new RuntimeException("Não foi possível copiar {$relative}");
            }
        }
    }

    /**
     * Cabeçalhos HTTP do Cloudflare Pages (_headers).
     */
    private function writeHeaders(Filesystem $files, string $output): void
    {
        if ($files->exists($output.'/_headers')) {
            return; // public/_headers personalizado tem prioridade
        }

        $files->put($output.'/_headers', <<<'TXT'
/*
  X-Content-Type-Options: nosniff
  Referrer-Policy: strict-origin-when-cross-origin
  X-Frame-Options: DENY
  Permissions-Policy: interest-cohort=()

/css/*
  Cache-Control: public, max-age=31536000, immutable

/images/*
  Cache-Control: public, max-age=2592000

/feed.xml
  Content-Type: application/atom+xml; charset=utf-8

TXT);
    }
}
