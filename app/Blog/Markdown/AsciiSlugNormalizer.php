<?php

namespace App\Blog\Markdown;

use Illuminate\Support\Str;
use League\CommonMark\Normalizer\TextNormalizerInterface;

/**
 * Âncoras ASCII para os títulos: "Configuração" -> "#configuracao".
 */
final class AsciiSlugNormalizer implements TextNormalizerInterface
{
    public function normalize(string $text, array $context = []): string
    {
        return Str::slug($text) ?: 'secao';
    }
}
