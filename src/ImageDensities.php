<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

use InvalidArgumentException;

final readonly class ImageDensities
{
    /**
     * @param  list<float>  $values
     */
    private function __construct(public array $values) {}

    public static function parse(string $densities): self
    {
        $tokens = preg_split('/[\s,]+/', trim($densities), flags: PREG_SPLIT_NO_EMPTY) ?: [];

        return self::fromList(array_map(function (string $token): float {
            $value = trim($token, 'x');

            if (! is_numeric($value)) {
                throw new InvalidArgumentException("Invalid image density [{$token}].");
            }

            return (float) $value;
        }, $tokens));
    }

    /**
     * @param  list<int|float>  $densities
     */
    public static function fromList(array $densities): self
    {
        $values = [];

        foreach ($densities as $density) {
            if ($density <= 0) {
                throw new InvalidArgumentException("Invalid image density [{$density}].");
            }

            $values[] = (float) $density;
        }

        if ($values === []) {
            throw new InvalidArgumentException('Image densities must not be empty.');
        }

        $values = array_values(array_unique($values, SORT_REGULAR));

        sort($values);

        return new self($values);
    }

    public static function descriptor(float $density): string
    {
        return rtrim(rtrim(number_format($density, 2, '.', ''), '0'), '.').'x';
    }
}
