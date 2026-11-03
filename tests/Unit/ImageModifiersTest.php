<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit;

use Eolica\LaravelImage\ImageModifiers;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ImageModifiersTest extends TestCase
{
    #[Test]
    public function it_defaults_all_properties_to_null(): void
    {
        $modifiers = new ImageModifiers();

        $this->assertNull($modifiers->width);
        $this->assertNull($modifiers->height);
        $this->assertNull($modifiers->fit);
        $this->assertNull($modifiers->format);
        $this->assertNull($modifiers->quality);
    }

    #[Test]
    public function it_builds_from_props_with_integers(): void
    {
        $modifiers = ImageModifiers::fromProps(
            width: 640,
            height: 320,
            fit: 'cover',
            format: 'webp',
            quality: 80,
        );

        $this->assertSame(640, $modifiers->width);
        $this->assertSame(320, $modifiers->height);
        $this->assertSame('cover', $modifiers->fit);
        $this->assertSame('webp', $modifiers->format);
        $this->assertSame(80, $modifiers->quality);
    }

    #[Test]
    public function it_parses_string_dimensions_and_quality_from_props(): void
    {
        $modifiers = ImageModifiers::fromProps(
            width: '640px',
            height: '320',
            quality: '85',
        );

        $this->assertSame(640, $modifiers->width);
        $this->assertSame(320, $modifiers->height);
        $this->assertSame(85, $modifiers->quality);
    }

    #[Test]
    public function it_treats_empty_strings_as_null_from_props(): void
    {
        $modifiers = ImageModifiers::fromProps(
            width: '',
            height: '',
            quality: '',
        );

        $this->assertNull($modifiers->width);
        $this->assertNull($modifiers->height);
        $this->assertNull($modifiers->quality);
    }

    #[Test]
    public function it_builds_from_array(): void
    {
        $modifiers = ImageModifiers::fromArray([
            'width' => 1200,
            'height' => 630,
            'fit' => 'cover',
            'format' => 'webp',
            'quality' => 80,
        ]);

        $this->assertSame(1200, $modifiers->width);
        $this->assertSame(630, $modifiers->height);
        $this->assertSame('cover', $modifiers->fit);
        $this->assertSame('webp', $modifiers->format);
        $this->assertSame(80, $modifiers->quality);
    }

    #[Test]
    public function it_omits_null_values_from_array(): void
    {
        $modifiers = new ImageModifiers(width: 640);

        $this->assertSame(['width' => 640], $modifiers->toArray());
    }

    #[Test]
    public function it_returns_an_empty_array_when_all_values_are_null(): void
    {
        $this->assertSame([], (new ImageModifiers())->toArray());
    }

    #[Test]
    public function it_merges_other_modifiers_over_the_base(): void
    {
        $base = ImageModifiers::fromProps(
            width: 640,
            height: 320,
            fit: 'cover',
            format: 'webp',
            quality: 80,
        );

        $merged = $base->merge(ImageModifiers::fromProps(width: 800, format: 'jpg'));

        $this->assertSame(800, $merged->width);
        $this->assertSame(320, $merged->height);
        $this->assertSame('cover', $merged->fit);
        $this->assertSame('jpg', $merged->format);
        $this->assertSame(80, $merged->quality);
    }

    #[Test]
    public function it_keeps_base_values_when_other_properties_are_null(): void
    {
        $base = ImageModifiers::fromProps(width: 640, height: 320);
        $merged = $base->merge(new ImageModifiers());

        $this->assertSame(640, $merged->width);
        $this->assertSame(320, $merged->height);
    }
}
