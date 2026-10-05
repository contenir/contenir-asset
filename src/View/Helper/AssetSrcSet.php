<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Laminas\Escaper\Escaper;

use function array_key_exists;
use function array_map;
use function explode;
use function file_exists;
use function implode;
use function pathinfo;
use function rawurlencode;
use function str_contains;

/**
 * Legacy responsive srcset helper emitting the dot-notation `.<dimensions>`
 * scheme (e.g. `/asset/<folder>/.480x/<file>`) served by
 * {@see \Contenir\Asset\Controller\ImageResizeController}.
 *
 *     <img srcset="<?= $this->assetSrcSet($asset->path, [480 => '480x', 960 => '960x']) ?>">
 *
 * @api
 *
 * @deprecated Superseded by contenir/contenir-asset-laminas-mvc's `storageSrcSet`
 *             and `storageSources` helpers, which emit `_variant/<dimensions>/`
 *             URLs and `<source>` elements for WebP/AVIF.
 */
final class AssetSrcSet
{
    /**
     * @param array<string, array<int|string, int|string>> $sizes named size sets from configuration
     */
    public function __construct(
        private readonly string $rootPath = './public',
        private readonly array $sizes = [],
        private readonly Escaper $escaper = new Escaper(),
    ) {}

    private static function url(string $filepath, string $dimensions): string
    {
        $parts     = pathinfo($filepath);
        $directory = implode('/', array_map(rawurlencode(...), explode('/', $parts['dirname'] ?? '')));
        $geometry  = str_contains($dimensions, 'x') ? $dimensions : "{$dimensions}x";
        $extension = array_key_exists('extension', $parts) ? ".{$parts['extension']}" : '';

        return "{$directory}/.{$geometry}/" . rawurlencode($parts['filename']) . $extension;
    }

    /**
     * Escaped srcset value; the escaped path itself when the file does not
     * exist.
     *
     * @param string|array<array-key, mixed>|null $options a size set name, an array of width => "WxH", or ['sizes' => ...]
     */
    public function __invoke(?string $filepath = null, string|array|null $options = null): string
    {
        if (null === $filepath || '' === $filepath || ! file_exists($this->rootPath . $filepath)) {
            return $this->escaper->escapeHtmlAttr($filepath ?? '');
        }

        $srcset = [];
        foreach (SizeOptions::resolve($options, $this->sizes) as $width => $dimensions) {
            $srcset[] = self::url($filepath, (string) $dimensions) . " {$width}w";
        }

        return $this->escaper->escapeHtmlAttr(implode(', ', $srcset));
    }
}
