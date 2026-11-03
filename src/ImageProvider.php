<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

interface ImageProvider
{
    public function url(string $src, ImageModifiers $modifiers): string;
}
