<?php

declare(strict_types=1);

namespace Contenir\Asset\Service;

use function ltrim;
use function rtrim;
use function sprintf;

/**
 * AssetUrlGenerator - Generates URLs for responsive images
 */
class AssetUrlGenerator
{
    public function __construct(
        private string $cachePath
    ) {
    }

    /**
     * Generate URL for responsive image
     *
     * @param string $imagePath Original image path
     * @param array $options Options (size, crop, focal, format)
     * @return string Image URL
     */
    public function generate(string $imagePath, array $options): string
    {
        $size = $options['size'] ?? '1200x800';
        $format = $options['format'] ?? 'jpg';

        // Build URL: /cache/images/{dimensions}/{original-path}/filename.{format}
        $pathParts = pathinfo($imagePath);
        $directory = $pathParts['dirname'] ?? '';
        $filename = $pathParts['filename'] ?? 'image';

        return sprintf(
            '%s/%s/%s/%s.%s',
            rtrim($this->cachePath, '/'),
            $size,
            ltrim($directory, '/'),
            $filename,
            $format
        );
    }
}