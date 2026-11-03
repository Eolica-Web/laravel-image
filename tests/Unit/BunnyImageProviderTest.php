<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit;

use Eolica\LaravelImage\ImageModifiers;
use Eolica\LaravelImage\Providers\BunnyImageProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class BunnyImageProviderTest extends TestCase
{
    private BunnyImageProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new BunnyImageProvider(
            baseUrl: 'https://cdn.example.com',
        );
    }

    #[Test]
    public function it_builds_a_url_with_dimensions_and_format(): void
    {
        $url = $this->provider->url('images/web/hero.webp', ImageModifiers::fromProps(
            width: 640,
            height: 320,
            format: 'webp',
            quality: 90,
        ));

        $this->assertSame(
            'https://cdn.example.com/images/web/hero.webp?width=640&height=320&format=webp&quality=90',
            $url,
        );
    }

    #[Test]
    public function it_maps_cover_fit_to_crop_parameters(): void
    {
        $url = $this->provider->url('images/web/hero.webp', ImageModifiers::fromProps(
            width: 640,
            height: 320,
            fit: 'cover',
        ));

        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

        $this->assertSame('true', $query['crop'] ?? null);
        $this->assertSame('center', $query['crop_gravity'] ?? null);
    }

    #[Test]
    public function it_maps_cover_top_left_fit_to_northwest_gravity(): void
    {
        $url = $this->provider->url('images/web/hero.webp', ImageModifiers::fromProps(
            width: 640,
            height: 320,
            fit: 'cover-top-left',
        ));

        parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

        $this->assertSame('true', $query['crop'] ?? null);
        $this->assertSame('northwest', $query['crop_gravity'] ?? null);
    }
}
