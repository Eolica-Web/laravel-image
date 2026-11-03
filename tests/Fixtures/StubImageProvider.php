<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Fixtures;

use Eolica\LaravelImage\ImageModifiers;
use Eolica\LaravelImage\ImageProvider;

final readonly class StubImageProvider implements ImageProvider
{
    public const BASE_URL = 'https://test.invalid';

    public function url(string $src, ImageModifiers $modifiers): string
    {
        $path = ltrim($src, '/');
        $params = array_filter([
            'w' => $modifiers->width,
            'h' => $modifiers->height,
            'fit' => $modifiers->fit,
            'format' => $modifiers->format,
            'q' => $modifiers->quality,
        ], fn (mixed $value): bool => $value !== null);

        $url = self::BASE_URL.'/'.$path;

        if ($params === []) {
            return $url;
        }

        return $url.'?'.http_build_query($params);
    }
}
