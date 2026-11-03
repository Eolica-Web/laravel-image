<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

final readonly class ImageSources
{
    /**
     * @param  list<array{screen: string, image: ResolvedImage}>  $sources
     */
    public function __construct(
        public array $sources = [],
        public ?string $untilScreen = null,
    ) {}
}
