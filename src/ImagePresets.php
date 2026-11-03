<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

use InvalidArgumentException;

final readonly class ImagePresets
{
    /**
     * @param  array<string, array{width?: int, height?: int, fit?: string, format?: string, quality?: int}>  $presets
     */
    public function __construct(private array $presets) {}

    public function resolve(string $name): ImageModifiers
    {
        if (! array_key_exists($name, $this->presets)) {
            throw new InvalidArgumentException("Unknown image preset [{$name}].");
        }

        return ImageModifiers::fromArray($this->presets[$name]);
    }
}
