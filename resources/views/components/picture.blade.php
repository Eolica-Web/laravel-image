@blaze

@inject('imageManager', Eolica\LaravelImage\ImageManager::class)
@inject('imagePreloader', Eolica\LaravelImage\ImagePreloader::class)
@inject('imageSourceCollector', Eolica\LaravelImage\ImageSourceCollector::class)

@props([
    'src',
    'preset' => null,
    'width' => null,
    'height' => null,
    'fit' => null,
    'quality' => null,
    'format' => null,
    'provider' => null,
    'sizes' => null,
    'densities' => null,
    'originalWidth' => null,
    'originalHeight' => null,
    'preload' => false,
])

@php
use Eolica\LaravelImage\ImageModifiers;

$sources = $imageSourceCollector->pull();

$resolved = $imageManager->resolve(
    $src,
    ImageModifiers::fromProps($width, $height, $fit, $format, $quality),
    $provider,
    $preset,
    sizes: $sizes,
    densities: $densities,
    originalWidth: $originalWidth,
    originalHeight: $originalHeight,
    untilScreen: $sources->untilScreen,
);

if ($preload) {
    $imagePreloader->picture($resolved, $sources);
}
@endphp

<picture>
    {{ $slot }}

    <img
        src="{{ $resolved->url }}"
        {{ $attributes->except(['srcset'])->merge([
            'srcset' => $resolved->srcset,
            'sizes' => $resolved->sizes,
            'width' => $resolved->width,
            'height' => $resolved->height,
        ]) }}
    />
</picture>
