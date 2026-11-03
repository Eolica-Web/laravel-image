<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

use InvalidArgumentException;

final class ImageProviderRegistry
{
    /** @var array<string, callable(ProviderSettings): ImageProvider> */
    private array $factories = [];

    /**
     * @param  callable(ProviderSettings): ImageProvider  $factory
     */
    public function register(string $driver, callable $factory): void
    {
        if (isset($this->factories[$driver])) {
            throw new InvalidArgumentException("Image provider driver [{$driver}] is already registered.");
        }

        $this->factories[$driver] = $factory;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function resolve(string $driver, array $settings): ImageProvider
    {
        if (! isset($this->factories[$driver])) {
            throw new InvalidArgumentException("Unknown image provider driver [{$driver}].");
        }

        return ($this->factories[$driver])(new ProviderSettings($settings));
    }
}
