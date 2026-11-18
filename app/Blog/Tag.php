<?php

namespace App\Blog;

use Illuminate\Support\Str;

final class Tag
{
    public static function slug(string $tag): string
    {
        return Str::slug($tag);
    }
}
