<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Contenir\Asset\Model\Entity\BaseAssetEntity;

use function array_key_exists;
use function get_object_vars;
use function getimagesize;
use function is_array;
use function is_file;
use function is_object;
use function is_readable;
use function is_string;

/**
 * Reads an image's public path from the shapes templates pass (a path
 * string, an asset entity, an array or object with a "path" entry) and its
 * intrinsic dimensions from disk.
 *
 * @internal
 */
final readonly class ImageInfo
{
    /**
     * Width and height of the image, or null when the path is empty, the
     * file is missing or unreadable, or it is not an image.
     *
     * @return array{width: int, height: int}|null
     *
     * @mago-expect lint:no-error-control-operator getimagesize() warns on corrupt or non-image files; those read as "no dimensions".
     */
    public static function dimensions(string $publicPath, mixed $image): ?array
    {
        $path = self::path($image);
        if ('' === $path) {
            return null;
        }

        $absolute = $publicPath . $path;
        if (! is_file($absolute) || ! is_readable($absolute)) {
            return null;
        }

        $info = @getimagesize($absolute);
        if (false === $info || 0 === $info[0] || 0 === $info[1]) {
            return null;
        }

        return ['width' => $info[0], 'height' => $info[1]];
    }

    /**
     * @mago-expect analysis:mixed-assignment Template input is untyped; validated here.
     */
    public static function path(mixed $image): string
    {
        $path = match (true) {
            is_string($image) => $image,
            $image instanceof BaseAssetEntity => $image->path,
            is_array($image)  => $image['path'] ?? null,
            is_object($image) => self::publicPath($image),
            default           => null,
        };

        return is_string($path) ? $path : '';
    }

    private static function publicPath(object $image): mixed
    {
        $properties = get_object_vars($image);

        return array_key_exists('path', $properties) ? $properties['path'] : null;
    }
}
