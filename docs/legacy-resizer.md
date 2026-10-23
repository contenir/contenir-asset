# Legacy resizer (deprecated)

`assetSrcSet()` and `Image\LegacyResizer` implement the old
"dot-dimension" scheme. Prefer the keyed variants of
contenir/contenir-asset-laminas-mvc or contenir/contenir-asset-mezzio
(`storageSrcSet` and `storageSources`) for new work. The legacy pieces are
kept for sites that still emit these URLs.

```php
<img srcset="<?= $this->assetSrcSet($asset->path, [480 => '480x', 960 => '960x540']) ?>">
<!-- /asset/gallery/.480x/photo.jpg 480w, /asset/gallery/.960x540/photo.jpg 960w -->
```

The resizing lives here, in the `LegacyResizer` service that
`ConfigProvider` registers; the route that maps
`/asset/<folder>/.<WxH>/<file>` onto it comes from the framework adapter:
`imageresize` and `ImageResizeController` in contenir-asset-laminas-mvc,
`ImageResizeHandler` in contenir-asset-mezzio. Whichever serves the request,
it works like this:

- **Generating:** `LegacyResizer::resize()` creates the resized copy with
  ImageMagick (the `asset.srcset.helper` binary) and names the file, which
  the adapter serves. Configure your web server to serve files that already
  exist, so this only runs once per size.
- **Not found (404)** for missing files, malformed dimensions, and paths
  that resolve outside `<public_path>/asset`: `resize()` returns null.
  That includes `../` and its URL-encoded forms, which were not blocked
  in 1.x.
- **Not implemented (501)** when no binary is configured:
  `isConfigured()` is false.
- **Server error (500)** with the reason when conversion fails: `resize()`
  throws.
