<?php

declare(strict_types=1);

use Eolica\LaravelImage\PreloadPublishers\StackImagePreloadPublisher;
use Eolica\LaravelImage\Providers\BunnyImageProvider;
use Eolica\LaravelImage\Providers\GlideImageProvider;
use Eolica\LaravelImage\Providers\ImgproxyImageProvider;

return [
    'provider' => env('IMAGE_PROVIDER'),

    'screens' => [
        'xs' => 320,
        'sm' => 640,
        'md' => 768,
        'lg' => 1024,
        'xl' => 1280,
        '2xl' => 1536,
    ],

    'densities' => [1, 2],

    'providers' => [
        // 'bunny' => [
        //     'driver' => BunnyImageProvider::class,
        //     'base_url' => env('BUNNY_BASE_URL'),
        //     'defaults' => [
        //         'quality' => 80,
        //     ],
        // ],

        // requires `composer require league/glide` in your app.
        // 'glide' => [
        //     'driver' => GlideImageProvider::class,
        //     'base_path' => '/images',
        //     'sign_key' => env('GLIDE_SIGN_KEY'),
        //     'app_url' => env('APP_URL'),
        // ],

        // 'imgproxy' => [
        //     'driver' => ImgproxyImageProvider::class,
        //     'base_url' => env('IMGPROXY_BASE_URL', 'http://localhost:8080'),
        //     'key' => env('IMGPROXY_KEY'),
        //     'salt' => env('IMGPROXY_SALT'),
        //     'bucket' => env('AWS_BUCKET', 'public'),
        //     'defaults' => [
        //         'quality' => 80,
        //     ],
        // ],
    ],

    'presets' => [
        // 'og' => [
        //     'width' => 1200,
        //     'height' => 630,
        //     'fit' => 'cover',
        //     'format' => 'webp',
        //     'quality' => 80,
        // ],
    ],

    'preload' => [
        'publisher' => StackImagePreloadPublisher::class,
        'stack' => 'head',
    ],
];
