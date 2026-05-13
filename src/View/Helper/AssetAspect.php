<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Laminas\View\Helper\AbstractHelper;

use function getimagesize;
use function is_array;
use function is_file;
use function is_object;
use function is_readable;
use function is_string;
use function rtrim;
use function sprintf;

/**
 * Resolve an image's aspect ratio as a CSS padding-top percentage.
 *
 * Templates use this to reserve layout space before an image loads. When the
 * file is missing or unreadable (record points at an asset that no longer
 * exists on disk), the helper returns an empty string (or the supplied
 * fallback) rather than letting getimagesize() bubble warnings up and
 * division-by-zero through to the exception strategy.
 *
 * Usage:
 *   <?= $this->assetAspect($article) ?>          // "56.25" → 16:9
 *   <?= $this->assetAspect('/missing.jpg') ?>    // ""
 *   <?= $this->assetAspect($img, 56.25) ?>       // "56.25" fallback
 */
class AssetAspect extends AbstractHelper
{
    public function __construct(private readonly string $publicPath = './public')
    {
    }

    /**
     * @param string|array<string, mixed>|object $image
     */
    public function __invoke(mixed $image, ?float $fallback = null): string
    {
        $fallbackString = $fallback === null ? '' : $this->format($fallback);

        $path = $this->extractPath($image);
        if ($path === '') {
            return $fallbackString;
        }

        $absolute = $this->publicPath . $path;
        if (! is_file($absolute) || ! is_readable($absolute)) {
            return $fallbackString;
        }

        $info = @getimagesize($absolute);
        if ($info === false || (int) ($info[0] ?? 0) === 0) {
            return $fallbackString;
        }

        return $this->format(((int) $info[1] / (int) $info[0]) * 100);
    }

    private function extractPath(mixed $image): string
    {
        if (is_string($image)) {
            return $image;
        }
        if (is_array($image)) {
            return (string) ($image['path'] ?? '');
        }
        if (is_object($image)) {
            return (string) ($image->path ?? '');
        }
        return '';
    }

    private function format(float $value): string
    {
        return rtrim(rtrim(sprintf('%.4f', $value), '0'), '.');
    }
}
