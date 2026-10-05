<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

/**
 * Absolute URLs for assets: the configured CDN host ("asset.cdn.host"),
 * or the current request's host, with the current request's scheme.
 *
 *     <?= $this->assetUrl($asset->path) ?>   // https://cdn.example.com/asset/user/...
 *
 * @api
 */
final readonly class AssetUrl
{
    public function __construct(
        private string $scheme,
        private string $host,
    ) {}

    public function __invoke(string $path = ''): string
    {
        return "{$this->scheme}://{$this->host}{$path}";
    }
}
