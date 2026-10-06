# Upgrading from 1.x to 2.0

2.0 moves to contenir-db-model 2.x (typed, attribute-mapped entities) and
PHP 8.3+. Most applications only need to convert their asset entity and
update configuration.

## 1. Asset entity

```php
// 1.x
class AssetEntity extends BaseAssetEntity
{
    protected array $primaryKeys = ['asset_id'];
    protected array $columns     = ['asset_id', 'type', 'user_id', 'entry_id', /* … */];
}

// 2.0
#[Table('asset')]
final class Asset extends BaseAssetEntity
{
    #[Column('entry_id')]
    public ?int $entryId = null;
}
```

- **Inherited columns:** `BaseAssetEntity` now maps the standard columns
  itself (see [configuration](docs/configuration.md#asset-table)). Declare
  only your extra columns.
- **Properties are camelCase:** `$asset->image_lg` becomes
  `$asset->imageLg`, `mime_type` becomes `mimeType`, `user_id` becomes
  `userId`, and so on.
- **Constructor:** the entity must be constructible without arguments.
- **Table columns:** `BaseAssetEntity` maps every standard column, so the
  table must have all of them (see
  [configuration](docs/configuration.md#asset-table)). Older Contenir asset
  tables often lack `user_id`, `mime_type`, `image_lg` and `sequence`, and
  may hold rows with a NULL `type`, which the non-nullable `type` property
  cannot load. Check your table before deploying. A typical MySQL
  migration:

  ```sql
  ALTER TABLE asset ADD COLUMN user_id INT NULL AFTER type;
  ALTER TABLE asset ADD COLUMN mime_type VARCHAR(100) NULL AFTER path;
  ALTER TABLE asset ADD COLUMN image_lg VARCHAR(255) NULL AFTER mime_type;
  ALTER TABLE asset ADD COLUMN sequence INT NOT NULL DEFAULT 0 AFTER active;
  UPDATE asset SET type = 'image' WHERE type IS NULL;
  ```

  Skip any column you already have, and give `type` a value that suits
  your data. Making `type` NOT NULL with a default afterwards stops new
  rows from arriving without one.

## 2. Configuration and services

| 1.x | 2.0 |
| --- | --- |
| `asset.repository.asset` (a repository class) | `asset.entity` (your entity class) |
| `BaseAssetRepository`, `RepositoryFactory` registrations | Removed: `AssetManager` uses `$em->getRepository()` |
| `BaseAssetEntity::class => InvokableFactory::class` | Remove |
| `asset.cache.public_path` (helpers) | `asset.public_path`; the old key is still read as a fallback |
| — | New: `asset.storage_directory`, `asset.convert`, `asset.logger`, `asset.cdn.host` |
| Route default action `index` | `resize` (only matters if you override the route) |

## 3. AssetManager

| 1.x | 2.0 |
| --- | --- |
| Write methods called undefined methods (`commitTransaction`, `getLogger`, `getSafeFilename`) | All methods work, with transactions and an optional PSR-3 logger |
| `addAsset($target, UserEntity $user, AbstractEntity $parent = null, $type)` | `addAsset(ResourceInterface $target, ?object $user = null, ?object $parent = null, string $type = 'image')` |
| `updateAsset($asset, $user, array $values, array $data)` with column names | `updateAsset($asset, ?object $user = null, array $values = [])` with **property** names; unknown names throw |
| `findByType()` unordered | Ordered by `sequence`, then id |
| `storeAsset()` shell commands with unescaped paths | Arguments escaped, constraints validated |

## 4. View helpers

- **Plain invokables:** helpers no longer extend laminas-view's deprecated
  `AbstractHelper`, and `AssetUrl` no longer extends the deprecated
  `ServerUrl`. Template usage is unchanged.
- **`assetSizes(array)`:** a plain array is now used as the sizes. 1.x
  ignored it, and only `['sizes' => …]` or a set name worked.
- **`assetSrcSet()`:** for a missing file it returns the path
  attribute-escaped.
