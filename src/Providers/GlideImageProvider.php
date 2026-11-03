<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Providers;

use Eolica\LaravelImage\ImageProvider;
use Eolica\LaravelImage\ImageModifiers;
use Eolica\LaravelImage\OperationsMapper;
use League\Glide\Urls\UrlBuilder;
use League\Glide\Urls\UrlBuilderFactory;
use RuntimeException;

final readonly class GlideImageProvider implements ImageProvider
{
    private OperationsMapper $mapper;

    private UrlBuilder $builder;

    public function __construct(
        private string $basePath,
        private string $signKey,
        private ?string $appUrl = null,
    ) {
        if (!class_exists(UrlBuilderFactory::class)) {
            throw new RuntimeException(
                'The Glide image provider requires league/glide. Run: composer require league/glide'
            );
        }

        $this->mapper = new OperationsMapper(
            keyMap: [
                'width' => 'w',
                'height' => 'h',
                'quality' => 'q',
                'format' => 'fm',
                'fit' => 'fit',
            ],
            valueMap: [
                'fit' => [
                    'cover' => 'crop',
                    'cover-center' => 'crop-center',
                    'cover-top-left' => 'crop-top-left',
                ],
            ],
        );

        $this->builder = UrlBuilderFactory::create($this->basePath, $this->signKey);
    }

    public function url(string $src, ImageModifiers $modifiers): string
    {
        $operations = $this->mapper->map($modifiers);

        $relativeUrl = $this->builder->getUrl($src, $operations);

        $appUrl = rtrim($this->appUrl ?? '', '/');

        if ($appUrl === '') {
            return $relativeUrl;
        }

        return $appUrl.$relativeUrl;
    }
}
