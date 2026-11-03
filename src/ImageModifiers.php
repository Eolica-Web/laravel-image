<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

final readonly class ImageModifiers
{
    public function __construct(
        public ?int $width = null,
        public ?int $height = null,
        public ?string $fit = null,
        public ?string $format = null,
        public ?int $quality = null,
    ) {}

    /**
     * @param  int|string|null  $width
     * @param  int|string|null  $height
     * @param  int|string|null  $quality
     */
    public static function fromProps(
        int|string|null $width = null,
        int|string|null $height = null,
        ?string $fit = null,
        ?string $format = null,
        int|string|null $quality = null,
    ): self {
        return new self(
            width: self::parseInt($width),
            height: self::parseInt($height),
            fit: $fit,
            format: $format,
            quality: self::parseInt($quality),
        );
    }

    /**
     * @param  array{width?: int|null, height?: int|null, fit?: string|null, format?: string|null, quality?: int|null}  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            width: $attributes['width'] ?? null,
            height: $attributes['height'] ?? null,
            fit: $attributes['fit'] ?? null,
            format: $attributes['format'] ?? null,
            quality: $attributes['quality'] ?? null,
        );
    }

    /**
     * @return array{width?: int, height?: int, fit?: string, format?: string, quality?: int}
     */
    public function toArray(): array
    {
        return array_filter([
            'width' => $this->width,
            'height' => $this->height,
            'fit' => $this->fit,
            'format' => $this->format,
            'quality' => $this->quality,
        ], fn (mixed $value): bool => $value !== null);
    }

    public function isCover(): bool
    {
        return in_array($this->fit, ['cover', 'cover-center', 'cover-top-left'], true);
    }

    public function merge(self $other): self
    {
        return new self(
            width: $other->width ?? $this->width,
            height: $other->height ?? $this->height,
            fit: $other->fit ?? $this->fit,
            format: $other->format ?? $this->format,
            quality: $other->quality ?? $this->quality,
        );
    }

    private static function parseInt(int|string|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        return (int) str_replace('px', '', $value);
    }
}
