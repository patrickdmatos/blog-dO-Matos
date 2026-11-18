<?php

namespace App\Http\Controllers;

use App\Blog\ContentRepository;
use App\Blog\Tag;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(private readonly ContentRepository $content) {}

    public function index(int $page = 1): View
    {
        $posts = $this->content->posts();
        $perPage = max(1, (int) config('blog.per_page'));
        $lastPage = max(1, (int) ceil($posts->count() / $perPage));

        abort_if($page < 1 || $page > $lastPage, 404);

        // Paginação por segmento (/pagina/2) em vez de ?page=2, para que
        // cada página possa ser exportada como um arquivo estático.
        $paginator = new LengthAwarePaginator(
            $posts->forPage($page, $perPage)->values(),
            $posts->count(),
            $perPage,
            $page,
        );

        return view('blog.index', [
            'posts' => $paginator,
            'previousUrl' => $page > 1 ? self::pageUrl($page - 1) : null,
            'nextUrl' => $page < $lastPage ? self::pageUrl($page + 1) : null,
        ]);
    }

    public function show(string $slug): View
    {
        $post = $this->content->post($slug) ?? abort(404);

        return view('blog.show', [
            'post' => $post,
            ...$this->content->neighbours($post),
        ]);
    }

    public function tags(): View
    {
        return view('blog.tags', ['tags' => $this->content->tags()]);
    }

    public function tag(string $tag): View
    {
        $tags = $this->content->tags();
        $info = $tags->get(Tag::slug($tag)) ?? abort(404);

        return view('blog.tag', [
            'tag' => $info,
            'posts' => $this->content->postsWithTag($info['slug']),
        ]);
    }

    public function page(string $slug): View
    {
        $page = $this->content->page($slug) ?? abort(404);

        return view('blog.page', ['page' => $page]);
    }

    public function feed(): Response
    {
        return response()
            ->view('blog.feed', ['posts' => $this->content->posts()->take(20)])
            ->header('Content-Type', 'application/atom+xml; charset=UTF-8');
    }

    public function sitemap(): Response
    {
        return response()
            ->view('blog.sitemap', [
                'posts' => $this->content->posts(),
                'pages' => $this->content->pages(),
                'tags' => $this->content->tags(),
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $sitemap = rtrim((string) config('blog.url'), '/').'/sitemap.xml';

        return response("User-agent: *\nAllow: /\n\nSitemap: {$sitemap}\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public static function pageUrl(int $page): string
    {
        return $page <= 1 ? route('home', absolute: false) : route('home.page', ['page' => $page], false);
    }
}
