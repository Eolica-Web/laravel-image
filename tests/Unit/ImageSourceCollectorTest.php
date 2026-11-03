<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit;

use Eolica\LaravelImage\ImageScreens;
use Eolica\LaravelImage\ImageSourceCollector;
use Eolica\LaravelImage\ImageSources;
use Eolica\LaravelImage\ResolvedImage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ImageSourceCollectorTest extends TestCase
{
    private ImageSourceCollector $collector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->collector = new ImageSourceCollector(new ImageScreens([
            'sm' => 640,
            'md' => 768,
            'lg' => 1024,
        ]));
    }

    #[Test]
    public function it_pulls_the_sources_in_order_with_the_smallest_screen(): void
    {
        $lg = new ResolvedImage('lg.webp');
        $sm = new ResolvedImage('sm.webp');
        $md = new ResolvedImage('md.webp');

        $this->collector->add('lg', $lg);
        $this->collector->add('sm', $sm);
        $this->collector->add('md', $md);

        $this->assertEquals(new ImageSources([
            ['screen' => 'lg', 'image' => $lg],
            ['screen' => 'sm', 'image' => $sm],
            ['screen' => 'md', 'image' => $md],
        ], 'sm'), $this->collector->pull());
    }

    #[Test]
    public function it_pulls_no_sources_when_none_were_added(): void
    {
        $this->assertEquals(new ImageSources(), $this->collector->pull());
    }

    #[Test]
    public function it_forgets_the_sources_once_pulled(): void
    {
        $this->collector->add('lg', new ResolvedImage('lg.webp'));
        $this->collector->pull();

        $this->assertEquals(new ImageSources(), $this->collector->pull());
    }
}
