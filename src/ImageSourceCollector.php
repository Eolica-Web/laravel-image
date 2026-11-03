<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

final class ImageSourceCollector
{
    /** @var list<array{screen: string, image: ResolvedImage}> */
    private array $sources = [];

    public function __construct(
        private readonly ImageScreens $screens,
    ) {}

    public function add(string $screen, ResolvedImage $image): void
    {
        $this->sources[] = ['screen' => $screen, 'image' => $image];
    }

    public function pull(): ImageSources
    {
        $sources = $this->sources;
        $this->sources = [];

        $untilScreen = null;

        foreach ($sources as $source) {
            if ($untilScreen === null || $this->screens->width($source['screen']) < $this->screens->width($untilScreen)) {
                $untilScreen = $source['screen'];
            }
        }

        return new ImageSources($sources, $untilScreen);
    }
}
