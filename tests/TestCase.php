<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests;

use Eolica\LaravelImage\ImageProviderRegistry;
use Eolica\LaravelImage\ImageServiceProvider;
use Eolica\LaravelImage\ProviderSettings;
use Eolica\LaravelImage\Tests\Fixtures\CollectingImagePreloadPublisher;
use Eolica\LaravelImage\Tests\Fixtures\StubImageProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ImageServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app->afterResolving(ImageProviderRegistry::class, function (ImageProviderRegistry $registry): void {
            $registry->register(
                StubImageProvider::class,
                fn (ProviderSettings $settings): StubImageProvider => new StubImageProvider(),
            );
        });

        $app['config']->set('image.provider', 'stub');

        $app['config']->set('image.providers.stub', [
            'driver' => StubImageProvider::class,
            'defaults' => [],
        ]);

        $app['config']->set('image.screens', [
            'md' => 768,
        ]);

        $app['config']->set('image.presets', [
            'og' => [
                'width' => 1200,
                'height' => 630,
                'fit' => 'cover',
                'format' => 'webp',
                'quality' => 80,
            ],
            'card' => [
                'width' => 400,
                'height' => 300,
                'fit' => 'cover',
                'format' => 'webp',
            ],
        ]);

        $app->singleton(CollectingImagePreloadPublisher::class);

        $app['config']->set('image.preload.publisher', CollectingImagePreloadPublisher::class);
    }
}
