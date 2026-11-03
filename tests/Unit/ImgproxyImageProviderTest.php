<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit;

use Eolica\LaravelImage\ImageModifiers;
use Eolica\LaravelImage\Providers\ImgproxyImageProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ImgproxyImageProviderTest extends TestCase
{
    private const TEST_KEY = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    private const TEST_SALT = 'fedcba9876543210fedcba9876543210fedcba9876543210fedcba9876543210';

    private ImgproxyImageProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new ImgproxyImageProvider(
            baseUrl: 'http://localhost:8080',
            key: self::TEST_KEY,
            salt: self::TEST_SALT,
            bucket: 'public',
        );
    }

    #[Test]
    public function it_builds_a_url_with_dimensions_format_and_quality(): void
    {
        $url = $this->provider->url('images/web/hero.webp', ImageModifiers::fromProps(
            width: 640,
            height: 320,
            format: 'webp',
            quality: 90,
        ));

        $this->assertStringStartsWith('http://localhost:8080/', $url);
        $this->assertStringContainsString('/rt:fit/w:640/h:320/', $url);
        $this->assertStringContainsString('/q:90/', $url);
        $this->assertStringContainsString('/f:webp/', $url);
        $this->assertStringContainsString('/plain/s3://public/images/web/hero.webp', $url);
        $this->assertStringNotContainsString('@webp', $url);
    }

    #[Test]
    public function it_maps_cover_fit_to_center_gravity(): void
    {
        $url = $this->provider->url('images/web/hero.webp', ImageModifiers::fromProps(
            width: 640,
            height: 320,
            fit: 'cover',
        ));

        $this->assertStringContainsString('/rt:fill/w:640/h:320/', $url);
        $this->assertStringContainsString('/g:ce/', $url);
    }

    #[Test]
    public function it_maps_cover_top_left_fit_to_northwest_gravity(): void
    {
        $url = $this->provider->url('images/web/hero.webp', ImageModifiers::fromProps(
            width: 640,
            height: 320,
            fit: 'cover-top-left',
        ));

        $this->assertStringContainsString('/rt:fill/w:640/h:320/', $url);
        $this->assertStringContainsString('/g:nowe/', $url);
    }

    #[Test]
    public function it_produces_a_deterministic_signature(): void
    {
        $modifiers = ImageModifiers::fromProps(
            width: 640,
            height: 320,
            fit: 'cover',
            format: 'webp',
            quality: 80,
        );

        $url = $this->provider->url('images/web/hero.webp', $modifiers);

        $path = '/rt:fill/w:640/h:320/g:ce/q:80/f:webp/plain/s3://public/images/web/hero.webp';
        $expectedSignature = $this->expectedSignature($path);

        $this->assertSame(
            "http://localhost:8080/{$expectedSignature}{$path}",
            $url,
        );
    }

    #[Test]
    public function it_resizes_by_width_only_using_auto_height(): void
    {
        $url = $this->provider->url('images/web/hero.webp', ImageModifiers::fromProps(
            width: 640,
            format: 'webp',
        ));

        $this->assertStringContainsString('/rt:fit/w:640/h:0/', $url);
        $this->assertStringContainsString('/f:webp/', $url);
        $this->assertStringContainsString('/plain/s3://public/images/web/hero.webp', $url);
        $this->assertStringNotContainsString('@webp', $url);
    }

    #[Test]
    public function it_resizes_by_height_only_using_auto_width(): void
    {
        $url = $this->provider->url('images/web/hero.webp', ImageModifiers::fromProps(
            height: 320,
            format: 'webp',
        ));

        $this->assertStringContainsString('/rt:fit/w:0/h:320/', $url);
        $this->assertStringContainsString('/f:webp/', $url);
        $this->assertStringContainsString('/plain/s3://public/images/web/hero.webp', $url);
        $this->assertStringNotContainsString('@webp', $url);
    }

    #[Test]
    public function it_applies_cover_fit_when_only_width_is_set(): void
    {
        $url = $this->provider->url('images/web/hero.webp', ImageModifiers::fromProps(
            width: 640,
            fit: 'cover',
        ));

        $this->assertStringContainsString('/rt:fill/w:640/h:0/', $url);
        $this->assertStringContainsString('/g:ce/', $url);
    }

    #[Test]
    public function it_normalizes_src_path(): void
    {
        $url = $this->provider->url('/images/foo.jpg', ImageModifiers::fromProps(
            width: 100,
            height: 100,
        ));

        $this->assertStringContainsString('/plain/s3://public/images/foo.jpg', $url);
        $this->assertStringNotContainsString('s3://public//images', $url);
    }

    private function expectedSignature(string $path): string
    {
        $key = hex2bin(self::TEST_KEY);
        $salt = hex2bin(self::TEST_SALT);

        return rtrim(strtr(
            base64_encode(hash_hmac('sha256', $salt.$path, $key, true)),
            '+/',
            '-_',
        ), '=');
    }
}
