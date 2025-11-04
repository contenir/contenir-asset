<?php

declare(strict_types=1);

namespace Contenir\Asset\Service;

use function dirname;
use function file_exists;
use function is_dir;
use function mkdir;
use function pathinfo;
use function sprintf;
use function str_replace;

use const PATHINFO_EXTENSION;
use const PATHINFO_FILENAME;

/**
 * ImageCache - Manages cached responsive images
 */
class ImageCache
{
    public function __construct(
        private string $cachePath,
        private string $publicPath
    ) {
    }

    /**
     * Get the cached image path for given source and parameters
     *
     * @param string $sourcePath Original image path (relative to public)
     * @param string $dimensions Dimensions string (e.g., "800x600")
     * @param string $format Output format
     * @return string Cached image path
     */
    public function getCachePath(string $sourcePath, string $dimensions, string $format): string
    {
        $pathInfo = pathinfo($sourcePath);
        $filename = $pathInfo['filename'] ?? 'image';

        // Build cache path: /cache/images/{dimensions}/{original-path}/{filename}.{format}
        $cachePath = sprintf(
            '%s/%s/%s/%s.%s',
            $this->cachePath,
            $dimensions,
            dirname($sourcePath),
            $filename,
            $format
        );

        return $this->normalizePath($cachePath);
    }

    /**
     * Get the full filesystem path for cached image
     */
    public function getFilesystemPath(string $cachePath): string
    {
        return $this->publicPath . '/' . ltrim($cachePath, '/');
    }

    /**
     * Check if cached image exists
     */
    public function exists(string $cachePath): bool
    {
        return file_exists($this->getFilesystemPath($cachePath));
    }

    /**
     * Ensure the cache directory exists for a given path
     */
    public function ensureDirectory(string $cachePath): void
    {
        $filesystemPath = $this->getFilesystemPath($cachePath);
        $directory = dirname($filesystemPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Normalize path by removing duplicate slashes
     */
    private function normalizePath(string $path): string
    {
        return str_replace('//', '/', $path);
    }
}