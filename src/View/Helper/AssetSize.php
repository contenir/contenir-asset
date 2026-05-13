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

/**
 * Resolve an image's intrinsic dimensions safely.
 *
 * Templates use this to set explicit width/height attributes (browsers can
 * reserve space before the image loads) or to compute aspect-based layout
 * values. Returns null when the asset record points at a file that no
 * longer exists on disk, so consumers can fall through to a no-dimension
 * render rather than crashing on getimagesize() warnings +
 * DivisionByZeroError in PHP 8.
 *
 * Usage:
 *   $size = $this->assetSize($asset);
 *   if ($size !== null) {
 *       echo '<img width="' . $size['width'] . '" height="' . $size['height'] . '">';
 *   }
 */
class AssetSize extends AbstractHelper
{
    public function __construct(private readonly string $publicPath = './public')
    {
    }

    /**
     * @param string|array<string, mixed>|object $image
     * @return array{width: int, height: int}|null
     */
    public function __invoke(mixed $image): ?array
    {
        $path = $this->extractPath($image);
        if ($path === '') {
            return null;
        }

        $absolute = $this->publicPath . $path;
        if (! is_file($absolute) || ! is_readable($absolute)) {
            return null;
        }

        $info = @getimagesize($absolute);
        if ($info === false || (int) ($info[0] ?? 0) === 0 || (int) ($info[1] ?? 0) === 0) {
            return null;
        }

        return [
            'width'  => (int) $info[0],
            'height' => (int) $info[1],
        ];
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
}
