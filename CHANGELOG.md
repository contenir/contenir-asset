# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2025-01-04

### Added
- Mezzio/PSR-15 support (replaces Laminas MVC)
- Modern image format support (AVIF, WebP)
- Focal point cropping with database storage
- Breakpoint-based art direction (renamed from art_direction)
- ImageMagick-based image processing with optimization
- PSR-15 ImageResizeHandler for on-demand generation
- Comprehensive preset system
- AssetSrcSet view helper (generates complete `<picture>` elements)
- AssetUrl helper for single image URLs
- AssetSizes helper for sizes attribute generation
- Security improvements with dimension whitelist
- Improved caching strategy with immutable cache headers
- Named crop support for advanced use cases

### Changed
- **BREAKING**: Migrated from Laminas MVC to Mezzio
- **BREAKING**: Renamed `assetPicture` to `assetSrcSet`
- **BREAKING**: Renamed `art_direction` config key to `breakpoints`
- **BREAKING**: Cache path structure changed from `.dimensions/` to `/cache/images/dimensions/`
- **BREAKING**: Requires PHP 8.1+
- Improved ImageMagick command generation
- Better focal point to gravity mapping

### Removed
- Laminas MVC Module.php (replaced with ConfigProvider)
- FastRoute dependency
- Hidden directory pattern for cached images

### Fixed
- Security vulnerabilities in path handling
- Improved error handling and logging

### Migration from 1.x
See README.md "Upgrading from 1.x" section for migration guide.

## [1.0.3.1] - 2024-09
Last MVC-based release. See legacy repository for changelog.