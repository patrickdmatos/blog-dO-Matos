# Blog do Matos

Blog em **Laravel 13** cujo conteúdo é escrito em **Markdown** e publicado como
**site estático no Cloudflare Pages**.

- **Dinâmico para escrever:** em desenvolvimento o Laravel lê os `.md` a cada
  requisição. Salvou o arquivo, atualizou a página.
- **Leve para publicar:** `php artisan blog:build` renderiza todas as rotas com
  as mesmas views Blade e gera HTML puro em `dist/`. Em produção não roda PHP,
  não há banco de dados, sessão, cookies nem JavaScript.
- **Acessível:** HTML semântico, link "pular para o conteúdo", foco visível,
  contraste AA, modo escuro automático, `prefers-reduced-motion`,
  alto contraste do Windows, blocos de código navegáveis pelo teclado e aviso
  no build para imagens sem texto alternativo.

## Como funciona

```text
content/posts/*.md ─┐                          ┌─► php artisan serve  (dev, dinâmico)
content/pages/*.md ─┼─► ContentRepository ─► Blade
                    │   (CommonMark + YAML)    └─► php artisan blog:build ─► dist/ ─► Cloudflare Pages
```

| Peça | Arquivo |
| --- | --- |
| Configuração do blog | [config/blog.php](config/blog.php) |
| Markdown → HTML (GFM, notas, âncoras, realce de código) | [app/Blog/Markdown/MarkdownRenderer.php](app/Blog/Markdown/MarkdownRenderer.php) |
| Leitura dos arquivos, slugs, datas, tags, cache | [app/Blog/ContentRepository.php](app/Blog/ContentRepository.php) |
| Rotas | [routes/web.php](routes/web.php) |
| Controller | [app/Http/Controllers/BlogController.php](app/Http/Controllers/BlogController.php) |
| Views | [resources/views](resources/views) |
| CSS (sem build, ~10 KB) | [public/css/app.css](public/css/app.css) |
| Export estático | [app/Console/Commands/BuildStaticSite.php](app/Console/Commands/BuildStaticSite.php) |

### Rotas

| URL | Conteúdo | Arquivo gerado |
| --- | --- | --- |
| `/` e `/pagina/{n}` | lista de posts | `index.html`, `pagina/2.html` |
| `/posts/{slug}` | post | `posts/{slug}.html` |
| `/tags`, `/tags/{tag}` | tags | `tags.html`, `tags/{tag}.html` |
| `/{slug}` | página de `content/pages` | `{slug}.html` |
| `/feed.xml`, `/sitemap.xml`, `/robots.txt` | Atom, sitemap, robots | idem |
| qualquer outra | 404 | `404.html` |

O Cloudflare Pages serve `posts/ola.html` em `/posts/ola`, então as URLs do
site estático são as mesmas do modo dinâmico.

## Escrevendo

Crie `content/posts/AAAA-MM-DD-slug.md`:

```markdown
---
title: Meu post
description: Resumo para listagem, feed e redes sociais.   # opcional
date: 2026-11-18          # opcional se estiver no nome do arquivo
updated: 2026-11-20       # opcional
tags: [laravel, php]
draft: true               # rascunho: não é publicado
cover: /images/capa.jpg   # opcional (coloque em public/images)
cover_alt: Descrição da capa
toc: false                # opcional: esconde o sumário
slug: outro-slug          # opcional
---

Texto em **Markdown**.
```

Sem `description`, o primeiro parágrafo vira o resumo. O sumário aparece quando
o post tem 3 ou mais títulos `##`/`###`.

Recursos suportados: GFM (tabelas, listas de tarefa, riscado, autolink), notas
de rodapé, listas de definição, atributos (`{.callout}`), HTML embutido
(`<details>`, `<figure>`…), âncoras nos títulos e realce de sintaxe feito no
servidor com [tempest/highlight](https://github.com/tempestphp/highlight).

## Rodando localmente

Requisitos: PHP 8.3+ (extensões `mbstring`, `intl`, `dom`, `xml`, `curl`,
`tokenizer`, `fileinfo` e `openssl`) e Composer.
Não precisa de Node nem de banco de dados.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve          # http://localhost:8000
```

### Ambiente sem sudo

Em máquinas sem PHP instalado e sem acesso root, use o build estático do
[static-php-cli](https://static-php.dev) (o build `bulk` já traz `intl`,
`mbstring`, `dom`, `curl` e `tokenizer`) e o Composer em `~/.local`:

```bash
mkdir -p ~/.local/php ~/.local/bin

# PHP 8.3 estático (confira a versão mais recente em https://dl.static-php.dev/static-php-cli/bulk/)
curl -L -o /tmp/php.tar.gz \
  https://dl.static-php.dev/static-php-cli/bulk/php-8.3.32-cli-linux-x86_64.tar.gz
tar -xzf /tmp/php.tar.gz -C ~/.local/php
chmod +x ~/.local/php/php
ln -sf ~/.local/php/php ~/.local/bin/php

# Composer
curl -L -o ~/.local/bin/composer https://getcomposer.org/composer-stable.phar
chmod +x ~/.local/bin/composer

# Garanta ~/.local/bin no PATH
export PATH="$HOME/.local/bin:$PATH"
php -v && composer --version
```

Depois siga os comandos acima. Para validar tudo antes de um push:

```bash
php artisan test
BLOG_SHOW_DRAFTS=false php artisan blog:build
```

Com `BLOG_SHOW_DRAFTS=true` no `.env`, os rascunhos aparecem localmente.

```bash
php artisan test                                       # testes
php artisan blog:build --url=https://seu-blog.com.br   # gera dist/
php -S localhost:8080 -t dist                          # pré-visualiza o estático
```

Opções do build: `--output=pasta`, `--url=https://...` e `--drafts`
(inclui rascunhos, útil para pré-visualizações).

## Publicando no Cloudflare Pages

O build de imagem do Cloudflare Pages não tem PHP, então o site é gerado no
**GitHub Actions** e enviado com o Wrangler
([.github/workflows/deploy.yml](.github/workflows/deploy.yml)).

1. No painel da Cloudflare: **Workers & Pages → Create → Pages → Direct Upload**,
   e crie um projeto (ex.: `blog-do-matos`).
2. Crie um API Token com a permissão **Cloudflare Pages: Edit**.
3. No GitHub, em **Settings → Secrets and variables → Actions**:
   - Secrets: `CLOUDFLARE_API_TOKEN` e `CLOUDFLARE_ACCOUNT_ID`
   - Variables: `BLOG_URL` (ex.: `https://blog.seudominio.com.br`) e
     `CLOUDFLARE_PROJECT_NAME`
4. Faça push na `main`. Cada PR também ganha uma URL de preview.

Sem os segredos, o workflow só roda os testes e o build.

Deploy manual (com o [Wrangler](https://developers.cloudflare.com/workers/wrangler/) instalado):

```bash
php artisan blog:build --url=https://seu-blog.com.br
npx wrangler pages deploy dist --project-name=blog-do-matos
```

O build gera um `_headers` com cabeçalhos de segurança e cache longo para o
CSS (versionado por hash). Para personalizá-lo, crie `public/_headers`.

## Licença

GPL-3.0. Veja [LICENSE](LICENSE).
