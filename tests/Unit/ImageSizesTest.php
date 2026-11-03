<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit;

use Eolica\LaravelImage\ImageSizes;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ImageSizesTest extends TestCase
{
    #[Test]
    public function it_parses_entries_with_and_without_a_screen(): void
    {
        $sizes = ImageSizes::parse('100vw sm:50vw lg:400px');

        $this->assertSame([
            ['screen' => null, 'value' => 100, 'unit' => 'vw'],
            ['screen' => 'sm', 'value' => 50, 'unit' => 'vw'],
            ['screen' => 'lg', 'value' => 400, 'unit' => 'px'],
        ], $sizes->entries);
    }

    #[Test]
    public function it_accepts_entries_separated_by_commas(): void
    {
        $sizes = ImageSizes::parse('lg:400px, 100vw');

        $this->assertSame([
            ['screen' => 'lg', 'value' => 400, 'unit' => 'px'],
            ['screen' => null, 'value' => 100, 'unit' => 'vw'],
        ], $sizes->entries);
    }

    #[Test]
    public function it_treats_unitless_sizes_as_pixels(): void
    {
        $sizes = ImageSizes::parse('400');

        $this->assertSame([['screen' => null, 'value' => 400, 'unit' => 'px']], $sizes->entries);
    }

    #[Test]
    public function it_adds_a_full_width_entry_when_there_is_no_unprefixed_one(): void
    {
        $sizes = ImageSizes::parse('md:50vw');

        $this->assertSame([
            ['screen' => 'md', 'value' => 50, 'unit' => 'vw'],
            ['screen' => null, 'value' => 100, 'unit' => 'vw'],
        ], $sizes->entries);
    }

    #[Test]
    public function it_keeps_the_last_entry_for_a_repeated_screen(): void
    {
        $sizes = ImageSizes::parse('100vw md:50vw md:30vw');

        $this->assertSame([
            ['screen' => null, 'value' => 100, 'unit' => 'vw'],
            ['screen' => 'md', 'value' => 30, 'unit' => 'vw'],
        ], $sizes->entries);
    }

    #[Test]
    public function it_throws_for_an_invalid_size(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid image size [sm:50%].');

        ImageSizes::parse('100vw sm:50%');
    }

    #[Test]
    public function it_throws_for_a_zero_size(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid image size [0vw].');

        ImageSizes::parse('0vw');
    }

    #[Test]
    public function it_throws_for_empty_sizes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Image sizes must not be empty.');

        ImageSizes::parse('  ');
    }
}
