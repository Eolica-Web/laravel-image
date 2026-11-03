<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

final readonly class ImagePreloader
{
    public function __construct(
        private ImagePreloadPublisher $publisher,
        private ImageScreens $screens,
    ) {}

    public function image(ResolvedImage $image): void
    {
        $this->publish($image, null);
    }

    public function picture(ResolvedImage $fallback, ImageSources $sources): void
    {
        $until = null;

        foreach ($sources->sources as $source) {
            $from = $this->screens->width($source['screen']);

            if ($until !== null && $from >= $until) {
                continue;
            }

            $media = $this->screens->mediaQuery($source['screen']);

            $this->publish($source['image'], $until !== null ? "{$media} and {$this->maxWidth($until)}" : $media);

            $until = $from;
        }

        $this->publish($fallback, $until !== null ? $this->maxWidth($until) : null);
    }

    private function publish(ResolvedImage $image, ?string $media): void
    {
        $this->publisher->preload(new ImagePreload(
            url: $image->url,
            srcset: $image->srcset,
            sizes: $image->sizes,
            media: $media,
        ));
    }

    private function maxWidth(int $width): string
    {
        return '(max-width: '.($width - 0.02).'px)';
    }
}
