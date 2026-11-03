<?php

declare(strict_types=1);

namespace Eolica\LaravelImage\Providers;

use Eolica\LaravelImage\ImageProvider;
use Eolica\LaravelImage\ImageModifiers;
use Eolica\LaravelImage\OperationsSerializer;
use InvalidArgumentException;

final readonly class ImgproxyImageProvider implements ImageProvider
{
    private OperationsSerializer $serializer;

    public function __construct(
        private string $baseUrl,
        private string $key,
        private string $salt,
        private string $bucket = 'public',
    ) {
        $this->serializer = new OperationsSerializer(
            joinWith: '/',
            formatter: fn (string $key, mixed $value): string => "{$key}:{$value}",
        );
    }

    public function url(string $src, ImageModifiers $modifiers): string
    {
        $operations = $this->resolveModifiers($modifiers);
        $segments = $this->serializer->serialize($operations);
        $source = $this->buildSource($src);

        $path = $segments === ''
            ? '/plain/'.$source
            : '/'.$segments.'/plain/'.$source;

        return $this->signUrl($path);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveModifiers(ImageModifiers $modifiers): array
    {
        $resolved = [];

        $hasDimensions = $modifiers->width !== null || $modifiers->height !== null;
        $isCover = $modifiers->isCover();

        if ($hasDimensions) {
            $resolved['rt'] = $isCover ? 'fill' : 'fit';
            $resolved['w'] = $modifiers->width ?? 0;
            $resolved['h'] = $modifiers->height ?? 0;
        }

        if ($modifiers->fit !== null && $isCover) {
            $resolved['g'] = match ($modifiers->fit) {
                'cover-top-left' => 'nowe',
                default => 'ce',
            };
        }

        if ($modifiers->quality !== null) {
            $resolved['q'] = $modifiers->quality;
        }

        if ($modifiers->format !== null) {
            $resolved['f'] = $modifiers->format;
        }

        $ordered = [];

        foreach (['rt', 'w', 'h', 'g', 'q', 'f'] as $key) {
            if (array_key_exists($key, $resolved)) {
                $ordered[$key] = $resolved[$key];
            }
        }

        return $ordered;
    }

    private function buildSource(string $src): string
    {
        $path = ltrim($src, '/');

        return "s3://{$this->bucket}/{$path}";
    }

    private function signUrl(string $path): string
    {
        $baseUrl = rtrim($this->baseUrl, '/');

        if ($this->key === '' || $this->salt === '') {
            throw new InvalidArgumentException('Imgproxy key and salt must be configured.');
        }

        $key = hex2bin($this->key);
        $salt = hex2bin($this->salt);

        if ($key === false || $salt === false) {
            throw new InvalidArgumentException('Imgproxy key and salt must be valid hex strings.');
        }

        $signature = rtrim(strtr(
            base64_encode(hash_hmac('sha256', $salt.$path, $key, true)),
            '+/',
            '-_',
        ), '=');

        return "{$baseUrl}/{$signature}{$path}";
    }
}
