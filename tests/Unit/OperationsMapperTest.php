<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit;

use Eolica\LaravelImage\ImageModifiers;
use Eolica\LaravelImage\OperationsMapper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OperationsMapperTest extends TestCase
{
    #[Test]
    public function it_maps_modifier_keys_to_provider_keys(): void
    {
        $mapper = new OperationsMapper([
            'width' => 'w',
            'height' => 'h',
            'quality' => 'q',
            'format' => 'fm',
        ]);

        $params = $mapper->map(ImageModifiers::fromProps(
            width: 640,
            height: 320,
            format: 'webp',
            quality: 80,
        ));

        $this->assertSame([
            'w' => 640,
            'h' => 320,
            'fm' => 'webp',
            'q' => 80,
        ], $params);
    }

    #[Test]
    public function it_skips_modifiers_without_a_key_mapping(): void
    {
        $mapper = new OperationsMapper([
            'width' => 'width',
        ]);

        $params = $mapper->map(ImageModifiers::fromProps(
            width: 640,
            height: 320,
            fit: 'cover',
        ));

        $this->assertSame(['width' => 640], $params);
    }

    #[Test]
    public function it_maps_scalar_values_through_value_map(): void
    {
        $mapper = new OperationsMapper(
            keyMap: ['fit' => 'fit'],
            valueMap: [
                'fit' => [
                    'cover' => 'crop',
                    'cover-center' => 'crop-center',
                ],
            ],
        );

        $params = $mapper->map(ImageModifiers::fromProps(fit: 'cover-center'));

        $this->assertSame(['fit' => 'crop-center'], $params);
    }

    #[Test]
    public function it_expands_fit_array_mappings_into_multiple_params(): void
    {
        $mapper = new OperationsMapper(
            keyMap: ['fit' => 'fit'],
            valueMap: [
                'fit' => [
                    'cover' => [
                        'crop' => 'true',
                        'crop_gravity' => 'center',
                    ],
                    'cover-top-left' => [
                        'crop' => 'true',
                        'crop_gravity' => 'northwest',
                    ],
                ],
            ],
        );

        $params = $mapper->map(ImageModifiers::fromProps(fit: 'cover'));

        $this->assertSame([
            'crop' => 'true',
            'crop_gravity' => 'center',
        ], $params);
    }

    #[Test]
    public function it_uses_key_map_for_fit_when_no_value_map_entry_exists(): void
    {
        $mapper = new OperationsMapper(
            keyMap: ['fit' => 'fit'],
            valueMap: [
                'fit' => [
                    'cover' => 'crop',
                ],
            ],
        );

        $params = $mapper->map(ImageModifiers::fromProps(fit: 'contain'));

        $this->assertSame(['fit' => 'contain'], $params);
    }

    #[Test]
    public function it_returns_an_empty_array_for_empty_modifiers(): void
    {
        $mapper = new OperationsMapper([
            'width' => 'width',
            'height' => 'height',
        ]);

        $this->assertSame([], $mapper->map(new ImageModifiers()));
    }

    #[Test]
    public function it_generates_bunny_style_params(): void
    {
        $mapper = new OperationsMapper(
            keyMap: [
                'width' => 'width',
                'height' => 'height',
                'quality' => 'quality',
                'format' => 'format',
            ],
            valueMap: [
                'fit' => [
                    'cover-top-left' => [
                        'crop' => 'true',
                        'crop_gravity' => 'northwest',
                    ],
                ],
            ],
        );

        $params = $mapper->map(ImageModifiers::fromProps(
            width: 640,
            height: 320,
            fit: 'cover-top-left',
            format: 'webp',
            quality: 80,
        ));

        $this->assertSame([
            'width' => 640,
            'height' => 320,
            'crop' => 'true',
            'crop_gravity' => 'northwest',
            'format' => 'webp',
            'quality' => 80,
        ], $params);
    }
}
