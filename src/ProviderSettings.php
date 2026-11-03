<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

use InvalidArgumentException;

final readonly class ProviderSettings
{
    /**
     * @param  array<string, mixed>  $values
     */
    public function __construct(private array $values) {}

    public function requireString(string $key): string
    {
        $value = $this->values[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new InvalidArgumentException("Image provider setting [{$key}] is required.");
        }

        return $value;
    }

    public function optionalString(string $key): ?string
    {
        $value = $this->values[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function string(string $key, string $default = ''): string
    {
        return $this->optionalString($key) ?? $default;
    }

    public function requireInt(string $key): int
    {
        $value = $this->parseInt($this->values[$key] ?? null);

        if ($value === null) {
            throw new InvalidArgumentException("Image provider setting [{$key}] is required.");
        }

        return $value;
    }

    public function optionalInt(string $key): ?int
    {
        return $this->parseInt($this->values[$key] ?? null);
    }

    private function parseInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }
}
