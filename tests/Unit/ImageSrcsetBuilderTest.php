<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit;

use Closure;
use Eolica\LaravelImage\ImageDensities;
use Eolica\LaravelImage\ImageModifiers;
use Eolica\LaravelImage\ImageScreens;
use Eolica\LaravelImage\ImageSizes;
use Eolica\LaravelImage\ImageSrcsetBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ImageSrcsetBuilderTest extends TestCase
{
    private ImageSrcsetBuilder $builder;

    /** @var Closure(ImageModifiers): string */
    private Closure $urlBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new ImageSrcsetBuilder(
            new ImageScreens([
                'sm' => 640,
                'md' => 768,
                'lg' => 1024,
                'xl' => 1280,
            ]),
            ImageDensities::fromList([1, 2]),
        );

        $this->urlBuilder = fn (ImageModifiers $modifiers): string => 'img?'.http_build_query($modifiers->toArray());
    }

    #[Test]
    public function it_returns_null_without_sizes_or_densities(): void
    {
        $this->assertNull($this->builder->srcset(new ImageModifiers(width: 800), null, null, null, null, $this->urlBuilder));
        $this->assertNull($this->builder->sizes(null));
    }

    #[Test]
    public function it_builds_a_width_srcset_from_sizes_keeping_the_aspect_ratio(): void
    {
        $srcset = $this->builder->srcset(
            new ImageModifiers(width: 800, height: 600, fit: 'cover'),
            ImageSizes::parse('100vw sm:50vw lg:400px'),
            null,
            null,
            null,
            $this->urlBuilder,
        );

        $this->assertSame(implode(', ', [
            'img?width=384&height=288&fit=cover 384w',
            'img?width=400&height=300&fit=cover 400w',
            'img?width=512&height=384&fit=cover 512w',
            'img?width=640&height=480&fit=cover 640w',
            'img?width=768&height=576&fit=cover 768w',
            'img?width=800&height=600&fit=cover 800w',
            'img?width=1024&height=768&fit=cover 1024w',
            'img?width=1280&height=960&fit=cover 1280w',
        ]), $srcset);
    }

    #[Test]
    public function it_builds_the_sizes_attribute_from_the_largest_screen(): void
    {
        $this->assertSame(
            '(min-width: 1024px) 400px, (min-width: 640px) 50vw, 100vw',
            $this->builder->sizes(ImageSizes::parse('lg:400px, 100vw, sm:50vw')),
        );
    }

    #[Test]
    public function it_falls_back_to_full_width_below_the_smallest_size(): void
    {
        $this->assertSame('(min-width: 768px) 50vw, 100vw', $this->builder->sizes(ImageSizes::parse('md:50vw')));
        $this->assertSame([512, 640, 768], $this->candidateWidths('md:50vw'));
    }

    #[Test]
    public function it_computes_fluid_widths_at_each_screen_inside_their_range(): void
    {
        $this->assertSame([384, 400, 512, 640], $this->candidateWidths('100vw sm:50vw lg:400px'));
    }

    #[Test]
    public function it_uses_every_larger_screen_for_an_open_ended_fluid_size(): void
    {
        $this->assertSame([640, 768, 1024, 1280], $this->candidateWidths('100vw'));
    }

    #[Test]
    public function it_uses_the_starting_screen_when_no_larger_screen_exists(): void
    {
        $this->assertSame([320, 640, 768, 1024, 1280], $this->candidateWidths('100vw xl:25vw'));
    }

    #[Test]
    public function it_only_builds_candidates_from_the_given_screen_up(): void
    {
        $this->assertSame([640], $this->candidateWidths('100vw lg:50vw', screen: 'lg'));
    }

    #[Test]
    public function it_only_builds_candidates_below_the_until_screen(): void
    {
        $this->assertSame([640, 768], $this->candidateWidths('100vw', untilScreen: 'md'));
        $this->assertSame([512, 640, 768], $this->candidateWidths('100vw md:50vw', untilScreen: 'lg'));
    }

    #[Test]
    public function it_builds_candidates_between_the_screen_and_the_until_screen(): void
    {
        $this->assertSame([1024], $this->candidateWidths('100vw', screen: 'md', untilScreen: 'lg'));
    }

    #[Test]
    public function it_caps_width_candidates_at_the_original_width(): void
    {
        $srcset = $this->builder->srcset(
            new ImageModifiers(width: 800, height: 600, fit: 'cover'),
            ImageSizes::parse('100vw'),
            null,
            null,
            null,
            $this->urlBuilder,
            originalWidth: 1000,
        );

        $this->assertSame(implode(', ', [
            'img?width=640&height=480&fit=cover 640w',
            'img?width=768&height=576&fit=cover 768w',
            'img?width=1000&height=750&fit=cover 1000w',
        ]), $srcset);
    }

    #[Test]
    public function it_caps_cover_candidates_at_the_width_the_original_height_allows(): void
    {
        $srcset = $this->builder->srcset(
            new ImageModifiers(width: 400, height: 500, fit: 'cover'),
            ImageSizes::parse('400px'),
            null,
            null,
            null,
            $this->urlBuilder,
            originalWidth: 960,
            originalHeight: 720,
        );

        $this->assertSame(
            'img?width=400&height=500&fit=cover 400w, img?width=576&height=720&fit=cover 576w',
            $srcset,
        );
    }

    #[Test]
    public function it_ignores_the_original_height_without_cover(): void
    {
        $srcset = $this->builder->srcset(
            new ImageModifiers(width: 400, height: 500),
            ImageSizes::parse('400px'),
            null,
            null,
            null,
            $this->urlBuilder,
            originalWidth: 960,
            originalHeight: 720,
        );

        $this->assertSame('img?width=400&height=500 400w, img?width=800&height=1000 800w', $srcset);
    }

    #[Test]
    public function it_uses_the_given_densities_with_sizes(): void
    {
        $srcset = $this->builder->srcset(
            new ImageModifiers(width: 400),
            ImageSizes::parse('400px'),
            ImageDensities::parse('1x 3x'),
            null,
            null,
            $this->urlBuilder,
        );

        $this->assertSame('img?width=400 400w, img?width=1200 1200w', $srcset);
    }

    #[Test]
    public function it_leaves_the_height_out_of_width_candidates_without_an_aspect_ratio(): void
    {
        $srcset = $this->builder->srcset(
            new ImageModifiers(height: 600),
            ImageSizes::parse('400px'),
            ImageDensities::parse('1x'),
            null,
            null,
            $this->urlBuilder,
        );

        $this->assertSame('img?width=400 400w', $srcset);
    }

    #[Test]
    public function it_throws_for_a_size_with_an_unknown_screen(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown image screen [huge].');

        $this->builder->sizes(ImageSizes::parse('huge:50vw'));
    }

    #[Test]
    public function it_throws_for_a_fluid_size_without_screens(): void
    {
        $builder = new ImageSrcsetBuilder(new ImageScreens([]), ImageDensities::fromList([1]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fluid image sizes require at least one configured screen.');

        $builder->srcset(new ImageModifiers(), ImageSizes::parse('100vw'), null, null, null, $this->urlBuilder);
    }

    #[Test]
    public function it_builds_a_density_srcset_without_sizes(): void
    {
        $srcset = $this->builder->srcset(
            new ImageModifiers(width: 64, height: 64, fit: 'cover'),
            null,
            ImageDensities::parse('1x 2x'),
            null,
            null,
            $this->urlBuilder,
        );

        $this->assertSame(
            'img?width=64&height=64&fit=cover 1x, img?width=128&height=128&fit=cover 2x',
            $srcset,
        );
    }

    #[Test]
    public function it_caps_densities_at_the_original_width(): void
    {
        $srcset = $this->builder->srcset(
            new ImageModifiers(width: 600, height: 450, fit: 'cover'),
            null,
            ImageDensities::parse('1x 2x 3x'),
            null,
            null,
            $this->urlBuilder,
            originalWidth: 960,
        );

        $this->assertSame(
            'img?width=600&height=450&fit=cover 1x, img?width=960&height=720&fit=cover 1.6x',
            $srcset,
        );
    }

    #[Test]
    public function it_keeps_the_first_density_when_the_original_is_smaller(): void
    {
        $srcset = $this->builder->srcset(
            new ImageModifiers(width: 1200),
            null,
            ImageDensities::parse('1x 2x'),
            null,
            null,
            $this->urlBuilder,
            originalWidth: 960,
        );

        $this->assertSame('img?width=1200 1x', $srcset);
    }

    #[Test]
    public function it_caps_height_only_densities_at_the_original_height(): void
    {
        $srcset = $this->builder->srcset(
            new ImageModifiers(height: 40),
            null,
            ImageDensities::parse('1x 2x'),
            null,
            null,
            $this->urlBuilder,
            originalHeight: 60,
        );

        $this->assertSame('img?height=40 1x, img?height=60 1.5x', $srcset);
    }

    #[Test]
    public function it_scales_a_height_only_image_by_density(): void
    {
        $srcset = $this->builder->srcset(
            new ImageModifiers(height: 40),
            null,
            ImageDensities::parse('1x 1.5x'),
            null,
            null,
            $this->urlBuilder,
        );

        $this->assertSame('img?height=40 1x, img?height=60 1.5x', $srcset);
    }

    #[Test]
    public function it_throws_for_densities_without_dimensions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Image densities require a width or a height.');

        $this->builder->srcset(new ImageModifiers(), null, ImageDensities::parse('1x 2x'), null, null, $this->urlBuilder);
    }

    /**
     * @return list<int>
     */
    private function candidateWidths(string $sizes, ?string $screen = null, ?string $untilScreen = null): array
    {
        $srcset = $this->builder->srcset(
            new ImageModifiers(),
            ImageSizes::parse($sizes),
            ImageDensities::parse('1x'),
            $screen,
            $untilScreen,
            $this->urlBuilder,
        );

        preg_match_all('/ (\d+)w(?:,|$)/', (string) $srcset, $matches);

        return array_map(intval(...), $matches[1]);
    }
}
