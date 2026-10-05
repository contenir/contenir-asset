# Legacy resizer (deprecated)

`assetSrcSet()` and `ImageResizeController` implement the old
"dot-dimension" scheme. Prefer contenir/contenir-asset-laminas-mvc's
`storageSrcSet` and `storageSources` for new work. The legacy pieces are
kept for sites that still emit these URLs.

```php
<img srcset="<?= $this->assetSrcSet($asset->path, [480 => '480x', 960 => '960x540']) ?>">
<!-- /asset/gallery/.480x/photo.jpg 480w, /asset/gallery/.960x540/photo.jpg 960w -->
```

The `imageresize` route maps `/asset/<folder>/.<WxH>/<file>` to
`ImageResizeController::resizeAction()`, which works like this:

- **Generating:** it creates the resized copy with ImageMagick (the
  `asset.srcset.helper` binary) and serves it. Configure your web server
  to serve files that already exist, so the action only runs once per
  size.
- **Not found (404)** for missing files, malformed dimensions, and paths
  that resolve outside `<public_path>/asset`. That includes `../` and its
  URL-encoded forms, which were not blocked in 1.x.
- **Not implemented (501)** when no binary is configured.
- **Server error (500)** with the reason when conversion fails.

Responses are proper laminas-http `Response` objects; 1.x wrote headers
directly and called `exit`.
