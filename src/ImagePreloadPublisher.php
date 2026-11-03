<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

interface ImagePreloadPublisher
{
    public function preload(ImagePreload $preload): void;
}
