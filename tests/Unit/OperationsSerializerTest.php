<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Tests\Unit;

use Eolica\LaravelImage\OperationsSerializer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OperationsSerializerTest extends TestCase
{
    #[Test]
    public function it_returns_empty_string_for_empty_operations(): void
    {
        $serializer = new OperationsSerializer(
            joinWith: '/',
            formatter: fn (string $key, mixed $value): string => "{$key}:{$value}",
        );

        $this->assertSame('', $serializer->serialize([]));
    }

    #[Test]
    public function it_serializes_operations_as_path_segments(): void
    {
        $serializer = new OperationsSerializer(
            joinWith: '/',
            formatter: fn (string $key, mixed $value): string => "{$key}:{$value}",
        );

        $result = $serializer->serialize([
            'rt' => 'fill',
            'w' => 640,
        ]);

        $this->assertSame('rt:fill/w:640', $result);
    }

    #[Test]
    public function it_joins_segments_with_the_configured_separator(): void
    {
        $serializer = new OperationsSerializer(
            joinWith: '|',
            formatter: fn (string $key, mixed $value): string => "{$key}={$value}",
        );

        $result = $serializer->serialize([
            'a' => 1,
            'b' => 2,
        ]);

        $this->assertSame('a=1|b=2', $result);
    }
}
