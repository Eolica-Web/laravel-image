<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit;

use Eolica\LaravelImage\ImageModifiers;
use Eolica\LaravelImage\Providers\GlideImageProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class GlideImageProviderTest extends TestCase
{
    private GlideImageProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new GlideImageProvider(
            basePath: '/images',
            signKey: 'test-sign-key',
            appUrl: 'https://app.example.com',
        );
    }

    #[Test]
    public function it_builds_a_signed_url_with_glide_parameters(): void
    {
        $url = $this->provider->url('images/web/hero.webp', ImageModifiers::fromProps(
            width: 640,
            height: 320,
            format: 'webp',
            quality: 90,
        ));

        $this->assertStringStartsWith('https://app.example.com/images/images/web/hero.webp?', $url);
        $this->assertStringContainsString('w=640', $url);
        $this->assertStringContainsString('h=320', $url);
        $this->assertStringContainsString('fm=webp', $url);
        $this->assertStringContainsString('q=90', $url);
        $this->assertStringContainsString('s=', $url);
    }

    #[Test]
    public function it_maps_cover_fit_to_crop(): void
    {
        $url = $this->provider->url('images/web/hero.webp', ImageModifiers::fromProps(
            width: 640,
            height: 320,
            fit: 'cover',
        ));

        $this->assertStringContainsString('fit=crop', $url);
    }

    #[Test]
    public function it_maps_cover_top_left_fit_to_crop_top_left(): void
    {
        $url = $this->provider->url('images/web/hero.webp', ImageModifiers::fromProps(
            width: 640,
            height: 320,
            fit: 'cover-top-left',
        ));

        $this->assertStringContainsString('fit=crop-top-left', $url);
    }
}
