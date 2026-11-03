<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

final readonly class OperationsMapper
{
    /**
     * @param  array<string, string>  $keyMap
     * @param  array<string, array<string, array<string, mixed>|string>>  $valueMap
     */
    public function __construct(
        private array $keyMap,
        private array $valueMap = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function map(ImageModifiers $modifiers): array
    {
        $params = [];

        foreach ($modifiers->toArray() as $key => $value) {
            if (isset($this->valueMap[$key][$value])) {
                $mapped = $this->valueMap[$key][$value];

                if (is_array($mapped)) {
                    foreach ($mapped as $mappedKey => $mappedValue) {
                        $params[$mappedKey] = $mappedValue;
                    }

                    continue;
                }

                $params[$this->keyMap[$key] ?? $key] = $mapped;

                continue;
            }

            if (!array_key_exists($key, $this->keyMap)) {
                continue;
            }

            $params[$this->keyMap[$key]] = $value;
        }

        return $params;
    }
}
