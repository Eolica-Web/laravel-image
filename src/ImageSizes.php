<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

use InvalidArgumentException;

final readonly class ImageSizes
{
    /**
     * @param  list<array{screen: string|null, value: int, unit: 'vw'|'px'}>  $entries
     */
    private function __construct(public array $entries) {}

    public static function parse(string $sizes): self
    {
        /** @var array<string, array{screen: string|null, value: int, unit: 'vw'|'px'}> $entries */
        $entries = [];

        $tokens = preg_split('/[\s,]+/', trim($sizes), flags: PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($tokens as $token) {
            if (preg_match('/^(?:([^:]+):)?(\d+)(vw|px)?$/', $token, $matches) !== 1 || (int) $matches[2] === 0) {
                throw new InvalidArgumentException("Invalid image size [{$token}].");
            }

            $screen = $matches[1] !== '' ? $matches[1] : null;

            $entries[$screen ?? ''] = [
                'screen' => $screen,
                'value' => (int) $matches[2],
                'unit' => ($matches[3] ?? '') === 'vw' ? 'vw' : 'px',
            ];
        }

        if ($entries === []) {
            throw new InvalidArgumentException('Image sizes must not be empty.');
        }

        $entries[''] ??= ['screen' => null, 'value' => 100, 'unit' => 'vw'];

        return new self(array_values($entries));
    }
}
