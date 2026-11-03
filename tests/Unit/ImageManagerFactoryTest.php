<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit;

use Eolica\LaravelImage\ImageManager;
use Eolica\LaravelImage\ImageManagerFactory;
use Eolica\LaravelImage\ImageModifiers;
use Eolica\LaravelImage\ImageProviderRegistry;
use Eolica\LaravelImage\ProviderSettings;
use Eolica\LaravelImage\Providers\BunnyImageProvider;
use Eolica\LaravelImage\Tests\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;

final class ImageManagerFactoryTest extends TestCase
{
    #[Test]
    public function it_builds_an_image_manager_from_config(): void
    {
        $this->app['config']->set('image.provider', 'bunny');
        $this->app['config']->set('image.providers.bunny', [
            'driver' => BunnyImageProvider::class,
            'base_url' => 'https://cdn.example.com',
            'defaults' => [
                'quality' => 80,
            ],
        ]);

        $factory = $this->app->make(ImageManagerFactory::class);

        $manager = $factory->make();

        $this->assertInstanceOf(ImageManager::class, $manager);

        $url = $manager->url(
            'images/web/hero.webp',
            ImageModifiers::fromProps(width: 640),
        );

        $this->assertStringStartsWith('https://cdn.example.com/images/web/hero.webp?', $url);
        $this->assertStringContainsString('width=640', $url);
        $this->assertStringContainsString('quality=80', $url);
    }

    #[Test]
    public function it_resolves_image_manager_from_the_container(): void
    {
        $manager = $this->app->make(ImageManager::class);

        $this->assertInstanceOf(ImageManager::class, $manager);

        $this->assertSame(
            '(min-width: 768px)',
            $manager->mediaQuery('md'),
        );
    }

    #[Test]
    public function it_uses_the_configured_densities_for_sizes(): void
    {
        $this->app['config']->set('image.densities', [1, 3]);

        $manager = $this->app->make(ImageManagerFactory::class)->make();

        $resolved = $manager->resolve('hero.webp', ImageModifiers::fromProps(width: 400), sizes: '400px');

        $this->assertStringEndsWith('w=1200 1200w', (string) $resolved->srcset);
        $this->assertStringContainsString('w=400 400w', (string) $resolved->srcset);
    }

    #[Test]
    public function it_throws_for_invalid_configured_densities(): void
    {
        $this->app['config']->set('image.densities', [0]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid image density [0].');

        $this->app->make(ImageManagerFactory::class)->make();
    }

    #[Test]
    public function it_throws_when_a_required_provider_setting_is_missing(): void
    {
        $this->app['config']->set('image.providers.bunny', [
            'driver' => BunnyImageProvider::class,
            'defaults' => [
                'quality' => 80,
            ],
        ]);

        $factory = new ImageManagerFactory(
            $this->app->make('config'),
            $this->app->make(ImageProviderRegistry::class),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Image provider setting [base_url] is required.');

        $factory->make();
    }

    #[Test]
    public function it_resolves_custom_providers_registered_on_the_registry(): void
    {
        $this->app->afterResolving(ImageProviderRegistry::class, function (ImageProviderRegistry $registry): void {
            $registry->register(CustomImageProvider::class, fn (ProviderSettings $settings): CustomImageProvider => new CustomImageProvider(
                baseUrl: $settings->requireString('base_url'),
            ));
        });

        $this->app['config']->set('image.provider', 'custom');
        $this->app['config']->set('image.providers.custom', [
            'driver' => CustomImageProvider::class,
            'base_url' => 'https://custom.example.com',
            'defaults' => [],
        ]);

        $factory = $this->app->make(ImageManagerFactory::class);
        $manager = $factory->make();

        $url = $manager->url('images/hero.webp', ImageModifiers::fromProps(width: 640));

        $this->assertSame('https://custom.example.com/images/hero.webp', $url);
    }

    #[Test]
    public function it_strips_driver_and_defaults_from_provider_settings(): void
    {
        $receivedSettings = null;

        $this->app->afterResolving(ImageProviderRegistry::class, function (ImageProviderRegistry $registry) use (&$receivedSettings): void {
            $registry->register(SettingsCapturingImageProvider::class, function (ProviderSettings $settings) use (&$receivedSettings): SettingsCapturingImageProvider {
                $receivedSettings = $settings;

                if ($settings->optionalString('driver') !== null) {
                    throw new InvalidArgumentException('driver should not be passed to ProviderSettings.');
                }

                return new SettingsCapturingImageProvider(
                    baseUrl: $settings->requireString('base_url'),
                );
            });
        });

        $this->app['config']->set('image.provider', 'custom');
        $this->app['config']->set('image.providers.custom', [
            'driver' => SettingsCapturingImageProvider::class,
            'base_url' => 'https://custom.example.com',
            'defaults' => [
                'quality' => 80,
            ],
        ]);

        $factory = $this->app->make(ImageManagerFactory::class);
        $manager = $factory->make();

        $this->assertInstanceOf(ProviderSettings::class, $receivedSettings);

        $reflection = new \ReflectionClass($receivedSettings);
        $values = $reflection->getProperty('values')->getValue($receivedSettings);

        $this->assertIsArray($values);
        $this->assertArrayNotHasKey('driver', $values);
        $this->assertArrayNotHasKey('defaults', $values);
        $this->assertSame(['base_url' => 'https://custom.example.com'], $values);

        $url = $manager->url('images/hero.webp', ImageModifiers::fromProps(width: 640));

        $this->assertStringStartsWith('https://custom.example.com/images/hero.webp?', $url);
        $this->assertStringContainsString('width=640', $url);
        $this->assertStringContainsString('quality=80', $url);
    }
}

final class CustomImageProvider implements \Eolica\LaravelImage\ImageProvider
{
    public function __construct(private readonly string $baseUrl) {}

    public function url(string $path, ImageModifiers $modifiers): string
    {
        return $this->baseUrl.'/'.$path;
    }
}

final class SettingsCapturingImageProvider implements \Eolica\LaravelImage\ImageProvider
{
    public function __construct(private readonly string $baseUrl) {}

    public function url(string $path, ImageModifiers $modifiers): string
    {
        $query = http_build_query(array_filter([
            'width' => $modifiers->width,
            'quality' => $modifiers->quality,
        ], fn (?int $value): bool => $value !== null));

        return $this->baseUrl.'/'.$path.($query !== '' ? '?'.$query : '');
    }
}
