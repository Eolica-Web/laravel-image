<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

use Closure;

final readonly class OperationsSerializer
{
    /**
     * @param  Closure(string, mixed): string  $formatter
     */
    public function __construct(
        private string $joinWith,
        private Closure $formatter,
    ) {}

    /**
     * @param  array<string, mixed>  $operations
     */
    public function serialize(array $operations): string
    {
        if ($operations === []) {
            return '';
        }

        $parts = [];

        foreach ($operations as $key => $value) {
            $parts[] = ($this->formatter)($key, $value);
        }

        return implode($this->joinWith, $parts);
    }
}
