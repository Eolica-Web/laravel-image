<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

use InvalidArgumentException;

final readonly class ImageManager
{
    /**
     * @param  array<string, ImageProvider>  $providers
     * @param  array<string, array<string, mixed>>  $providerDefaults
     */
    public function __construct(
        private string $defaultProvider,
        private array $providers,
        private array $providerDefaults,
        private ImageScreens $screens,
        private ImagePresets $presets,
        private ImageSrcsetBuilder $srcsetBuilder,
    ) {}

    public function resolve(
        string $src,
        ImageModifiers $modifiers = new ImageModifiers(),
        ?string $provider = null,
        ?string $preset = null,
        ?string $sizes = null,
        ?string $densities = null,
        ?string $screen = null,
        ?string $untilScreen = null,
        ?int $originalWidth = null,
        ?int $originalHeight = null,
    ): ResolvedImage {
        $providerName = $provider ?? $this->defaultProvider;

        if ($providerName === '') {
            throw new InvalidArgumentException('Image provider is not configured.');
        }

        if (! isset($this->providers[$providerName])) {
            throw new InvalidArgumentException("Image provider [{$providerName}] is not configured.");
        }

        $normalizedSrc = $this->normalizeSrc($src);

        $urlBuilder = fn (ImageModifiers $modifiers): string => $this->providers[$providerName]->url(
            $normalizedSrc,
            $this->mergeDefaults($providerName, $modifiers),
        );

        $displayModifiers = $this->displayModifiers($modifiers, $preset);

        $parsedSizes = $this->parseSizes($sizes);

        return new ResolvedImage(
            url: $urlBuilder($displayModifiers),
            width: $displayModifiers->width,
            height: $displayModifiers->height,
            format: $displayModifiers->format,
            srcset: $this->srcsetBuilder->srcset(
                $displayModifiers,
                $parsedSizes,
                $this->parseDensities($densities),
                $screen,
                $untilScreen,
                $urlBuilder,
                $originalWidth,
                $originalHeight,
            ),
            sizes: $this->srcsetBuilder->sizes($parsedSizes),
        );
    }

    public function url(
        string $src,
        ImageModifiers $modifiers = new ImageModifiers(),
        ?string $provider = null,
        ?string $preset = null,
    ): string {
        return $this->resolve($src, $modifiers, $provider, $preset)->url;
    }

    public function mediaQuery(string $screen): string
    {
        return $this->screens->mediaQuery($screen);
    }

    private function parseSizes(?string $sizes): ?ImageSizes
    {
        if ($sizes === null || trim($sizes) === '') {
            return null;
        }

        return ImageSizes::parse($sizes);
    }

    private function parseDensities(?string $densities): ?ImageDensities
    {
        if ($densities === null || trim($densities) === '') {
            return null;
        }

        return ImageDensities::parse($densities);
    }

    private function displayModifiers(ImageModifiers $modifiers, ?string $preset): ImageModifiers
    {
        $base = $preset !== null
            ? $this->presets->resolve($preset)
            : new ImageModifiers();

        return $base->merge($modifiers);
    }

    private function normalizeSrc(string $src): string
    {
        return ltrim($src, '/');
    }

    private function mergeDefaults(string $providerName, ImageModifiers $modifiers): ImageModifiers
    {
        /** @var array<string, mixed> $defaults */
        $defaults = $this->providerDefaults[$providerName] ?? [];

        return ImageModifiers::fromArray($defaults)->merge($modifiers);
    }
}
