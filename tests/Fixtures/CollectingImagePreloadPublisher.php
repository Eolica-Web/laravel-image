<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Fixtures;

use Eolica\LaravelImage\ImagePreload;
use Eolica\LaravelImage\ImagePreloadPublisher;

final class CollectingImagePreloadPublisher implements ImagePreloadPublisher
{
    /** @var list<ImagePreload> */
    public array $preloads = [];

    public function preload(ImagePreload $preload): void
    {
        $this->preloads[] = $preload;
    }
}
