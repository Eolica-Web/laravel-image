<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Feature;

use Eolica\LaravelImage\ImagePreload;
use Eolica\LaravelImage\Tests\Fixtures\CollectingImagePreloadPublisher;
use Eolica\LaravelImage\Tests\Fixtures\StubImageProvider;
use Eolica\LaravelImage\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

final class PictureComponentTest extends TestCase
{
    #[Test]
    public function it_renders_a_picture_element_with_responsive_source_and_fallback_img(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-image::picture
                src="images/web/hero.webp"
                width="640"
                height="320"
                fit="cover"
                format="webp"
                alt="Hero"
            >
                <x-image::source screen="md" width="768" height="640" fit="cover" format="webp" />
            </x-image::picture>
        BLADE);

        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringContainsString('<source media="(min-width: 768px)"', $html);
        $this->assertStringContainsString(
            'srcset="'.StubImageProvider::BASE_URL.'/images/web/hero.webp?w=768&amp;h=640&amp;fit=cover&amp;format=webp"',
            $html,
        );
        $this->assertStringContainsString(
            'src="'.StubImageProvider::BASE_URL.'/images/web/hero.webp?w=640&amp;h=320&amp;fit=cover&amp;format=webp"',
            $html,
        );
        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('width="640"', $html);
        $this->assertStringContainsString('height="320"', $html);
        $this->assertStringContainsString('alt="Hero"', $html);
    }

    #[Test]
    public function it_renders_a_picture_with_a_preset_on_the_fallback_img(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-image::picture
                src="images/web/hero.webp"
                preset="og"
                alt="Hero"
            >
                <x-image::source screen="md" width="768" height="640" />
            </x-image::picture>
        BLADE);

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString(
            'src="'.StubImageProvider::BASE_URL.'/images/web/hero.webp?w=1200&amp;h=630&amp;fit=cover&amp;format=webp&amp;q=80"',
            $html,
        );
        $this->assertStringContainsString('width="1200"', $html);
        $this->assertStringContainsString('height="630"', $html);
    }

    #[Test]
    public function it_inherits_preset_fit_and_format_on_source_via_aware(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-image::picture
                src="images/web/hero.webp"
                preset="og"
                width="1280"
                height="640"
                alt="Hero"
            >
                <x-image::source screen="md" width="768" height="640" />
            </x-image::picture>
        BLADE);

        $this->assertStringContainsString('<source media="(min-width: 768px)"', $html);
        $this->assertStringContainsString(
            'srcset="'.StubImageProvider::BASE_URL.'/images/web/hero.webp?w=768&amp;h=640&amp;fit=cover&amp;format=webp&amp;q=80"',
            $html,
        );
    }

    #[Test]
    public function it_pushes_a_preload_link_for_each_source_and_the_fallback_img(): void
    {
        $this->app['config']->set('image.screens', ['md' => 768, 'lg' => 1024]);

        Blade::render(<<<'BLADE'
            <x-image::picture src="images/web/hero.webp" width="640" height="800" sizes="100vw" densities="1x" :preload="true">
                <x-image::source screen="lg" width="1600" height="900" />
                <x-image::source screen="md" width="1024" height="768" />
            </x-image::picture>
        BLADE);

        $publisher = $this->app->make(CollectingImagePreloadPublisher::class);
        $url = StubImageProvider::BASE_URL.'/images/web/hero.webp';

        $this->assertEquals([
            new ImagePreload($url.'?w=1600&h=900', media: '(min-width: 1024px)'),
            new ImagePreload($url.'?w=1024&h=768', media: '(min-width: 768px) and (max-width: 1023.98px)'),
            new ImagePreload(
                $url.'?w=640&h=800',
                srcset: $url.'?w=768&h=960 768w',
                sizes: '100vw',
                media: '(max-width: 767.98px)',
            ),
        ], $publisher->preloads);
    }

    #[Test]
    public function it_does_not_push_a_preload_link_by_default(): void
    {
        Blade::render(<<<'BLADE'
            <x-image::picture
                src="images/web/hero.webp"
                width="640"
                height="320"
            >
                <x-image::source screen="md" width="768" height="640" />
            </x-image::picture>
        BLADE);

        $publisher = $this->app->make(CollectingImagePreloadPublisher::class);

        $this->assertSame([], $publisher->preloads);
    }

    #[Test]
    public function it_renders_a_source_srcset_from_its_screen_up_and_the_fallback_srcset_below_it(): void
    {
        $this->app['config']->set('image.screens', ['sm' => 640, 'lg' => 1024, 'xl' => 1280]);

        $html = Blade::render(<<<'BLADE'
            <x-image::picture src="images/web/hero.webp" width="640" height="800" sizes="100vw" densities="1x" alt="Hero">
                <x-image::source screen="lg" width="1600" height="900" sizes="100vw" densities="1x" />
            </x-image::picture>
        BLADE);

        $url = StubImageProvider::BASE_URL.'/images/web/hero.webp';

        $this->assertStringContainsString(
            '<source media="(min-width: 1024px)" srcset="'.$url.'?w=1280&amp;h=720 1280w" sizes="100vw" />',
            $html,
        );
        $this->assertStringContainsString(
            'srcset="'.$url.'?w=640&amp;h=800 640w, '.$url.'?w=1024&amp;h=1280 1024w"',
            $html,
        );
    }

    #[Test]
    public function it_does_not_inherit_sizes_from_the_picture_on_a_source(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-image::picture src="images/web/hero.webp" width="640" height="320" sizes="100vw">
                <x-image::source screen="md" width="768" height="640" />
            </x-image::picture>
        BLADE);

        $this->assertStringContainsString(
            '<source media="(min-width: 768px)" srcset="'.StubImageProvider::BASE_URL.'/images/web/hero.webp?w=768&amp;h=640" />',
            $html,
        );
    }

    #[Test]
    public function it_forwards_additional_attributes_on_a_source(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-image::picture src="images/web/hero.webp" width="640" height="320">
                <x-image::source screen="md" width="768" height="640" type="image/webp" media="(orientation: portrait)" srcset="custom.webp 1x" />
            </x-image::picture>
        BLADE);

        $this->assertStringContainsString(
            '<source media="(min-width: 768px)" srcset="'.StubImageProvider::BASE_URL.'/images/web/hero.webp?w=768&amp;h=640" type="image/webp" />',
            $html,
        );
    }

    #[Test]
    public function it_caps_the_picture_and_its_sources_at_the_original_size(): void
    {
        $this->app['config']->set('image.screens', ['sm' => 640, 'lg' => 1024, 'xl' => 1280]);

        $html = Blade::render(<<<'BLADE'
            <x-image::picture src="images/web/hero.webp" width="640" height="800" sizes="100vw" densities="1x" :original-width="900">
                <x-image::source screen="lg" width="1600" height="900" sizes="100vw" densities="1x" />
            </x-image::picture>
        BLADE);

        $url = StubImageProvider::BASE_URL.'/images/web/hero.webp';

        $this->assertStringContainsString('srcset="'.$url.'?w=900&amp;h=506 900w"', $html);
        $this->assertStringContainsString(
            'srcset="'.$url.'?w=640&amp;h=800 640w, '.$url.'?w=900&amp;h=1125 900w"',
            $html,
        );
    }
}
