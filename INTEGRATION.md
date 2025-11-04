# Integration Guide

How to integrate contenir-asset 2.0 with your Mezzio application.

## Step 1: Install Package

```bash
composer require contenir/asset:^2.0
```

Or for local development:

```json
// composer.json
{
    "repositories": [
        {
            "type": "path",
            "url": "../contenir-asset",
            "options": {"symlink": true}
        }
    ],
    "require": {
        "contenir/asset": "@dev"
    }
}
```

## Step 2: Add to ConfigAggregator

```php
// config/config.php
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\PhpFileProvider;

$aggregator = new ConfigAggregator([
    \Contenir\Asset\ConfigProvider::class,  // Add this
    \Contenir\Workflow\ConfigProvider::class,
    \Contenir\Diagnostics\ConfigProvider::class,
    // ... rest of your providers
]);
```

## Step 3: Add Routes

```php
// config/routes.php
return static function (Mezzio\Application $app, ...) {
    // ... your routes

    // Add asset image resize route
    require __DIR__ . '/../vendor/contenir/asset/config/routes.php';
    $app->pipe(/* routes */);
};
```

Or manually:

```php
use Contenir\Asset\Handler\ImageResizeHandler;

$app->get(
    '/cache/images/{dimensions:[0-9]+x[0-9]*}/{image_path:.+}\.{format:jpg|jpeg|png|webp|avif}',
    ImageResizeHandler::class,
    'asset.image.resize'
);
```

## Step 4: Database Migration

```bash
mysql -u your_user -p your_database < vendor/contenir/asset/config/migration.sql
```

## Step 5: Configuration (Optional)

Create `config/autoload/asset.global.php`:

```php
<?php
return [
    'contenir_asset' => [
        'processor' => [
            'imagemagick_path' => '/usr/local/bin/convert',
            'quality' => [
                'jpg' => 85,
                'webp' => 85,
                'avif' => 75,
            ],
            'optimization' => [
                'strip_metadata' => true,
                'progressive' => true,
                'colorspace' => 'sRGB',
            ],
        ],
        'cache' => [
            'path' => '/cache/images',
            'public_path' => './public',
        ],
        'default_formats' => ['avif', 'webp', 'jpg'],
        'presets' => [
            'hero' => [
                'sizes' => [
                    '1920x1080' => '1920w',
                    '1280x720' => '1280w',
                    '960x540' => '960w',
                    '640x360' => '640w',
                ],
                'crop' => 'cover',
                'formats' => ['avif', 'webp', 'jpg'],
            ],
            'responsive' => [
                'sizes' => [
                    '1600x900' => '1600w',
                    '1200x675' => '1200w',
                    '800x450' => '800w',
                    '400x225' => '400w',
                ],
                'crop' => 'cover',
            ],
        ],
    ],
];
```

## Step 6: Integration with AssetsPlugin (Optional)

If using with Contenir's metadata/settings system:

```php
// In your AssetsPlugin.php

use App\Metadata\Settings\SettingDefinition;
use App\Metadata\Settings\SettingsKeys;

public function getSettingsDefinitions(): array
{
    return [
        // ... existing settings ...

        new SettingDefinition(
            SettingsKeys::ASSETS_IMAGE_PRESETS,
            SettingDefinition::TYPE_ARRAY,
            [
                'hero' => [
                    'sizes' => ['1920x1080' => '1920w', '960x540' => '960w'],
                    'crop' => 'cover',
                ],
            ],
            'Image Presets',
            'Responsive image size configurations',
            'Assets'
        ),
    ];
}

public function execute(PhpRenderer $view, ?ResourceInterface $resource, array $config): void
{
    // ... existing asset loading ...

    // Configure image helpers
    $this->configureImageHelpers($view, $config);
}

private function configureImageHelpers(PhpRenderer $view, array $config): void
{
    $imagePresets = SettingsKeys::get(
        $config,
        SettingsKeys::path(SettingsKeys::NAMESPACE_ASSETS, SettingsKeys::ASSETS_IMAGE_PRESETS),
        []
    );

    if ($view->plugin('assetSrcSet') instanceof \Contenir\Asset\View\Helper\AssetSrcSet) {
        $view->plugin('assetSrcSet')->setPresets($imagePresets);
    }
}
```

## Step 7: Use in Templates

### Replace old implementation:

**Before (contenir-asset 1.x):**
```php
<?php if (!empty($project['image_lg'])): ?>
<section class="section section__image">
    <div class="container">
        <img src="<?= $project['image_lg'] ?>"
             class="img img--cover"
             alt="<?= $project['title'] ?>">
    </div>
</section>
<?php endif; ?>
```

**After (contenir-asset 2.0):**
```php
<?php if (!empty($project['image_lg'])): ?>
<section class="section section__image">
    <div class="container">
        <?= $this->assetSrcSet($project['image_lg'], [
            'preset' => 'responsive',
            'img' => [
                'alt' => $project['title'],
                'class' => 'img img--cover',
                'loading' => 'lazy',
            ],
            'sizes' => '(max-width: 768px) 100vw, 80vw'
        ]) ?>
    </div>
</section>
<?php endif; ?>
```

### Hero image with high priority:

```php
<?php if (!empty($resource['image_lg'])): ?>
<?= $this->assetSrcSet($resource, [
    'preset' => 'hero',
    'img' => [
        'alt' => $resource['title'],
        'class' => 'hero-image',
        'loading' => 'eager',
        'fetchpriority' => 'high',
    ],
    'sizes' => '100vw'
]) ?>
<?php endif; ?>
```

### With breakpoints (art direction):

```php
<?= $this->assetSrcSet($image, [
    'breakpoints' => [
        'mobile' => [
            'width' => 768,
            'sizes' => ['600x900' => '600w', '400x600' => '400w'],
            'crop' => 'cover',
        ],
        'desktop' => [
            'width' => 1024,
            'sizes' => ['1920x1080' => '1920w', '1280x720' => '1280w'],
            'crop' => 'cover',
        ],
    ],
    'img' => ['alt' => 'Responsive image'],
    'sizes' => '(max-width: 768px) 100vw, 80vw',
]) ?>
```

## Step 8: Create Cache Directory

```bash
mkdir -p public/cache/images
chmod 755 public/cache/images
```

## Testing

Test the image generation:

1. Visit a page with images
2. Check browser dev tools → Network tab
3. Look for `/cache/images/...` requests
4. Verify AVIF is served to supporting browsers
5. Check `public/cache/images/` for generated files

## Performance Optimization

### Preload LCP Images

In your head plugin or template:

```php
// Preload hero image for Largest Contentful Paint optimization
$view->headLink()->headLink([
    'rel' => 'preload',
    'as' => 'image',
    'href' => $this->assetUrl($heroImage, ['size' => '1920x1080', 'format' => 'avif']),
    'type' => 'image/avif',
    'imagesrcset' => '...',  // Build srcset
    'imagesizes' => '100vw',
    'fetchpriority' => 'high',
], 'PREPEND');
```

### Security: Whitelist Dimensions

```php
// config/autoload/asset.global.php
return [
    'contenir_asset' => [
        'allowed_dimensions' => [
            '1920x1080', '1280x720', '960x540',  // Hero sizes
            '1600x900', '800x450', '400x225',    // Responsive sizes
            '400x400', '200x200',                 // Thumbnails
        ],
    ],
];
```

## Troubleshooting

### Images not generating

1. Check ImageMagick is installed: `convert --version`
2. Verify path in config: `'imagemagick_path' => '/usr/local/bin/convert'`
3. Check permissions: `chmod 755 public/cache/images`
4. Check error logs

### Wrong image format served

1. Check browser dev tools → Accept header
2. Verify format order in config: `'default_formats' => ['avif', 'webp', 'jpg']`
3. Test with different browsers

### Performance issues

1. Pre-generate common sizes on upload (future feature)
2. Use CDN for cached images
3. Enable dimension whitelist
4. Check ImageMagick optimization settings

## Next Steps

- Add focal point UI to your CMS for setting focal_x/focal_y
- Create additional presets for your use cases
- Consider build-time generation for production
- Integrate with CDN for better performance