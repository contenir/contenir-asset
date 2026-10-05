<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use function rtrim;
use function sprintf;

/**
 * Resolve an image's aspect ratio as a CSS padding-top percentage, to
 * reserve layout space before the image loads. Returns the fallback (an
 * empty string by default) when the file is missing, unreadable or not an
 * image.
 *
 *     <?= $this->assetAspect($asset) ?>          // "56.25" (16:9)
 *     <?= $this->assetAspect('/missing.jpg') ?>  // ""
 *     <?= $this->assetAspect($asset, 56.25) ?>   // "56.25" when unknown
 *
 * @api
 */
final class AssetAspect
{
    public function __construct(
        private readonly string $publicPath = './public',
    ) {}

    private static function format(float $value): string
    {
        return rtrim(rtrim(sprintf('%.4f', $value), characters: '0'), characters: '.');
    }

    /**
     * @param mixed $image a public path, an asset entity, or an array/object with a "path" entry
     */
    public function __invoke(mixed $image, ?float $fallback = null): string
    {
        $dimensions = ImageInfo::dimensions($this->publicPath, $image);
        if (null === $dimensions) {
            return null === $fallback ? '' : self::format($fallback);
        }

        return self::format(($dimensions['height'] / $dimensions['width']) * 100);
    }
}
