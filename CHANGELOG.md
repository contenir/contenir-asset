# Changelog

## [2.0.0-RC2] - 2026-10-23

The package no longer depends on a framework: it serves laminas-mvc and
Mezzio alike, and each framework's adapter supplies the legacy resize
route. See [UPGRADE-2.0.md](UPGRADE-2.0.md) § 2.

### Added

- **`ConfigProvider`** carries the wiring in the shape Mezzio and
  laminas-config-aggregator read; `Module` hands the same wiring to
  laminas-mvc. `composer.json` declares both, so the laminas component
  installer registers whichever framework loads the package.
- **`Image\LegacyResizer`** (and its factory, registered by the provider)
  does the resizing behind the `.WxH` scheme for both framework adapters:
  it names the generated file, returns null for an unsafe or missing
  source, and throws when conversion fails, leaving status codes to the
  adapter. `LegacyResizePaths` moved from `Controller` to `Image`.
- **`View\Helper\RequestOrigin`** reads scheme and host from the server
  variables of the request PHP is serving.

### Changed

- **`AssetUrl`** takes scheme and host from `RequestOrigin`, so it needs
  no framework's request object; `AssetUrlFactory` accepts a server array
  for tests.

### Removed

- The `laminas/laminas-mvc`, `laminas/laminas-router` and
  `laminas/laminas-http` requirements.
- `ImageResizeController` and its factory, now `Controller\ImageResizeController`
  in contenir/contenir-asset-laminas-mvc 2.3; contenir/contenir-asset-mezzio
  2.1 adds `Handler\ImageResizeHandler`. Both route to `LegacyResizer`.

## [2.0.0-RC1] - 2026-10-06

Requires PHP 8.3+ and contenir/contenir-db-model 2.x. See
[UPGRADE-2.0.md](UPGRADE-2.0.md).

### Added

- **`BaseAssetEntity`** maps the standard asset columns.
- **`AssetManager` works end to end:**
  - transactions;
  - linking owners by key column;
  - storage through `AssetStorage`;
  - derivatives through `ImageConverterInterface` / `ImageMagickConverter`;
  - an optional PSR-3 logger.
- **New configuration options:** `asset.entity`, `asset.storage_directory`,
  `asset.convert`, `asset.logger` and `asset.cdn.host`.
- **QA.** Mago via php-db/phpdb-qa-tools, unit and integration test suites,
  CI, documentation and `llms.txt`.

### Changed

- **Helpers** are plain invokables, escaping through laminas-escaper.
- **`AssetUrl`** reads scheme and host from the MVC request.
- **The legacy resizer** returns `Response` objects instead of calling
  `exit`.

### Fixed

- **Path traversal:** the legacy resizer no longer serves or writes files
  outside `public/asset` (`../` in the URL).
- **Shell commands:** ImageMagick commands escape every argument.
- **Undeclared dependencies:** `laminas-view`, `laminas-permissions-acl`,
  `laminas-escaper`, `laminas-http` and `laminas-router` are now required
  explicitly.

### Removed

- `BaseAssetRepository` and the v1 repository configuration.
