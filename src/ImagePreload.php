<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

final readonly class ImagePreload
{
    public function __construct(
        public string $url,
        public ?string $srcset = null,
        public ?string $sizes = null,
        public ?string $media = null,
    ) {}
}
