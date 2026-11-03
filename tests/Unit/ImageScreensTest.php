<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit;

use Eolica\LaravelImage\ImageScreens;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ImageScreensTest extends TestCase
{
    private ImageScreens $screens;

    protected function setUp(): void
    {
        parent::setUp();

        $this->screens = new ImageScreens([
            'xs' => 320,
            'sm' => 640,
            'md' => 768,
            'lg' => 1024,
            'xl' => 1280,
            '2xl' => 1536,
        ]);
    }

    #[Test]
    public function it_builds_a_media_query_for_a_known_screen(): void
    {
        $this->assertSame('(min-width: 768px)', $this->screens->mediaQuery('md'));
    }

    #[Test]
    public function it_returns_the_width_of_a_screen(): void
    {
        $this->assertSame(1024, $this->screens->width('lg'));
    }

    #[Test]
    public function it_lists_screen_widths_smallest_first(): void
    {
        $screens = new ImageScreens(['lg' => 1024, 'sm' => 640, 'md' => 768]);

        $this->assertSame([640, 768, 1024], $screens->widths());
    }

    #[Test]
    public function it_throws_for_an_unknown_screen(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->screens->mediaQuery('unknown');
    }
}
