<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit;

use Eolica\LaravelImage\ImageDensities;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ImageDensitiesTest extends TestCase
{
    #[Test]
    public function it_parses_densities_with_a_trailing_or_leading_x(): void
    {
        $this->assertSame([1.0, 2.0], ImageDensities::parse('1x 2x')->values);
        $this->assertSame([1.0, 2.0], ImageDensities::parse('x1 x2')->values);
        $this->assertSame([1.0, 1.5, 2.0], ImageDensities::parse('2, 1.5x, 1')->values);
    }

    #[Test]
    public function it_removes_duplicate_densities(): void
    {
        $this->assertSame([1.0, 2.0], ImageDensities::fromList([2, 1, 2.0])->values);
    }

    #[Test]
    public function it_formats_density_descriptors(): void
    {
        $this->assertSame('1x', ImageDensities::descriptor(1.0));
        $this->assertSame('1.5x', ImageDensities::descriptor(1.5));
    }

    #[Test]
    public function it_throws_for_an_invalid_density(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid image density [retina].');

        ImageDensities::parse('1x retina');
    }

    #[Test]
    public function it_throws_for_a_non_positive_density(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ImageDensities::parse('0x');
    }

    #[Test]
    public function it_throws_for_empty_densities(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ImageDensities::fromList([]);
    }
}
