<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

/**
 * Resolve an image's intrinsic dimensions, for explicit width and height
 * attributes. Returns null when the file is missing, unreadable or not an
 * image, so templates can render without dimensions.
 *
 *     $size = $this->assetSize($asset);
 *     if (null !== $size) {
 *         echo '<img width="' . $size['width'] . '" height="' . $size['height'] . '">';
 *     }
 *
 * @api
 */
final class AssetSize
{
    public function __construct(
        private readonly string $publicPath = './public',
    ) {}

    /**
     * @param mixed $image a public path, an asset entity, or an array/object with a "path" entry
     *
     * @return array{width: int, height: int}|null
     */
    public function __invoke(mixed $image): ?array
    {
        return ImageInfo::dimensions($this->publicPath, $image);
    }
}
