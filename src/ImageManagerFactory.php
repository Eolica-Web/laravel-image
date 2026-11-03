<?php

declare(strict_types=1);

namespace Eolica\LaravelImage;

use Illuminate\Contracts\Config\Repository;
use InvalidArgumentException;

final readonly class ImageManagerFactory
{
    /** @var list<string> */
    private const PROVIDER_META_KEYS = ['driver', 'defaults'];

    public function __construct(
        private Repository $config,
        private ImageProviderRegistry $registry,
    ) {}

    public function make(): ImageManager
    {
        /** @var array{provider?: mixed, screens?: array<string, int>, densities?: list<int|float>, providers?: array<string, array<string, mixed>>, presets?: array<string, array{width?: int, height?: int, fit?: string, format?: string, quality?: int}>} $imageConfig */
        $imageConfig = $this->config->get('image', []);

        $defaultProvider = $imageConfig['provider'] ?? '';

        if (! is_string($defaultProvider) || $defaultProvider === '') {
            throw new InvalidArgumentException('Image provider is not configured.');
        }

        /** @var array<string, array<string, mixed>> $providerConfigs */
        $providerConfigs = $imageConfig['providers'] ?? [];

        /** @var array<string, ImageProvider> $providers */
        $providers = [];

        /** @var array<string, array<string, mixed>> $providerDefaults */
        $providerDefaults = [];

        foreach ($providerConfigs as $name => $providerConfig) {
            $driver = $providerConfig['driver'] ?? null;

            if (! is_string($driver) || $driver === '') {
                throw new InvalidArgumentException("Image provider [{$name}] is missing a driver.");
            }

            $providers[$name] = $this->registry->resolve($driver, $this->providerSettings($providerConfig));
            $providerDefaults[$name] = $providerConfig['defaults'] ?? [];
        }

        /** @var array<string, int> $screens */
        $screens = $imageConfig['screens'] ?? [];

        /** @var array<string, array{width?: int, height?: int, fit?: string, format?: string, quality?: int}> $presets */
        $presets = $imageConfig['presets'] ?? [];

        $imageScreens = new ImageScreens($screens);

        return new ImageManager(
            defaultProvider: $defaultProvider,
            providers: $providers,
            providerDefaults: $providerDefaults,
            screens: $imageScreens,
            presets: new ImagePresets($presets),
            srcsetBuilder: new ImageSrcsetBuilder(
                $imageScreens,
                ImageDensities::fromList($imageConfig['densities'] ?? [1, 2]),
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $providerConfig
     * @return array<string, mixed>
     */
    private function providerSettings(array $providerConfig): array
    {
        return array_diff_key($providerConfig, array_flip(self::PROVIDER_META_KEYS));
    }
}
