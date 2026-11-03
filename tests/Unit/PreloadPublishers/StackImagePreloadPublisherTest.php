<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit\PreloadPublishers;

use Eolica\LaravelImage\ImagePreload;
use Eolica\LaravelImage\PreloadPublishers\StackImagePreloadPublisher;
use Eolica\LaravelImage\Tests\Fixtures\StubImageProvider;
use Eolica\LaravelImage\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;

final class StackImagePreloadPublisherTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('image.preload.publisher', StackImagePreloadPublisher::class);
        $app['config']->set('image.preload.stack', 'head');
    }

    #[Test]
    public function it_pushes_a_preload_link_onto_the_configured_stack(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-image::image
                src="images/web/hero.webp"
                width="640"
                height="320"
                fit="cover"
                format="webp"
                :preload="true"
            />

            @stack('head')
        BLADE);

        $this->assertStringContainsString(
            '<link rel="preload" as="image" href="'.StubImageProvider::BASE_URL.'/images/web/hero.webp?w=640&amp;h=320&amp;fit=cover&amp;format=webp" />',
            $html,
        );
    }

    #[Test]
    public function it_pushes_onto_a_custom_stack_name(): void
    {
        $this->app['config']->set('image.preload.stack', 'meta');

        $this->app->forgetInstance(\Eolica\LaravelImage\ImagePreloadPublisher::class);

        $html = Blade::render(<<<'BLADE'
            <x-image::image
                src="images/web/hero.webp"
                width="640"
                height="320"
                :preload="true"
            />

            @stack('meta')
        BLADE);

        $this->assertStringContainsString(
            '<link rel="preload" as="image" href="'.StubImageProvider::BASE_URL.'/images/web/hero.webp?w=640&amp;h=320" />',
            $html,
        );
    }

    #[Test]
    public function it_pushes_the_srcset_sizes_and_media_attributes(): void
    {
        $this->app->make(\Eolica\LaravelImage\ImagePreloadPublisher::class)->preload(new ImagePreload(
            url: 'https://cdn.test/a.webp?w=400&h=300',
            srcset: 'https://cdn.test/a.webp?w=400&h=300 400w, https://cdn.test/a.webp?w=800&h=600 800w',
            sizes: '100vw',
            media: '(max-width: 767.98px)',
        ));

        $this->assertSame(
            '<link rel="preload" as="image" href="https://cdn.test/a.webp?w=400&amp;h=300"'
                .' imagesrcset="https://cdn.test/a.webp?w=400&amp;h=300 400w, https://cdn.test/a.webp?w=800&amp;h=600 800w"'
                .' imagesizes="100vw" media="(max-width: 767.98px)" />',
            trim(Blade::render("@stack('head')")),
        );
    }
}
