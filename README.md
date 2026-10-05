# contenir-asset

Asset records, file storage, image derivatives and view helpers for
laminas-mvc applications, built on
[contenir-db-model](https://github.com/contenir/contenir-db-model) 2.x.

- **`AssetManager`.** Create, store, update, sequence and delete asset
  records and their files.
- **Image derivatives.** A large copy and a thumbnail are generated through
  ImageMagick on upload.
- **View helpers:**
  - `asset()`, `assetUrl()`, `assetSize()`, `assetAspect()` and
    `assetSizes()`;
  - the legacy `assetSrcSet()`, with its on-demand resizer.

> **Status: 2.0 pre-release.** 2.0 is not compatible with 1.x. See
> [UPGRADE-2.0.md](UPGRADE-2.0.md).

## Requirements

- PHP 8.3, 8.4 or 8.5
- `contenir/contenir-db-model` 2.x and a `php-db/phpdb` platform package
- laminas-mvc 3.7+
- ImageMagick (`convert`) for image derivatives
- `ext-intl` (recommended) for transliterating accented file names

## Installation

```bash
composer require contenir/contenir-asset:^2.0@RC
```

The consuming project needs `"minimum-stability": "dev"` and
`"prefer-stable": true` until php-db/phpdb 0.6.0 is tagged. With the
laminas component installer, the `Contenir\Asset` module registers itself.

## At a glance

```php
// Your asset entity: the standard columns come from BaseAssetEntity.
#[Table('asset')]
final class Asset extends BaseAssetEntity
{
    #[Column('entry_id')]
    public ?int $entryId = null;
}

// config/autoload/asset.global.php
return ['asset' => ['entity' => Asset::class]];

// In a controller or service:
$asset = $assetManager->addAsset($entry, $currentUser);             // links entry_id and user_id
$assetManager->storeAsset($asset, $entry, 'image', $_FILES['photo'], '1200x800');
```

```php
<!-- In a view -->
<img src="<?= $this->escapeHtmlAttr($this->asset($id)?->imageLg ?? "") ?>"
     width="<?= $this->assetSize($asset)['width'] ?? '' ?>">
```

## Documentation

1. [Configuration](docs/configuration.md): every `asset` option and the
   reference table schema
2. [AssetManager](docs/asset-manager.md): creating, storing, updating,
   sequencing and deleting assets
3. [View helpers](docs/view-helpers.md): `asset`, `assetUrl`, `assetSize`,
   `assetAspect`, `assetSizes`
4. [Legacy resizer](docs/legacy-resizer.md): the deprecated `.WxH` scheme,
   `assetSrcSet` and its resize route

Upgrading from 1.x: [UPGRADE-2.0.md](UPGRADE-2.0.md). Changes:
[CHANGELOG.md](CHANGELOG.md). [`llms.txt`](llms.txt) indexes the docs for
LLM tooling.

## Development

Mago is required (`brew install mago`).

```bash
composer install
composer check              # format check, lint, static analysis, unit + integration tests
composer cs-fix
composer test               # unit suite
composer test-integration   # SQLite + temporary directory suite (needs gd)
```

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
