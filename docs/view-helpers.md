# View helpers

| Helper | Returns |
| --- | --- |
| `asset($id)` | The asset entity, or `null` (for `null`/`''` ids, no query) |
| `assetUrl($path)` | Absolute URL: current scheme, `asset.cdn.host` or the request host, then `$path` |
| `assetSize($image)` | `['width' => int, 'height' => int]`, or `null` |
| `assetAspect($image, $fallback = null)` | Height/width as a percentage string (`"56.25"`), or the fallback / `''` |
| `assetSizes($sizes)` | An escaped `sizes` attribute value |

`$image` may be a public path, an asset entity, an array with a `path`
key, or an object with a public `path` property. Missing, unreadable and
non-image files never raise warnings; they produce `null` (or the
fallback).

## `assetSizes()`

```php
<img sizes="<?= $this->assetSizes([768 => 100, 1200 => 600, 'default' => 900]) ?>">
<!-- (max-width: 768px) 100px, (max-width: 1200px) 600px, 900px -->
```

- **Integer keys** become `(max-width: Npx)`.
- **String keys** are used as media queries.
- **The last entry** is the unconditional default.
- **The argument** may be a plain array, `['sizes' => …]`, or the name of a
  set in `view_helper_config.assetsizes.sizes`.

The output is attribute-escaped, so spaces and parentheses appear as
entities, which browsers decode.

Helpers are plain invokable classes; they don't extend laminas-view's
deprecated `AbstractHelper`.
