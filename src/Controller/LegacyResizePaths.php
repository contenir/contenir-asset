<?php

declare(strict_types=1);

namespace Contenir\Asset\Controller;

use Contenir\Asset\Exception\RuntimeException;
use Contenir\Asset\Storage\Filesystem;

use function basename;
use function dirname;
use function is_file;
use function preg_match;
use function realpath;
use function str_starts_with;

/**
 * Validated source and destination paths for a legacy resize request.
 * The source must resolve inside "<public>/asset", so "../" in the URL
 * cannot reach other files.
 *
 * @internal
 */
final readonly class LegacyResizePaths
{
    private function __construct(
        public string $source,
        public string $destination,
        public string $dimensions,
    ) {}

    /**
     * Null when the dimensions are malformed or the source does not exist
     * inside the asset directory.
     */
    public static function resolve(string $publicPath, string $folder, string $dimensions, string $filename): ?self
    {
        $root = realpath("{$publicPath}/asset");
        if (false === $root || 1 !== preg_match('/^\d+(\.\d+)?x(\d+(\.\d+)?)?$/', $dimensions)) {
            return null;
        }

        $source = realpath("{$root}/{$folder}/{$filename}");
        if (false === $source || ! str_starts_with($source, "{$root}/") || ! is_file($source)) {
            return null;
        }

        return new self($source, dirname($source) . "/.{$dimensions}/" . basename($source), $dimensions);
    }

    /**
     * @throws RuntimeException When the destination directory cannot be created.
     */
    public function prepare(): void
    {
        Filesystem::ensureDirectory(dirname($this->destination));
    }
}
