<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit;

use Eolica\LaravelImage\ImageDensities;
use Eolica\LaravelImage\ImageManager;
use Eolica\LaravelImage\ImageModifiers;
use Eolica\LaravelImage\ImagePresets;
use Eolica\LaravelImage\ImageScreens;
use Eolica\LaravelImage\ImageSrcsetBuilder;
use Eolica\LaravelImage\Providers\BunnyImageProvider;
use Eolica\LaravelImage\Providers\GlideImageProvider;
use Eolica\LaravelImage\ResolvedImage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ImageManagerTest extends TestCase
{
    private ImageManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new ImageManager(
            defaultProvider: 'bunny',
            providers: [
                'bunny' => new BunnyImageProvider(
                    baseUrl: 'https://cdn.example.com',
                ),
                'glide' => new GlideImageProvider(
                    basePath: '/images',
                    signKey: 'test-sign-key',
                    appUrl: 'https://app.example.com',
                ),
            ],
            providerDefaults: [
                'bunny' => [
                    'quality' => 80,
                ],
                'glide' => [],
            ],
            screens: new ImageScreens([
                'md' => 768,
            ]),
            presets: new ImagePresets([
                'og' => [
                    'width' => 1200,
                    'height' => 630,
                    'fit' => 'cover',
                    'format' => 'webp',
                    'quality' => 80,
                ],
            ]),
            srcsetBuilder: new ImageSrcsetBuilder(new ImageScreens([]), ImageDensities::fromList([1])),
        );
    }

    #[Test]
    public function it_normalizes_src_by_stripping_leading_slashes(): void
    {
        $url = $this->manager->url(
            '/images/web/hero.webp',
            ImageModifiers::fromProps(width: 640),
        );

        $this->assertStringStartsWith('https://cdn.example.com/images/web/hero.webp?', $url);
        $this->assertStringContainsString('width=640', $url);
    }

    #[Test]
    public function it_merges_provider_defaults_with_modifiers(): void
    {
        $url = $this->manager->url(
            'images/web/hero.webp',
            ImageModifiers::fromProps(width: 640),
        );

        $this->assertStringContainsString('quality=80', $url);
    }

    #[Test]
    public function it_resolves_an_explicit_provider_override(): void
    {
        $url = $this->manager->url(
            'images/web/hero.webp',
            ImageModifiers::fromProps(width: 640),
            'glide',
        );

        $this->assertStringStartsWith('https://app.example.com/images/', $url);
        $this->assertStringContainsString('w=640', $url);
    }

    #[Test]
    public function it_returns_a_resolved_image_dto_from_resolve(): void
    {
        $resolved = $this->manager->resolve(
            'images/web/hero.webp',
            ImageModifiers::fromProps(width: 640, height: 320, format: 'webp'),
        );

        $this->assertInstanceOf(ResolvedImage::class, $resolved);
        $this->assertStringStartsWith('https://cdn.example.com/images/web/hero.webp?', $resolved->url);
        $this->assertStringContainsString('width=640', $resolved->url);
        $this->assertStringContainsString('quality=80', $resolved->url);
        $this->assertSame(640, $resolved->width);
        $this->assertSame(320, $resolved->height);
        $this->assertSame('webp', $resolved->format);
    }

    #[Test]
    public function it_applies_preset_modifiers_and_allows_explicit_overrides(): void
    {
        $resolved = $this->manager->resolve(
            'images/web/hero.webp',
            ImageModifiers::fromProps(width: 800),
            preset: 'og',
        );

        $this->assertStringContainsString('width=800', $resolved->url);
        $this->assertStringContainsString('height=630', $resolved->url);
        $this->assertStringContainsString('crop=true', $resolved->url);
        $this->assertSame(800, $resolved->width);
        $this->assertSame(630, $resolved->height);
        $this->assertSame('webp', $resolved->format);
    }

    #[Test]
    public function it_excludes_provider_defaults_from_display_attributes(): void
    {
        $resolved = $this->manager->resolve(
            'images/web/hero.webp',
            ImageModifiers::fromProps(width: 640),
        );

        $this->assertStringContainsString('quality=80', $resolved->url);
        $this->assertNull($resolved->format);
    }

    #[Test]
    public function it_wraps_resolve_in_url(): void
    {
        $url = $this->manager->url(
            'images/web/hero.webp',
            ImageModifiers::fromProps(width: 640),
            preset: 'og',
        );

        $this->assertSame(
            $this->manager->resolve(
                'images/web/hero.webp',
                ImageModifiers::fromProps(width: 640),
                preset: 'og',
            )->url,
            $url,
        );
    }
}
