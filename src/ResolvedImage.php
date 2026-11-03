<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

final readonly class ResolvedImage
{
    public function __construct(
        public string $url,
        public ?int $width = null,
        public ?int $height = null,
        public ?string $format = null,
        public ?string $srcset = null,
        public ?string $sizes = null,
    ) {}
}
