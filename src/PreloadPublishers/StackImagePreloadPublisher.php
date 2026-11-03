<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\PreloadPublishers;

use Eolica\LaravelImage\ImagePreload;
use Eolica\LaravelImage\ImagePreloadPublisher;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\View\ComponentAttributeBag;

final readonly class StackImagePreloadPublisher implements ImagePreloadPublisher
{
    public function __construct(
        private ViewFactory $view,
        private string $stack,
    ) {}

    public function preload(ImagePreload $preload): void
    {
        $attributes = new ComponentAttributeBag()->merge([
            'rel' => 'preload',
            'as' => 'image',
            'href' => $preload->url,
            'imagesrcset' => $preload->srcset,
            'imagesizes' => $preload->sizes,
            'media' => $preload->media,
        ]);

        $this->view->startPush($this->stack, "<link {$attributes} />");
    }
}
