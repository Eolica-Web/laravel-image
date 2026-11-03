<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit;

use Eolica\LaravelImage\ProviderSettings;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProviderSettingsTest extends TestCase
{
    #[Test]
    public function it_returns_required_string_values(): void
    {
        $settings = new ProviderSettings([
            'base_url' => 'https://cdn.example.com',
        ]);

        $this->assertSame('https://cdn.example.com', $settings->requireString('base_url'));
    }

    #[Test]
    public function it_throws_when_a_required_string_is_missing(): void
    {
        $settings = new ProviderSettings([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Image provider setting [base_url] is required.');

        $settings->requireString('base_url');
    }

    #[Test]
    public function it_throws_when_a_required_string_is_empty(): void
    {
        $settings = new ProviderSettings([
            'base_url' => '',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Image provider setting [base_url] is required.');

        $settings->requireString('base_url');
    }

    #[Test]
    public function it_returns_optional_string_values(): void
    {
        $settings = new ProviderSettings([
            'app_url' => 'https://app.example.com',
        ]);

        $this->assertSame('https://app.example.com', $settings->optionalString('app_url'));
    }

    #[Test]
    public function it_returns_null_for_missing_optional_strings(): void
    {
        $settings = new ProviderSettings([]);

        $this->assertNull($settings->optionalString('app_url'));
    }

    #[Test]
    public function it_returns_null_for_empty_optional_strings(): void
    {
        $settings = new ProviderSettings([
            'app_url' => '',
        ]);

        $this->assertNull($settings->optionalString('app_url'));
    }

    #[Test]
    public function it_returns_a_string_with_a_default(): void
    {
        $settings = new ProviderSettings([]);

        $this->assertSame('public', $settings->string('bucket', 'public'));
    }

    #[Test]
    public function it_prefers_configured_values_over_defaults(): void
    {
        $settings = new ProviderSettings([
            'bucket' => 'private',
        ]);

        $this->assertSame('private', $settings->string('bucket', 'public'));
    }

    #[Test]
    public function it_returns_required_int_values(): void
    {
        $settings = new ProviderSettings([
            'quality' => 80,
        ]);

        $this->assertSame(80, $settings->requireInt('quality'));
    }

    #[Test]
    public function it_coerces_numeric_strings_for_required_int_values(): void
    {
        $settings = new ProviderSettings([
            'quality' => '80',
        ]);

        $this->assertSame(80, $settings->requireInt('quality'));
    }

    #[Test]
    public function it_throws_when_a_required_int_is_missing(): void
    {
        $settings = new ProviderSettings([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Image provider setting [quality] is required.');

        $settings->requireInt('quality');
    }

    #[Test]
    public function it_throws_when_a_required_int_is_empty(): void
    {
        $settings = new ProviderSettings([
            'quality' => '',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Image provider setting [quality] is required.');

        $settings->requireInt('quality');
    }

    #[Test]
    public function it_throws_when_a_required_int_is_invalid(): void
    {
        $settings = new ProviderSettings([
            'quality' => 'high',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Image provider setting [quality] is required.');

        $settings->requireInt('quality');
    }

    #[Test]
    public function it_returns_optional_int_values(): void
    {
        $settings = new ProviderSettings([
            'quality' => 80,
        ]);

        $this->assertSame(80, $settings->optionalInt('quality'));
    }

    #[Test]
    public function it_returns_null_for_missing_optional_ints(): void
    {
        $settings = new ProviderSettings([]);

        $this->assertNull($settings->optionalInt('quality'));
    }

    #[Test]
    public function it_returns_null_for_empty_optional_ints(): void
    {
        $settings = new ProviderSettings([
            'quality' => '',
        ]);

        $this->assertNull($settings->optionalInt('quality'));
    }

    #[Test]
    public function it_returns_null_for_invalid_optional_ints(): void
    {
        $settings = new ProviderSettings([
            'quality' => 'high',
        ]);

        $this->assertNull($settings->optionalInt('quality'));
    }
}
