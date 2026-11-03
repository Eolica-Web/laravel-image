@blaze

@inject('imageManager', Eolica\LaravelImage\ImageManager::class)
@inject('imageSourceCollector', Eolica\LaravelImage\ImageSourceCollector::class)

@props([
    'screen',
    'width',
    'height',
    'preset' => null,
    'fit' => null,
    'quality' => null,
    'format' => null,
    'sizes' => null,
    'densities' => null,
])

@aware([
    'src',
    'provider' => null,
    'preset' => null,
    'originalWidth' => null,
    'originalHeight' => null,
])

@php
use Eolica\LaravelImage\ImageModifiers;

$media = $imageManager->mediaQuery($screen);

$resolved = $imageManager->resolve(
    $src,
    ImageModifiers::fromProps($width, $height, $fit, $format, $quality),
    $provider,
    $preset,
    sizes: $sizes,
    densities: $densities,
    screen: $screen,
    originalWidth: $originalWidth,
    originalHeight: $originalHeight,
);

$imageSourceCollector->add($screen, $resolved);
@endphp

<source {{ $attributes->except(['media', 'srcset'])->merge([
    'media' => $media,
    'srcset' => $resolved->srcset ?? $resolved->url,
    'sizes' => $resolved->sizes,
]) }} />
