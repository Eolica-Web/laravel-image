<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

use InvalidArgumentException;

final readonly class ImageSrcsetBuilder
{
    public function __construct(
        private ImageScreens $screens,
        private ImageDensities $densities,
    ) {}

    /**
     * @param  callable(ImageModifiers): string  $urlBuilder
     */
    public function srcset(
        ImageModifiers $modifiers,
        ?ImageSizes $sizes,
        ?ImageDensities $densities,
        ?string $screen,
        ?string $untilScreen,
        callable $urlBuilder,
        ?int $originalWidth = null,
        ?int $originalHeight = null,
    ): ?string {
        $maxWidth = $this->maxWidth($modifiers, $originalWidth, $originalHeight);

        if ($sizes !== null) {
            return $this->widthSrcset(
                $modifiers,
                $this->sizeWidths(
                    $this->sizeRanges($sizes),
                    $screen !== null ? $this->screens->width($screen) : 0,
                    $untilScreen !== null ? $this->screens->width($untilScreen) : null,
                ),
                $densities ?? $this->densities,
                $maxWidth,
                $urlBuilder,
            );
        }

        if ($densities !== null) {
            return $this->densitySrcset(
                $modifiers,
                $densities,
                $modifiers->width !== null ? $maxWidth : $originalHeight,
                $urlBuilder,
            );
        }

        return null;
    }

    public function sizes(?ImageSizes $sizes): ?string
    {
        if ($sizes === null) {
            return null;
        }

        $parts = array_map(
            fn (array $range): string => trim("{$range['media']} {$range['value']}{$range['unit']}"),
            array_reverse($this->sizeRanges($sizes)),
        );

        return implode(', ', $parts);
    }

    private function maxWidth(ImageModifiers $modifiers, ?int $originalWidth, ?int $originalHeight): ?int
    {
        $limits = [];

        if ($originalWidth !== null) {
            $limits[] = $originalWidth;
        }

        if ($originalHeight !== null && $modifiers->isCover() && $modifiers->width !== null && $modifiers->height !== null) {
            $limits[] = (int) floor($originalHeight * $modifiers->width / $modifiers->height);
        }

        return $limits !== [] ? min($limits) : null;
    }

    /**
     * @return list<array{from: int, media: string|null, value: int, unit: 'vw'|'px'}>
     */
    private function sizeRanges(ImageSizes $sizes): array
    {
        $ranges = [];

        foreach ($sizes->entries as $entry) {
            $from = $entry['screen'] !== null ? $this->screens->width($entry['screen']) : 0;

            $ranges[$from] = [
                'from' => $from,
                'media' => $entry['screen'] !== null ? $this->screens->mediaQuery($entry['screen']) : null,
                'value' => $entry['value'],
                'unit' => $entry['unit'],
            ];
        }

        ksort($ranges);

        return array_values($ranges);
    }

    /**
     * @param  list<array{from: int, media: string|null, value: int, unit: 'vw'|'px'}>  $ranges
     * @return list<int>
     */
    private function sizeWidths(array $ranges, int $minViewport, ?int $maxViewport): array
    {
        $screenWidths = $this->screens->widths();
        $widths = [];

        foreach ($ranges as $index => $range) {
            $from = max($range['from'], $minViewport);
            $to = $ranges[$index + 1]['from'] ?? null;

            if ($maxViewport !== null) {
                $to = $to !== null ? min($to, $maxViewport) : $maxViewport;
            }

            if ($to !== null && $to <= $from) {
                continue;
            }

            if ($range['unit'] === 'px') {
                $widths[] = $range['value'];

                continue;
            }

            $viewports = array_filter(
                $screenWidths,
                fn (int $width): bool => $width > $from && ($to === null || $width <= $to),
            );

            if ($viewports === [] && $from > 0) {
                $viewports = [$from];
            }

            if ($viewports === []) {
                throw new InvalidArgumentException('Fluid image sizes require at least one configured screen.');
            }

            foreach ($viewports as $viewport) {
                $widths[] = (int) round($viewport * $range['value'] / 100);
            }
        }

        return $widths;
    }

    /**
     * @param  list<int>  $sizeWidths
     * @param  callable(ImageModifiers): string  $urlBuilder
     */
    private function widthSrcset(
        ImageModifiers $modifiers,
        array $sizeWidths,
        ImageDensities $densities,
        ?int $maxWidth,
        callable $urlBuilder,
    ): string {
        $ratio = $modifiers->width !== null && $modifiers->height !== null
            ? $modifiers->height / $modifiers->width
            : null;

        $widths = [];

        foreach ($sizeWidths as $width) {
            foreach ($densities->values as $density) {
                $widths[] = (int) round($width * $density);
            }
        }

        if ($maxWidth !== null) {
            $widths = array_map(fn (int $width): int => min($width, $maxWidth), $widths);
        }

        $widths = array_values(array_unique($widths));

        sort($widths);

        $candidates = array_map(fn (int $width): string => $urlBuilder(new ImageModifiers(
            width: $width,
            height: $ratio !== null ? (int) round($width * $ratio) : null,
            fit: $modifiers->fit,
            format: $modifiers->format,
            quality: $modifiers->quality,
        ))." {$width}w", $widths);

        return implode(', ', $candidates);
    }

    /**
     * @param  callable(ImageModifiers): string  $urlBuilder
     */
    private function densitySrcset(
        ImageModifiers $modifiers,
        ImageDensities $densities,
        ?int $maxSize,
        callable $urlBuilder,
    ): string {
        $size = $modifiers->width ?? $modifiers->height;

        if ($size === null) {
            throw new InvalidArgumentException('Image densities require a width or a height.');
        }

        $scales = [];

        foreach ($densities->values as $density) {
            if ($scales !== [] && $maxSize !== null && $size * $density > $maxSize) {
                $scale = round($maxSize / $size, 2);

                if ($scale > end($scales)) {
                    $scales[] = $scale;
                }

                break;
            }

            $scales[] = $density;
        }

        $candidates = array_map(fn (float $scale): string => $urlBuilder(new ImageModifiers(
            width: $modifiers->width !== null ? (int) round($modifiers->width * $scale) : null,
            height: $modifiers->height !== null ? (int) round($modifiers->height * $scale) : null,
            fit: $modifiers->fit,
            format: $modifiers->format,
            quality: $modifiers->quality,
        )).' '.ImageDensities::descriptor($scale), $scales);

        return implode(', ', $candidates);
    }
}
