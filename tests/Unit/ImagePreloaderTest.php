<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit;

use Eolica\LaravelImage\ImagePreload;
use Eolica\LaravelImage\ImagePreloader;
use Eolica\LaravelImage\ImageScreens;
use Eolica\LaravelImage\ImageSources;
use Eolica\LaravelImage\ResolvedImage;
use Eolica\LaravelImage\Tests\Fixtures\CollectingImagePreloadPublisher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ImagePreloaderTest extends TestCase
{
    private CollectingImagePreloadPublisher $publisher;

    private ImagePreloader $preloader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publisher = new CollectingImagePreloadPublisher();

        $this->preloader = new ImagePreloader($this->publisher, new ImageScreens([
            'sm' => 640,
            'md' => 768,
            'lg' => 1024,
        ]));
    }

    #[Test]
    public function it_preloads_an_image_with_its_srcset_and_sizes(): void
    {
        $this->preloader->image(new ResolvedImage('a.webp', srcset: 'a.webp 400w, b.webp 800w', sizes: '400px'));

        $this->assertEquals([
            new ImagePreload('a.webp', srcset: 'a.webp 400w, b.webp 800w', sizes: '400px'),
        ], $this->publisher->preloads);
    }

    #[Test]
    public function it_preloads_a_picture_without_sources_without_media(): void
    {
        $this->preloader->picture(new ResolvedImage('fallback.webp'), new ImageSources());

        $this->assertEquals([new ImagePreload('fallback.webp')], $this->publisher->preloads);
    }

    #[Test]
    public function it_preloads_each_source_with_a_media_range_that_does_not_overlap(): void
    {
        $this->preloader->picture(new ResolvedImage('fallback.webp'), new ImageSources([
            ['screen' => 'lg', 'image' => new ResolvedImage('lg.webp')],
            ['screen' => 'sm', 'image' => new ResolvedImage('sm.webp')],
        ], 'sm'));

        $this->assertEquals([
            new ImagePreload('lg.webp', media: '(min-width: 1024px)'),
            new ImagePreload('sm.webp', media: '(min-width: 640px) and (max-width: 1023.98px)'),
            new ImagePreload('fallback.webp', media: '(max-width: 639.98px)'),
        ], $this->publisher->preloads);
    }

    #[Test]
    public function it_skips_a_source_that_an_earlier_source_always_wins_over(): void
    {
        $this->preloader->picture(new ResolvedImage('fallback.webp'), new ImageSources([
            ['screen' => 'sm', 'image' => new ResolvedImage('sm.webp')],
            ['screen' => 'lg', 'image' => new ResolvedImage('lg.webp')],
        ], 'sm'));

        $this->assertEquals([
            new ImagePreload('sm.webp', media: '(min-width: 640px)'),
            new ImagePreload('fallback.webp', media: '(max-width: 639.98px)'),
        ], $this->publisher->preloads);
    }
}
