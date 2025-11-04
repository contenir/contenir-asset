# Contenir Asset 2.0

Responsive image generation and management for Mezzio applications.

## Features

- 🖼️ **On-demand image generation** using ImageMagick
- 🎨 **Modern image formats**: AVIF, WebP, JPEG, PNG
- 📱 **Responsive images**: Automatic srcset and picture element generation
- 🎯 **Focal point cropping**: Intelligent image cropping based on focal points
- 📐 **Breakpoint support**: Different crops and sizes for different viewports
- ⚡ **Performance optimized**: Automatic caching and cache headers
- 🔒 **Security**: Input validation and configurable dimension whitelist
- 🛠️ **Flexible**: Preset configurations or inline options

## Installation

```bash
composer require contenir/asset:^2.0
```

## Configuration

### 1. Add ConfigProvider

```php
// config/config.php
$aggregator = new ConfigAggregator([
    \Contenir\Asset\ConfigProvider::class,
    // ... other providers
]);
```

### 2. Add Routes

```php
// config/routes.php
require __DIR__ . '/../vendor/contenir/asset/config/routes.php';
```

### 3. Run Database Migration

```bash
mysql -u user -p database < vendor/contenir/asset/config/migration.sql
```

### 4. Configure (Optional)

```php
// config/autoload/asset.global.php
return [
    'contenir_asset' => [
        'processor' => [
            'imagemagick_path' => '/usr/local/bin/convert',
            'quality' => [
                'jpg' => 85,
                'webp' => 85,
                'avif' => 75,
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
                    '960x540' => '960w',
                ],
                'crop' => 'cover',
            ],
            // ... more presets
        ],
    ],
];
```

## Usage

### Basic Responsive Image

```php
<?= $this->assetSrcSet($image, 'responsive') ?>
```

Output:
```html
<picture>
    <source type="image/avif" srcset="/cache/images/1600x900/path/image.avif 1600w, ...">
    <source type="image/webp" srcset="/cache/images/1600x900/path/image.webp 1600w, ...">
    <img src="/cache/images/1600x900/path/image.jpg" srcset="..." loading="lazy" decoding="async">
</picture>
```

### With Custom Options

```php
<?= $this->assetSrcSet($project['image_lg'], [
    'preset' => 'hero',
    'img' => [
        'alt' => $project['title'],
        'class' => 'hero-image',
        'loading' => 'eager',
        'fetchpriority' => 'high',
    ],
    'picture' => [
        'class' => 'hero-wrapper',
    ],
    'sizes' => '100vw',
]) ?>
```

### Breakpoints (Art Direction)

```php
<?= $this->assetSrcSet($image, [
    'breakpoints' => [
        'mobile' => [
            'width' => 768,
            'sizes' => ['400x600' => '400w', '800x1200' => '800w'],
            'crop' => 'cover',
            'focal' => [0.5, 0.3], // Focus on upper center
        ],
        'desktop' => [
            'width' => 1024,
            'sizes' => ['1600x900' => '1600w', '2400x1350' => '2400w'],
            'crop' => 'cover',
            'focal' => [0.5, 0.5], // Center focus
        ],
    ],
    'img' => ['alt' => 'Adaptive image', 'class' => 'responsive'],
    'sizes' => '(max-width: 768px) 100vw, 80vw',
]) ?>
```

### With Focal Points from Database

```php
<?php
// Image data from database with focal_x and focal_y
$image = [
    'path' => '/asset/project/image.jpg',
    'focal_x' => 0.7,  // Focus right
    'focal_y' => 0.3,  // Focus top
];
?>

<?= $this->assetSrcSet($image, 'hero') ?>
```

The helper automatically uses focal points from the image data if available.

### Single Image URL

```php
<!-- For manual <img> tags -->
<img src="<?= $this->assetUrl($image, ['size' => '800x600', 'format' => 'webp']) ?>" alt="Image">
```

## Configuration Options

### Image Presets

Define reusable configurations:

```php
'presets' => [
    'hero' => [
        'sizes' => ['1920x1080' => '1920w', '960x540' => '960w'],
        'crop' => 'cover',
        'formats' => ['avif', 'webp', 'jpg'],
    ],
    'thumbnail' => [
        'sizes' => ['400x400' => '2x', '200x200' => '1x'],
        'crop' => 'cover',
    ],
],
```

### Crop Modes

- **cover**: Resize to fill dimensions, crop excess (default)
- **contain**: Resize to fit within dimensions
- **fill**: Resize and add background to fill
- **exact**: Force exact dimensions (may distort)

### Focal Points

Focal points use normalized coordinates (0.0 to 1.0):

- **focal_x**: 0.0 = left, 0.5 = center, 1.0 = right
- **focal_y**: 0.0 = top, 0.5 = center, 1.0 = bottom

Example:
```php
'focal' => [0.7, 0.3]  // Focus on upper-right area
```

### Formats

Supported formats in preference order:
- **avif**: Best compression, modern browsers
- **webp**: Good compression, wide support
- **jpg**: Universal fallback

## Helper Reference

### assetSrcSet($image, $options)

Main helper for generating `<picture>` elements.

**Parameters:**
- `$image`: String path, array, or object with image data
- `$options`: Preset name (string) or configuration array

**Options:**
- `preset`: Preset name to use
- `sizes`: Array of dimensions => descriptors
- `formats`: Array of formats to generate
- `crop`: Crop mode
- `focal`: [x, y] focal point coordinates
- `breakpoints`: Array of breakpoint configurations
- `img`: Attributes for `<img>` element
- `picture`: Attributes for `<picture>` element
- `sizes`: Sizes attribute value

### assetUrl($path, $options)

Generate single image URL.

**Options:**
- `size`: Dimensions (e.g., '800x600')
- `format`: Output format
- `crop`: Crop mode
- `focal`: Focal point

### assetSizes($sizes)

Generate sizes attribute (rarely needed manually).

## Integration with AssetsPlugin

If using with the Contenir metadata system:

```php
// In your AssetsPlugin or settings

use App\Metadata\Settings\SettingDefinition;
use App\Metadata\Settings\SettingsKeys;

public function getSettingsDefinitions(): array
{
    return [
        new SettingDefinition(
            'image_presets',
            SettingDefinition::TYPE_ARRAY,
            [
                'hero' => ['sizes' => ['1920x1080' => '1920w'], 'crop' => 'cover'],
            ],
            'Image Presets',
            'Responsive image size configurations',
            'Assets'
        ),
    ];
}
```

## Performance Tips

1. **Preload hero images**:
```html
<link rel="preload" as="image" href="/cache/images/1920x1080/hero.avif" type="image/avif" fetchpriority="high">
```

2. **Use fetchpriority="high" for LCP images**:
```php
'img' => ['fetchpriority' => 'high', 'loading' => 'eager']
```

3. **Lazy load below-the-fold images**:
```php
'img' => ['loading' => 'lazy']  // Default behavior
```

4. **Configure allowed dimensions** for security:
```php
'allowed_dimensions' => ['800x600', '1600x900', '1920x1080'],
```

## Upgrading from 1.x

### Breaking Changes

1. **MVC to Mezzio**: Uses PSR-15 handlers instead of MVC controllers
2. **Helper renamed**: `assetPicture` in docs → `assetSrcSet` (as requested)
3. **art_direction → breakpoints**: Configuration key renamed
4. **Cache path structure**: Changed from `.dimensions/` to `/cache/images/dimensions/`

### Migration Steps

1. Update composer.json: `"contenir/asset": "^2.0"`
2. Replace `Module.php` with `ConfigProvider.php`
3. Update route configuration
4. Run database migration
5. Update template calls (if using old helpers)

## License

BSD-3-Clause

## Support

- Documentation: https://docs.contenir.com.au
- Issues: https://github.com/contenir/contenir-asset/issues