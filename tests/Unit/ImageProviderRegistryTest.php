<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit;

use Eolica\LaravelImage\ImageProvider;
use Eolica\LaravelImage\ImageProviderRegistry;
use Eolica\LaravelImage\ProviderSettings;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ImageProviderRegistryTest extends TestCase
{
    #[Test]
    public function it_resolves_a_registered_provider_with_provider_settings(): void
    {
        $registry = new ImageProviderRegistry;

        $registry->register('test-driver', function (ProviderSettings $settings): ImageProvider {
            $this->assertSame('https://cdn.example.com', $settings->requireString('base_url'));

            return new TestImageProvider($settings->requireString('base_url'));
        });

        $provider = $registry->resolve('test-driver', [
            'base_url' => 'https://cdn.example.com',
        ]);

        $this->assertInstanceOf(TestImageProvider::class, $provider);
        $this->assertSame('https://cdn.example.com', $provider->baseUrl);
    }

    #[Test]
    public function it_throws_for_an_unknown_driver(): void
    {
        $registry = new ImageProviderRegistry;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown image provider driver [missing-driver].');

        $registry->resolve('missing-driver', []);
    }

    #[Test]
    public function it_throws_when_registering_a_duplicate_driver(): void
    {
        $registry = new ImageProviderRegistry;

        $registry->register('test-driver', fn (ProviderSettings $settings): ImageProvider => new TestImageProvider(
            $settings->requireString('base_url'),
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Image provider driver [test-driver] is already registered.');

        $registry->register('test-driver', fn (ProviderSettings $settings): ImageProvider => new TestImageProvider(
            $settings->requireString('base_url'),
        ));
    }
}

final class TestImageProvider implements ImageProvider
{
    public function __construct(public readonly string $baseUrl) {}

    public function url(string $path, \Eolica\LaravelImage\ImageModifiers $modifiers): string
    {
        return $this->baseUrl.'/'.$path;
    }
}
