# laravel-image

Nuxt Image-inspired image URL generation for Laravel. Switch between CDN and on-the-fly resize providers via configuration, and render responsive images with Blade components.

## Requirements

- PHP 8.4+
- Laravel 13+
- Optional: [league/glide](https://github.com/thephpleague/glide) (^3.0) for the Glide provider

## Installation

The package is auto-discovered via Composer. Publish the config when wiring a new app:

```bash
php artisan vendor:publish --tag=laravel-image-config
```

> **Required setup:** Publish `config/image.php` and wire it for your environment. The package stub ships with all provider entries commented out and no default for `IMAGE_PROVIDER` — an unpublished or unconfigured config will fail at runtime. See [Configuration](#configuration) for a complete example.

## Configuration

Configuration lives in `config/image.php` (publish with `--tag=laravel-image-config`).

### `IMAGE_PROVIDER`

The `IMAGE_PROVIDER` environment variable selects the default provider. It must match a key in the `providers` array.

| Value | Provider | Typical use |
|-------|----------|-------------|
| `imgproxy` | [ImgproxyImageProvider](src/Providers/ImgproxyImageProvider.php) | Signed URLs; imgproxy reads originals from S3 (`s3://bucket/key`) |
| `bunny` | [BunnyImageProvider](src/Providers/BunnyImageProvider.php) | Bunny CDN query-string transforms |
| `glide` | [GlideImageProvider](src/Providers/GlideImageProvider.php) | Signed URLs via League Glide (requires `composer require league/glide`) |

```dotenv
IMAGE_PROVIDER=imgproxy
```

Individual Blade components can override the default with a `provider` prop (e.g. `provider="bunny"`).

### Recommended `config/image.php`

```php
<?php

use Eolica\LaravelImage\Providers\BunnyImageProvider;
use Eolica\LaravelImage\Providers\GlideImageProvider;
use Eolica\LaravelImage\Providers\ImgproxyImageProvider;

return [
    'provider' => env('IMAGE_PROVIDER', 'imgproxy'),

    'screens' => [
        'xs' => 320,
        'sm' => 640,
        'md' => 768,
        'lg' => 1024,
        'xl' => 1280,
        '2xl' => 1536,
    ],

    'densities' => [1, 2],

    'providers' => [
        'imgproxy' => [
            'driver' => ImgproxyImageProvider::class,
            'base_url' => env('IMGPROXY_BASE_URL', 'http://localhost:8080'),
            'key' => env('IMGPROXY_KEY'),
            'salt' => env('IMGPROXY_SALT'),
            'bucket' => env('AWS_BUCKET', 'public'),
            'defaults' => [
                'quality' => 80,
            ],
        ],

        'glide' => [
            'driver' => GlideImageProvider::class,
            'base_path' => '/images',
            'sign_key' => env('GLIDE_SIGN_KEY'),
            'app_url' => env('APP_URL'),
        ],

        'bunny' => [
            'driver' => BunnyImageProvider::class,
            'base_url' => env('BUNNY_BASE_URL', 'https://cdn.example.com'),
            'defaults' => [
                'quality' => 80,
            ],
        ],
    ],

    'presets' => [
        'og' => [
            'width' => 1200,
            'height' => 630,
            'fit' => 'cover',
            'format' => 'webp',
            'quality' => 80,
        ],
    ],
];
```

### Environment variables

| Variable | Used by | Description |
|----------|---------|-------------|
| `IMAGE_PROVIDER` | All | Default provider key (`imgproxy`, `bunny`, or `glide`) |
| `IMGPROXY_BASE_URL` | imgproxy | imgproxy server URL (default `http://localhost:8080`) |
| `IMGPROXY_KEY` | imgproxy | Hex-encoded signing key (64 hex chars / 32 bytes) |
| `IMGPROXY_SALT` | imgproxy | Hex-encoded signing salt (64 hex chars / 32 bytes) |
| `AWS_BUCKET` | imgproxy | S3 bucket name imgproxy reads from (imgproxy `bucket` setting) |
| `AWS_ACCESS_KEY_ID` | imgproxy server | S3 credentials configured on the imgproxy instance |
| `AWS_SECRET_ACCESS_KEY` | imgproxy server | S3 credentials configured on the imgproxy instance |
| `AWS_DEFAULT_REGION` | imgproxy server | S3 region configured on the imgproxy instance |
| `GLIDE_SIGN_KEY` | glide | HMAC signing key for Glide URLs |
| `BUNNY_BASE_URL` | bunny | Bunny CDN origin (e.g. `https://cdn.example.com`) |

Provider-level `defaults` (e.g. `quality => 80`) are the least specific layer when building transform URLs. Presets and explicit props override defaults (most specific wins).

### Named presets (`presets`)

Define reusable modifier bundles in `presets` and reference them via the `preset` prop in Blade or the `preset` argument in PHP:

```php
'presets' => [
    'og' => [
        'width' => 1200,
        'height' => 630,
        'fit' => 'cover',
        'format' => 'webp',
        'quality' => 80,
    ],
],
```

**Merge order** (least → most specific):

```
provider defaults  →  preset  →  explicit props / ImageModifiers
```

Display attributes on `ResolvedImage` (`width`, `height`, `format`) come from **preset + props only** — not provider defaults like `quality`.

### Responsive breakpoints (`screens`)

The `screens` array defines breakpoint names used by `<x-image::source>` and the `sizes` prop. Breakpoints are mobile-first, like Tailwind: each key maps to a min-width media query, so a screen applies from that width up:

- `md` with value `768` → `(min-width: 768px)`

Screen widths are also the viewport widths `sizes` generates candidates for (see [Responsive images](#responsive-images-sizes-and-densities)).

### Default densities (`densities`)

The pixel densities generated for `sizes` when a component does not set `densities`. Defaults to `[1, 2]`.

## imgproxy provider

The imgproxy driver builds signed URLs that point at an [imgproxy](https://imgproxy.net/) server. imgproxy fetches originals from S3 using paths like `s3://{bucket}/{key}` — the bucket comes from provider config (`AWS_BUCKET` by default).

Transform options are emitted as Nuxt-style path segments (via `resolveModifiers()` + `OperationsSerializer`), for example:

```
/{signature}/rt:fit/w:640/h:320/q:90/f:webp/plain/s3://public/images/web/hero.webp
```

Cover crops use `rt:fill` with gravity (`g:ce` or `g:nowe`). Single-dimension resize uses `0` on the auto axis (`rt:fit/w:640/h:0`).

### Requirements

1. A running imgproxy instance with S3 access to the bucket configured in `config/image.php`.
2. Matching signing key and salt in Laravel (`IMGPROXY_KEY`, `IMGPROXY_SALT`) and on the imgproxy server.
3. Original files already stored in the bucket at the paths referenced by your `src` values.

This package generates URLs only; uploading originals to S3 (or any S3-compatible store) is the consuming application's responsibility.

### Generate key and salt

imgproxy requires 64-character hex strings (32 bytes each):

```bash
openssl rand -hex 32   # → IMGPROXY_KEY
openssl rand -hex 32   # → IMGPROXY_SALT
```

Set the same values in your Laravel `.env` and in the imgproxy server environment, then restart imgproxy.

### Example `.env`

```dotenv
IMAGE_PROVIDER=imgproxy
IMGPROXY_BASE_URL=http://localhost:8080
IMGPROXY_KEY=…
IMGPROXY_SALT=…
AWS_BUCKET=public
```

Configure S3 credentials and endpoint on the imgproxy server itself (see [imgproxy S3 documentation](https://docs.imgproxy.net/image_sources/amazon_s3)). For local development, any S3-compatible backend (MinIO, RustFS, LocalStack, etc.) works as long as imgproxy can read from it.

### Verify

```bash
curl -I "${IMGPROXY_BASE_URL}/health"
```

Reference bucket-relative paths in Blade — a file stored as `images/hero.webp` in the bucket:

```blade
<x-image::image src="images/hero.webp" width="640" height="320" />
```

## Blade components

Components are registered under the `image` namespace: `<x-image::…>`.

Shared props for `<x-image::image>` and `<x-image::picture>`:

| Prop | Type | Description |
|------|------|-------------|
| `src` | string | **Required.** Path relative to the bucket/CDN root (leading `/` is stripped) |
| `preset` | string | Named modifier bundle from `config('image.presets')` |
| `width` | int/string | Target width in pixels |
| `height` | int/string | Target height in pixels |
| `fit` | string | Resize/crop mode (see below) |
| `format` | string | Output format (e.g. `webp`, `jpg`) |
| `quality` | int/string | Output quality (1–100) |
| `provider` | string | Override the default `IMAGE_PROVIDER` |
| `sizes` | string | Mobile-first rendered sizes (`100vw sm:50vw lg:400px`); renders a width-descriptor `srcset` and `sizes`. See [Responsive images](#responsive-images-sizes-and-densities) |
| `densities` | string | Pixel densities (`1x 2x`); on their own they render a density-descriptor `srcset` |
| `original-width` | int | Width of the original image; `srcset` candidates never exceed it. See [Original size](#original-size) |
| `original-height` | int | Height of the original image; limits `cover` candidates. See [Original size](#original-size) |
| `preload` | bool | Publish a `<link rel="preload" as="image">` via `ImagePreloadPublisher`, including `imagesrcset` and `imagesizes` (default `false`) |

Additional HTML attributes (`alt`, `class`, `loading`, etc.) are forwarded to the rendered `<img>`. A `srcset` attribute is ignored: use `sizes` or `densities` instead.

### Fit modes

| Value | Behavior |
|-------|----------|
| `cover` | Crop to fill dimensions, center gravity |
| `cover-center` | Same as `cover` |
| `cover-top-left` | Crop to fill, top-left gravity |
| *(unset)* | Fit within dimensions without cropping |

### `<x-image::image>`

Renders a single `<img>` with a provider-generated URL.

```blade
<x-image::image
    src="images/web/hero.webp"
    width="640"
    height="320"
    fit="cover"
    format="webp"
    alt="Hero"
    loading="lazy"
    class="w-full object-cover"
/>
```

Set `:preload="true"` to publish a `<link rel="preload" as="image">` for the resolved URL, with its `srcset` and `sizes` as `imagesrcset` and `imagesizes`. By default this uses `StackImagePreloadPublisher`, which pushes onto the Blade `@stack('head')` — your layout must include `@stack('head')`. See [Preload publishing](#preload-publishing) to integrate with a custom head manager.

Use a preset for common sizes (e.g. Open Graph):

```blade
<x-image::image src="images/web/hero.webp" preset="og" alt="Hero" />
```

### `<x-image::picture>`

Renders a `<picture>` wrapper. The fallback `<img>` uses the props on the picture element and covers the smallest screens; `<x-image::source>` children override it from their breakpoint up.

The browser uses the first `<source>` whose media query matches, so list sources from the largest screen to the smallest.

```blade
<x-image::picture
    src="images/web/hero.webp"
    width="640"
    height="800"
    fit="cover"
    format="webp"
    alt="Hero"
    loading="lazy"
    class="w-full object-cover"
>
    <x-image::source screen="lg" width="1280" height="640" fit="cover" format="webp" />
    <x-image::source screen="md" width="1024" height="640" fit="cover" format="webp" />
</x-image::picture>
```

Set `:preload="true"` to preload every `<source>` and the fallback `<img>`, each with a `media` range that does not overlap the others, so the browser only downloads the one it will render. Pair with `fetchpriority="high"` and `loading="eager"` for LCP heroes. See [Preload publishing](#preload-publishing).

### `<x-image::source>`

Must be nested inside `<x-image::picture>`. Inherits `src`, optional `provider`, and optional `preset` from the parent via `@aware`. An explicit `preset` on the source overrides the parent.

| Prop | Type | Description |
|------|------|-------------|
| `screen` | string | **Required.** Breakpoint key from `config('image.screens')` |
| `preset` | string | Override parent preset (inherits from picture when omitted) |
| `width` | int/string | **Required.** Width for this breakpoint |
| `height` | int/string | **Required.** Height for this breakpoint |
| `fit` | string | Fit mode |
| `format` | string | Output format |
| `quality` | int/string | Output quality |
| `sizes` | string | Rendered sizes for this source; candidates start at its `screen` |
| `densities` | string | Pixel densities for this source |

`sizes` and `densities` are not inherited from the picture; `original-width` and `original-height` are, since every source crops the same image. Renders `<source media="(min-width: …px)" srcset="…" />`, plus `sizes` when set. Additional HTML attributes (for example `type`) are forwarded; `media` and `srcset` attributes are ignored, since they come from `screen`, `sizes` and `densities`.

## Responsive images (`sizes` and `densities`)

Without `sizes` or `densities`, components render a single URL. With them, they render a `srcset` and let the browser pick the smallest candidate that covers the rendered width times the device pixel ratio.

### Fixed-size images: `densities`

For images that always render at the same CSS size (avatars, logos), list the densities to generate. `width` and `height` are multiplied by each one:

```blade
<x-image::image src="images/avatar.webp" width="64" height="64" fit="cover" densities="1x 2x" />
```

```html
<img src="…/w:64/h:64…" srcset="…/w:64/h:64… 1x, …/w:128/h:128… 2x" width="64" height="64">
```

Densities accept `1x 2x`, `x1 x2` (Nuxt style), commas and fractions (`1.5x`). A width or a height is required.

### Fluid images: `sizes`

For images whose width depends on the layout, describe the rendered width per screen, mobile-first:

```blade
<x-image::image src="images/cover.webp" width="800" height="600" fit="cover" sizes="100vw sm:50vw lg:400px" />
```

- An entry without a screen applies from 0px; `screen:value` applies from that screen up, until the next larger entry. Without an unprefixed entry, smaller screens fall back to `100vw`.
- Values are `vw` (fluid) or `px` (fixed); a bare number is `px`.

This renders:

```html
<img src="…/w:800/h:600…"
     srcset="…/w:320/h:240… 320w, …/w:384/h:288… 384w, …/w:400/h:300… 400w, …/w:512/h:384… 512w,
             …/w:640/h:480… 640w, …/w:768/h:576… 768w, …/w:800/h:600… 800w, …/w:1024/h:768… 1024w,
             …/w:1280/h:960… 1280w"
     sizes="(min-width: 1024px) 400px, (min-width: 640px) 50vw, 100vw"
     width="800" height="600">
```

With the default screens and densities, candidates are computed per range and multiplied by each density (the `densities` prop, or `config('image.densities')`):

- A `px` range contributes its own width: `400`.
- A `vw` range contributes its width at every configured screen inside it. `100vw` up to `sm` gives `320` (at `xs`) and `640`; `50vw` from `sm` to `lg` gives `384` (at `md`) and `512` (at `lg`). The open-ended last range uses every larger screen, or its own screen when there is none.

When both `width` and `height` are set, each candidate keeps their aspect ratio; otherwise candidates only set the width. `src`, `width` and `height` keep the given values, for the fallback and to reserve layout space.

### Original size

Providers do not enlarge images, so a candidate wider than the original would be served at the original size under a larger descriptor. Pass the original dimensions to cap the candidates:

```blade
<x-image::image
    :src="$media->path"
    width="400"
    height="300"
    fit="cover"
    sizes="100vw sm:50vw lg:30vw"
    :original-width="$media->width"
    :original-height="$media->height"
/>
```

- Width candidates above the largest width the original allows are replaced by one candidate at that width: `…, 922w, 960w` instead of `…, 922w, 1024w, 1280w` for a 960px wide original.
- With `cover`, the original height also limits the width, since the crop keeps its aspect ratio: a 4:5 crop of a 960×720 original is at most 576px wide.
- Density candidates stop at the original too, with the real density as descriptor: `1x, 1.6x` instead of `1x, 2x, 3x`. The first density is always kept.

### Art direction with `sizes`

On `<x-image::source>`, candidates only cover viewports from its `screen` up, so a desktop crop gets no mobile-sized variants. The fallback `<img>` candidates stop at the smallest source `screen`, since the browser never renders it above that:

```blade
<x-image::picture src="images/cover.webp" width="800" height="1000" fit="cover" sizes="100vw" alt="…">
    <x-image::source screen="lg" width="1600" height="900" fit="cover" sizes="100vw" />
</x-image::picture>
```

Mobile gets a 4:5 crop from the `<img>`, with candidates for viewports up to `lg`; from `lg` up the `<source>` serves a 16:9 crop, each with its own `srcset`.

## Preload publishing

When `:preload="true"` is set on `<x-image::image>` or `<x-image::picture>`, `ImagePreloader` passes an `ImagePreload` (`url`, `srcset`, `sizes`, `media`) to `ImagePreloadPublisher` for each image to preload.

On a picture, sources are matched like the browser does: in document order, the first matching `media` wins. Each preload gets the range where its image is actually rendered:

```blade
<x-image::picture src="images/web/hero.webp" width="640" height="800" :preload="true">
    <x-image::source screen="lg" width="1600" height="900" />
    <x-image::source screen="md" width="1024" height="768" />
</x-image::picture>
```

```html
<link rel="preload" as="image" href="…?w=1600&h=900" media="(min-width: 1024px)" />
<link rel="preload" as="image" href="…?w=1024&h=768" media="(min-width: 768px) and (max-width: 1023.98px)" />
<link rel="preload" as="image" href="…?w=640&h=800" media="(max-width: 767.98px)" />
```

A source that an earlier source always wins over is not preloaded.

The default implementation is `PreloadPublishers\StackImagePreloadPublisher`, which pushes a `<link rel="preload" as="image">` onto a Blade stack.

Configure in `config/image.php`:

```php
'preload' => [
    'publisher' => \Eolica\LaravelImage\PreloadPublishers\StackImagePreloadPublisher::class,
    'stack' => 'head',
],
```

### Custom publisher

Implement `ImagePreloadPublisher` in your app and bind it (or set `image.preload.publisher`):

```php
use Eolica\LaravelImage\ImagePreload;
use Eolica\LaravelImage\ImagePreloadPublisher;

final readonly class MyHeadPreloadPublisher implements ImagePreloadPublisher
{
    public function __construct(private MyHeadManager $head) {}

    public function preload(ImagePreload $preload): void
    {
        $this->head->addLink(array_filter([
            'rel' => 'preload',
            'as' => 'image',
            'href' => $preload->url,
            'imagesrcset' => $preload->srcset,
            'imagesizes' => $preload->sizes,
            'media' => $preload->media,
        ]));
    }
}
```

```php
// AppServiceProvider::register()
$this->app->bind(ImagePreloadPublisher::class, MyHeadPreloadPublisher::class);
```

The ev-mallorca host app uses `App\Image\HeadImagePreloadPublisher` with `Seo\Head\Head` so preloads render via `{!! $head['headTags'] !!}` instead of `@stack('head')`.

## Programmatic usage

Inject or resolve `ImageManager`. **`resolve()`** is the primary API — it returns a `ResolvedImage` DTO with the provider URL and display attributes (width, height, format from preset + explicit modifiers, excluding provider defaults):

```php
use Eolica\LaravelImage\ImageManager;
use Eolica\LaravelImage\ImageModifiers;

$resolved = app(ImageManager::class)->resolve(
    'images/web/hero.webp',
    ImageModifiers::fromProps(width: 1200, height: 630, fit: 'cover', format: 'webp'),
);

$resolved->url;     // provider transform URL (meta tags, preload, etc.)
$resolved->width;   // display width
$resolved->height;  // display height
$resolved->format;  // display format

// Responsive candidates (null without sizes or densities)
$resolved = app(ImageManager::class)->resolve(
    'images/web/hero.webp',
    ImageModifiers::fromProps(width: 800, height: 600, fit: 'cover'),
    sizes: '100vw lg:400px',
    densities: '1x 2x',  // optional with sizes; defaults to config('image.densities')
    screen: 'lg',        // optional: only viewports from this screen up (for <source>)
    untilScreen: 'xl',   // optional: only viewports below this screen (for a picture fallback)
    originalWidth: 960,  // optional: caps candidates at the original size
    originalHeight: 720,
);

$resolved->srcset;  // "… 400w, … 640w, …"
$resolved->sizes;   // "(min-width: 1024px) 400px, 100vw"

// Named preset with optional overrides
$resolved = app(ImageManager::class)->resolve(
    'images/web/hero.webp',
    ImageModifiers::fromProps(width: 800),
    preset: 'og',
);

$mediaQuery = app(ImageManager::class)->mediaQuery('md'); // "(min-width: 768px)"
```

**`url()`** remains a thin wrapper for one-liners:

```php
$url = app(ImageManager::class)->url(
    'images/web/hero.webp',
    ImageModifiers::fromProps(width: 1200),
    preset: 'og',
);
```

Pass a third argument to override the provider: `->resolve($src, $modifiers, 'bunny')`.

## Custom providers

Implement `Eolica\LaravelImage\ImageProvider` and register a factory on the registry. Registration must happen **after** the package registers its built-in drivers, using `afterResolving`:

```php
use Eolica\LaravelImage\ImageManager;
use Eolica\LaravelImage\ImageModifiers;
use Eolica\LaravelImage\ImageProvider;
use Eolica\LaravelImage\ImageProviderRegistry;
use Eolica\LaravelImage\ProviderSettings;

final readonly class AcmeImageProvider implements ImageProvider
{
    public function __construct(private string $baseUrl) {}

    public function url(string $src, ImageModifiers $modifiers): string
    {
        return rtrim($this->baseUrl, '/').'/'.ltrim($src, '/');
    }
}
```

In `AppServiceProvider::register()`:

```php
$this->app->afterResolving(ImageProviderRegistry::class, function (ImageProviderRegistry $registry): void {
    $registry->register(AcmeImageProvider::class, fn (ProviderSettings $settings): AcmeImageProvider => new AcmeImageProvider(
        baseUrl: $settings->requireString('base_url'),
    ));
});
```

Add the provider to `config/image.php`:

```php
'providers' => [
    // …
    'acme' => [
        'driver' => AcmeImageProvider::class,
        'base_url' => env('ACME_CDN_URL'),
        'defaults' => [],
    ],
],
```

Use `ProviderSettings` helpers in the factory:

- `requireString($key)` — required non-empty string
- `optionalString($key)` — string or `null`
- `string($key, $default)` — string with fallback
- `requireInt($key)` — required integer (accepts int or numeric string)
- `optionalInt($key)` — integer or `null`

The `driver` and `defaults` keys in config are consumed by `ImageManagerFactory` and are **not** passed to your factory callback. Only provider-specific keys (for example `base_url`, `key`, `salt`) are forwarded via `ProviderSettings`.

## Architecture

```
config/image.php
    → ImageManagerFactory
        → ImageProviderRegistry (resolve driver classes)
            → ImageProvider instances
                → ImageManager
                    → Blade components (picture, image, source)
```

Built-in drivers are registered in [ImageServiceProvider](src/ImageServiceProvider.php). The core classes (`ImageManager`, `ImageModifiers`, `ImageScreens`, `ImagePresets`, `ResolvedImage`, etc.) are framework-agnostic and intended for eventual extraction to a standalone `eolica/image` package.

## Testing

```bash
composer test
composer analyse
```

## Known limitations

- **Published config stub** — Provider entries are commented out and `IMAGE_PROVIDER` has no default. Publish `config/image.php`, uncomment the providers you need, and set `IMAGE_PROVIDER` in `.env`.

## License

MIT © [Eolica Digital](LICENSE.md)
