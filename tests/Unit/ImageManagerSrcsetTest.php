<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit;

use Eolica\LaravelImage\ImageDensities;
use Eolica\LaravelImage\ImageManager;
use Eolica\LaravelImage\ImageModifiers;
use Eolica\LaravelImage\ImagePresets;
use Eolica\LaravelImage\ImageScreens;
use Eolica\LaravelImage\ImageSrcsetBuilder;
use Eolica\LaravelImage\Tests\Fixtures\StubImageProvider;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ImageManagerSrcsetTest extends TestCase
{
    private const string URL = StubImageProvider::BASE_URL.'/hero.webp';

    private ImageManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $screens = new ImageScreens([
            'sm' => 640,
            'lg' => 1024,
            'xl' => 1280,
        ]);

        $this->manager = new ImageManager(
            defaultProvider: 'stub',
            providers: ['stub' => new StubImageProvider()],
            providerDefaults: ['stub' => ['quality' => 80]],
            screens: $screens,
            presets: new ImagePresets([
                'card' => ['width' => 400, 'height' => 300, 'fit' => 'cover'],
            ]),
            srcsetBuilder: new ImageSrcsetBuilder($screens, ImageDensities::fromList([1, 2])),
        );
    }

    #[Test]
    public function it_does_not_build_a_srcset_without_sizes_or_densities(): void
    {
        $resolved = $this->manager->resolve('hero.webp', ImageModifiers::fromProps(width: 800, height: 600));

        $this->assertNull($resolved->srcset);
        $this->assertNull($resolved->sizes);
    }

    #[Test]
    public function it_builds_srcset_candidates_with_provider_defaults(): void
    {
        $resolved = $this->manager->resolve(
            'hero.webp',
            ImageModifiers::fromProps(width: 800, height: 600),
            sizes: '100vw lg:400px',
        );

        $this->assertSame(self::URL.'?w=800&h=600&q=80', $resolved->url);
        $this->assertSame('(min-width: 1024px) 400px, 100vw', $resolved->sizes);
        $this->assertSame(implode(', ', [
            self::URL.'?w=400&h=300&q=80 400w',
            self::URL.'?w=640&h=480&q=80 640w',
            self::URL.'?w=800&h=600&q=80 800w',
            self::URL.'?w=1024&h=768&q=80 1024w',
            self::URL.'?w=1280&h=960&q=80 1280w',
            self::URL.'?w=2048&h=1536&q=80 2048w',
        ]), $resolved->srcset);
    }

    #[Test]
    public function it_applies_presets_to_srcset_candidates(): void
    {
        $resolved = $this->manager->resolve('hero.webp', preset: 'card', densities: '2x');

        $this->assertSame(self::URL.'?w=800&h=600&fit=cover&q=80 2x', $resolved->srcset);
    }

    #[Test]
    public function it_passes_the_screen_to_the_srcset_builder(): void
    {
        $resolved = $this->manager->resolve(
            'hero.webp',
            ImageModifiers::fromProps(width: 1600, height: 900),
            sizes: '100vw',
            densities: '1x',
            screen: 'lg',
        );

        $this->assertSame(self::URL.'?w=1280&h=720&q=80 1280w', $resolved->srcset);
    }

    #[Test]
    public function it_passes_the_until_screen_to_the_srcset_builder(): void
    {
        $resolved = $this->manager->resolve(
            'hero.webp',
            ImageModifiers::fromProps(width: 640, height: 800),
            sizes: '100vw',
            densities: '1x',
            untilScreen: 'lg',
        );

        $this->assertSame(self::URL.'?w=640&h=800&q=80 640w, '.self::URL.'?w=1024&h=1280&q=80 1024w', $resolved->srcset);
    }

    #[Test]
    public function it_ignores_blank_sizes_and_densities(): void
    {
        $resolved = $this->manager->resolve(
            'hero.webp',
            ImageModifiers::fromProps(width: 800),
            sizes: '',
            densities: ' ',
        );

        $this->assertNull($resolved->srcset);
        $this->assertNull($resolved->sizes);
    }

    #[Test]
    public function it_throws_for_invalid_sizes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid image size [50%].');

        $this->manager->resolve('hero.webp', sizes: '50%');
    }
}
