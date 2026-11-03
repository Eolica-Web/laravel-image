<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Providers;

use Eolica\LaravelImage\ImageProvider;
use Eolica\LaravelImage\ImageModifiers;
use Eolica\LaravelImage\OperationsMapper;

final readonly class BunnyImageProvider implements ImageProvider
{
    private OperationsMapper $mapper;

    public function __construct(
        private string $baseUrl,
    ) {
        $this->mapper = new OperationsMapper(
            keyMap: [
                'width' => 'width',
                'height' => 'height',
                'quality' => 'quality',
                'format' => 'format',
            ],
            valueMap: [
                'fit' => [
                    'cover' => [
                        'crop' => 'true',
                        'crop_gravity' => 'center',
                    ],
                    'cover-center' => [
                        'crop' => 'true',
                        'crop_gravity' => 'center',
                    ],
                    'cover-top-left' => [
                        'crop' => 'true',
                        'crop_gravity' => 'northwest',
                    ],
                ],
            ],
        );
    }

    public function url(string $src, ImageModifiers $modifiers): string
    {
        $operations = $this->mapper->map($modifiers);

        $baseUrl = rtrim($this->baseUrl, '/');

        $path = ltrim($src, '/');

        $url = "{$baseUrl}/{$path}";

        if ($operations === []) {
            return $url;
        }

        return $url.'?'.http_build_query($operations);
    }
}
