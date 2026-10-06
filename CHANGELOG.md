# Changelog

## [2.0.0-RC1] - Unreleased

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
