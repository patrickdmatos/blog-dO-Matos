---
title: Acessibilidade começa no Markdown
description: Boas práticas para escrever posts que todo mundo consegue ler.
tags: [acessibilidade, markdown]
---

Um blog acessível depende tanto do tema quanto do conteúdo. Algumas regras
simples ao escrever:

## Use títulos em ordem

O título do post já é o `h1`. Dentro do texto, comece com `##` e não pule
níveis (de `##` direto para `####`, por exemplo).

## Descreva as imagens

Todo `![texto alternativo](imagem.jpg)` precisa de um texto alternativo que
explique a imagem. O comando de build avisa quando encontra uma imagem sem
descrição.

## Links com texto significativo

Prefira [ler a documentação do Laravel](https://laravel.com/docs) a
"clique aqui". Leitores de tela costumam listar os links fora do contexto.
