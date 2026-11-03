<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Feature;

use Eolica\LaravelImage\ImagePreload;
use Eolica\LaravelImage\Tests\Fixtures\CollectingImagePreloadPublisher;
use Eolica\LaravelImage\Tests\Fixtures\StubImageProvider;
use Eolica\LaravelImage\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

final class ImageComponentTest extends TestCase
{
    #[Test]
    public function it_renders_a_standalone_img_with_resolved_url_and_dimensions(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-image::image
                src="images/web/hero.webp"
                width="640"
                height="320"
                fit="cover"
                format="webp"
                alt="Hero"
                class="w-full"
            />
        BLADE);

        $this->assertStringNotContainsString('<picture>', $html);
        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString(
            'src="'.StubImageProvider::BASE_URL.'/images/web/hero.webp?w=640&amp;h=320&amp;fit=cover&amp;format=webp"',
            $html,
        );
        $this->assertStringContainsString('width="640"', $html);
        $this->assertStringContainsString('height="320"', $html);
        $this->assertStringContainsString('alt="Hero"', $html);
        $this->assertStringContainsString('class="w-full"', $html);
    }

    #[Test]
    public function it_pushes_a_preload_link_when_preload_is_enabled(): void
    {
        Blade::render(<<<'BLADE'
            <x-image::image
                src="images/web/hero.webp"
                width="640"
                height="320"
                fit="cover"
                format="webp"
                :preload="true"
            />
        BLADE);

        $publisher = $this->app->make(CollectingImagePreloadPublisher::class);

        $this->assertEquals([
            new ImagePreload(StubImageProvider::BASE_URL.'/images/web/hero.webp?w=640&h=320&fit=cover&format=webp'),
        ], $publisher->preloads);
    }

    #[Test]
    public function it_does_not_push_a_preload_link_by_default(): void
    {
        Blade::render(<<<'BLADE'
            <x-image::image
                src="images/web/hero.webp"
                width="640"
                height="320"
            />
        BLADE);

        $publisher = $this->app->make(CollectingImagePreloadPublisher::class);

        $this->assertSame([], $publisher->preloads);
    }

    #[Test]
    public function it_renders_with_a_preset_and_uses_display_dimensions(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-image::image
                src="images/web/hero.webp"
                preset="og"
                alt="Open Graph"
            />
        BLADE);

        $this->assertStringContainsString(
            'src="'.StubImageProvider::BASE_URL.'/images/web/hero.webp?w=1200&amp;h=630&amp;fit=cover&amp;format=webp&amp;q=80"',
            $html,
        );
        $this->assertStringContainsString('width="1200"', $html);
        $this->assertStringContainsString('height="630"', $html);
        $this->assertStringContainsString('alt="Open Graph"', $html);
    }

    #[Test]
    public function it_renders_a_width_srcset_and_sizes_from_sizes(): void
    {
        $this->app['config']->set('image.screens', ['sm' => 640, 'lg' => 1024]);

        $html = Blade::render(<<<'BLADE'
            <x-image::image
                src="images/web/hero.webp"
                width="800"
                height="600"
                sizes="100vw lg:400px"
                densities="1x"
                alt="Hero"
            />
        BLADE);

        $url = StubImageProvider::BASE_URL.'/images/web/hero.webp';

        $this->assertStringContainsString('src="'.$url.'?w=800&amp;h=600"', $html);
        $this->assertStringContainsString(
            'srcset="'.$url.'?w=400&amp;h=300 400w, '.$url.'?w=640&amp;h=480 640w, '.$url.'?w=1024&amp;h=768 1024w"',
            $html,
        );
        $this->assertStringContainsString('sizes="(min-width: 1024px) 400px, 100vw"', $html);
        $this->assertStringContainsString('width="800"', $html);
    }

    #[Test]
    public function it_renders_a_density_srcset_from_densities(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-image::image src="images/web/avatar.webp" width="64" height="64" densities="1x 2x" />
        BLADE);

        $url = StubImageProvider::BASE_URL.'/images/web/avatar.webp';

        $this->assertStringContainsString(
            'srcset="'.$url.'?w=64&amp;h=64 1x, '.$url.'?w=128&amp;h=128 2x"',
            $html,
        );
        $this->assertStringNotContainsString('sizes=', $html);
    }

    #[Test]
    public function it_does_not_render_srcset_or_sizes_by_default(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-image::image src="images/web/hero.webp" width="640" height="320" />
        BLADE);

        $this->assertStringNotContainsString('srcset=', $html);
        $this->assertStringNotContainsString('sizes=', $html);
    }

    #[Test]
    public function it_ignores_a_srcset_attribute(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-image::image src="images/web/hero.webp" width="64" height="64" srcset="custom.webp 1x" />
        BLADE);

        $this->assertStringNotContainsString('custom.webp', $html);
        $this->assertStringNotContainsString('srcset=', $html);
    }

    #[Test]
    public function it_ignores_a_srcset_attribute_in_favour_of_the_resolved_one(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-image::image src="images/web/hero.webp" width="64" height="64" densities="1x" srcset="custom.webp 1x" />
        BLADE);

        $this->assertStringNotContainsString('custom.webp', $html);
        $this->assertStringContainsString('srcset="'.StubImageProvider::BASE_URL.'/images/web/hero.webp?w=64&amp;h=64 1x"', $html);
    }

    #[Test]
    public function it_pushes_a_preload_link_with_the_srcset_and_sizes(): void
    {
        Blade::render(<<<'BLADE'
            <x-image::image src="images/web/hero.webp" width="400" sizes="400px" densities="1x 2x" :preload="true" />
        BLADE);

        $publisher = $this->app->make(CollectingImagePreloadPublisher::class);
        $url = StubImageProvider::BASE_URL.'/images/web/hero.webp';

        $this->assertEquals([
            new ImagePreload($url.'?w=400', srcset: $url.'?w=400 400w, '.$url.'?w=800 800w', sizes: '400px'),
        ], $publisher->preloads);
    }

    #[Test]
    public function it_caps_the_srcset_at_the_original_width(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-image::image src="images/web/avatar.webp" width="64" height="64" densities="1x 2x" original-width="100" />
        BLADE);

        $url = StubImageProvider::BASE_URL.'/images/web/avatar.webp';

        $this->assertStringContainsString('srcset="'.$url.'?w=64&amp;h=64 1x, '.$url.'?w=100&amp;h=100 1.56x"', $html);
    }
}
