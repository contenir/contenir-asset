# AssetManager

`Contenir\Asset\AssetManager` (service `AssetManager::class`) manages asset
rows through contenir-db-model's `EntityManager`, and their files through
`AssetStorage`. Every write runs in a transaction.

| Method | Does |
| --- | --- |
| `findOneById($id)` | The asset, or `null` |
| `findByType($type)` | `list` of assets of that type, in `sequence` order |
| `addAsset($target, $user = null, $parent = null, $type = 'image')` | Creates and saves a new asset linked to its owners |
| `storeAsset($asset, $target, $type, $source, $constraints = null)` | Copies the file in, generates derivatives, records paths, saves |
| `updateAsset($asset, $user = null, $values = [])` | Assigns properties by name, saves, logs |
| `saveAsset($asset)` | Saves |
| `sequenceAsset([$id, ...])` | Sets `sequence` to each id's position (from 1) |
| `deleteAsset($asset)` | Deletes the **row** (files stay) |
| `removeAsset($asset)` | Deletes the **files** (row stays) |

## Linking assets to records

`addAsset()` copies each owner's **primary-key columns** onto asset
properties mapped to the same column names. The owners are the target, the
user and the optional parent:

```php
$asset = $assetManager->addAsset($entry, $currentUser);
// Entry's key column entry_id → $asset->entryId (mapped to entry_id on your Asset)
// User's key column user_id   → $asset->userId
```

Owner key columns that the asset doesn't map are skipped. Owners must be
mapped contenir-db-model entities. The target must also implement
`Laminas\Permissions\Acl\Resource\ResourceInterface`, because its resource
id names the storage folder. The asset entity class must be constructible
without arguments.

## Storing files

```php
$assetManager->storeAsset($asset, $entry, 'image', $_FILES['photo']);           // upload array
$assetManager->storeAsset($asset, $entry, 'image', '/tmp/import/photo.jpg');    // file path
$assetManager->storeAsset($asset, $entry, 'image', $upload, constraints: '1200x800');
```

- **Where files go:** `<public_path><storage_directory>/<resourceId>-<keys>/<type>/`,
  for example `/asset/user/entry-42/image/holiday-photo.jpg`.
- **File names** are made URL-safe: lower case, ASCII, `-` and `_` only,
  with accents transliterated when `ext-intl` is available.
- **Recorded on the asset:** `path` and `mimeType`; `active` is set to
  `'active'`; `title` is filled from the file name if empty.
- **Derivatives:** with `asset.convert` set, a large JPEG (`imageLg`,
  `-lg.jpg`) and a 540px square thumbnail (`thumbnail`, `-sm.jpg`) are
  written.
  - Without constraints, the large copy fits within 2000×2000.
  - With `WIDTHxHEIGHT` constraints, it is filled and centre-cropped to
    that size.
  - Constraints are validated, and every ImageMagick argument is
    shell-escaped.

`AssetStorage` and `ImageMagickConverter` are public, so you can use them
directly, or implement `ImageConverterInterface` to use another image
library.

## Errors

| Exception | When |
| --- | --- |
| `Contenir\Asset\Exception\InvalidArgumentException` | Unknown property in `updateAsset()` values, malformed constraints, invalid source |
| `Contenir\Asset\Exception\RuntimeException` | Missing source file, directory or file operation failure (PHP's reason included), ImageMagick failure |
| contenir-db-model exceptions | Persistence failures (see its docs) |

Both package exceptions implement `Contenir\Asset\Exception\ExceptionInterface`.
