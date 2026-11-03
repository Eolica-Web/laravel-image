<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

use Eolica\LaravelImage\PreloadPublishers\StackImagePreloadPublisher;
use Eolica\LaravelImage\Providers\BunnyImageProvider;
use Eolica\LaravelImage\Providers\GlideImageProvider;
use Eolica\LaravelImage\Providers\ImgproxyImageProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

final class ImageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/image.php', 'image');

        $this->app->singleton(ImageProviderRegistry::class, function (): ImageProviderRegistry {
            $registry = new ImageProviderRegistry;

            $this->registerDefaultProviders($registry);

            return $registry;
        });

        $this->app->singleton(ImageManagerFactory::class);

        $this->app->singleton(ImageManager::class, fn ($app): ImageManager => $app->make(ImageManagerFactory::class)->make());

        $this->app->singleton(ImagePreloadPublisher::class, function ($app): ImagePreloadPublisher {
            $class = config('image.preload.publisher', StackImagePreloadPublisher::class);

            if ($class === StackImagePreloadPublisher::class) {
                return new StackImagePreloadPublisher(
                    view: $app->make('view'),
                    stack: config('image.preload.stack', 'head'),
                );
            }

            return $app->make($class);
        });

        $this->app->singleton(ImageScreens::class, function (): ImageScreens {
            /** @var array<string, int> $screens */
            $screens = config('image.screens', []);

            return new ImageScreens($screens);
        });

        $this->app->singleton(ImagePreloader::class);

        $this->app->scoped(ImageSourceCollector::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/image.php' => config_path('image.php'),
            ], ['laravel-image', 'config', 'laravel-image-config']);
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'image');

        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'image');
    }

    private function registerDefaultProviders(ImageProviderRegistry $registry): void
    {
        $registry->register(BunnyImageProvider::class, fn (ProviderSettings $settings): BunnyImageProvider => new BunnyImageProvider(
            baseUrl: $settings->requireString('base_url'),
        ));

        $registry->register(GlideImageProvider::class, fn (ProviderSettings $settings): GlideImageProvider => new GlideImageProvider(
            basePath: $settings->requireString('base_path'),
            signKey: $settings->requireString('sign_key'),
            appUrl: $settings->optionalString('app_url'),
        ));

        $registry->register(ImgproxyImageProvider::class, fn (ProviderSettings $settings): ImgproxyImageProvider => new ImgproxyImageProvider(
            baseUrl: $settings->requireString('base_url'),
            key: $settings->requireString('key'),
            salt: $settings->requireString('salt'),
            bucket: $settings->string('bucket', 'public'),
        ));
    }
}
