<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

use InvalidArgumentException;

final readonly class ImageScreens
{
    /**
     * @param  array<string, int>  $screens
     */
    public function __construct(private array $screens) {}

    public function mediaQuery(string $screen): string
    {
        return "(min-width: {$this->width($screen)}px)";
    }

    public function width(string $screen): int
    {
        if (!array_key_exists($screen, $this->screens)) {
            throw new InvalidArgumentException("Unknown image screen [{$screen}].");
        }

        return $this->screens[$screen];
    }

    /**
     * @return list<int>
     */
    public function widths(): array
    {
        $widths = array_values(array_unique($this->screens));

        sort($widths);

        return $widths;
    }
}
