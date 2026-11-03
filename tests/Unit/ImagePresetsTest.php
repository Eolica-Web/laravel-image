<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit;

use Eolica\LaravelImage\ImagePresets;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ImagePresetsTest extends TestCase
{
    private ImagePresets $presets;

    protected function setUp(): void
    {
        parent::setUp();

        $this->presets = new ImagePresets([
            'og' => [
                'width' => 1200,
                'height' => 630,
                'fit' => 'cover',
                'format' => 'webp',
                'quality' => 80,
            ],
            'card' => [
                'width' => 400,
                'height' => 300,
                'fit' => 'cover',
                'format' => 'webp',
            ],
        ]);
    }

    #[Test]
    public function it_resolves_a_known_preset_to_image_modifiers(): void
    {
        $modifiers = $this->presets->resolve('og');

        $this->assertSame(1200, $modifiers->width);
        $this->assertSame(630, $modifiers->height);
        $this->assertSame('cover', $modifiers->fit);
        $this->assertSame('webp', $modifiers->format);
        $this->assertSame(80, $modifiers->quality);
    }

    #[Test]
    public function it_throws_for_an_unknown_preset(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown image preset [unknown].');

        $this->presets->resolve('unknown');
    }
}
