# Configuration

All options live under the `asset` key. Defaults come from the module's
`getConfig()`.

```php
return [
    'asset' => [
        // Required for AssetManager: your entity class extending BaseAssetEntity.
        'entity'            => App\Entity\Asset::class,

        // Service the asset() view helper reads from.
        'asset_manager'     => Contenir\Asset\AssetManager::class,

        // Web root on disk, and the public directory uploads go under.
        'public_path'       => './public',
        'storage_directory' => '/asset/user',

        // ImageMagick binary for derivatives ("-lg" and "-sm" JPEGs); null disables them.
        'convert'           => 'convert',

        // Optional PSR-3 logger service name.
        'logger'            => null,

        // Host for assetUrl(); null uses the current request's host.
        'cdn'               => ['host' => 'cdn.example.com'],

        // Binary for the deprecated legacy resizer; null disables it.
        'srcset'            => ['helper' => '/usr/local/bin/convert'],
    ],

    // Named size sets for the assetSizes() and assetSrcSet() helpers.
    'view_helper_config' => [
        'assetsizes'  => ['sizes' => ['card' => [768 => 100, 'default' => 600]]],
        'assetsrcset' => ['sizes' => ['card' => [480 => '480x', 960 => '960x']]],
    ],
];
```

`AssetManager` also needs contenir-db-model's `EntityManager` and
`MetadataFactoryInterface` services. contenir-db-model's module registers
both.

Misconfiguration fails at construction with
`Contenir\Asset\Exception\RuntimeException` (a missing `asset.entity`, or a
service of the wrong type) or `InvalidArgumentException` (an `entity` that
doesn't extend `BaseAssetEntity`).

## Asset table

`BaseAssetEntity` maps these columns, so your table must have them. Add
your own columns, typically foreign keys such as `entry_id`, on your
subclass.

| Column | Property | Type |
| --- | --- | --- |
| `asset_id` | `assetId` | integer primary key, auto-increment |
| `type` | `type` | varchar, not null |
| `user_id` | `userId` | integer, nullable |
| `title` | `title` | varchar, nullable |
| `path` | `path` | varchar, nullable |
| `mime_type` | `mimeType` | varchar, nullable |
| `active` | `active` | varchar, nullable |
| `image_lg` | `imageLg` | varchar, nullable |
| `thumbnail` | `thumbnail` | varchar, nullable |
| `sequence` | `sequence` | integer, not null, default 0 |

MySQL example:

```sql
CREATE TABLE asset (
    asset_id  INT AUTO_INCREMENT PRIMARY KEY,
    type      VARCHAR(32)  NOT NULL,
    user_id   INT          NULL,
    title     VARCHAR(255) NULL,
    path      VARCHAR(255) NULL,
    mime_type VARCHAR(100) NULL,
    active    VARCHAR(16)  NULL,
    image_lg  VARCHAR(255) NULL,
    thumbnail VARCHAR(255) NULL,
    sequence  INT          NOT NULL DEFAULT 0,
    entry_id  INT          NULL
);
```
