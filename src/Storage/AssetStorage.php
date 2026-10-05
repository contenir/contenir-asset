<?php

declare(strict_types=1);

namespace Contenir\Asset\Storage;

use Contenir\Asset\Exception\InvalidArgumentException;
use Contenir\Asset\Exception\RuntimeException;
use Contenir\Asset\Image\ImageConverterInterface;
use Contenir\Asset\Image\ImageOperations;

use function is_file;
use function is_readable;
use function mime_content_type;
use function pathinfo;
use function rtrim;
use function str_starts_with;

use const PATHINFO_EXTENSION;
use const PATHINFO_FILENAME;

/**
 * Stores asset files under the web root and generates their image
 * derivatives. All paths it returns are public paths: relative to the web
 * root and starting with "/".
 *
 * @api
 */
final readonly class AssetStorage
{
    public function __construct(
        private string $publicPath = './public',
        private string $directory = '/asset/user',
        private ?ImageConverterInterface $converter = null,
    ) {}

    /**
     * Delete the files at the given public paths. Missing files and null
     * paths are ignored.
     *
     * @throws RuntimeException When an existing file cannot be deleted.
     */
    public function remove(?string ...$paths): void
    {
        foreach ($paths as $path) {
            $absolute = null === $path || '' === $path ? null : $this->absolute($path);
            if (null === $absolute || ! is_file($absolute)) {
                continue;
            }

            Filesystem::remove($absolute);
        }
    }

    /**
     * Copy $source into "<directory>/<folder>/<type>/" under a safe file
     * name. With a converter configured, also writes a large JPEG copy
     * ("-lg") and a thumbnail ("-sm").
     *
     * @throws InvalidArgumentException When $constraints is not WIDTHxHEIGHT.
     * @throws RuntimeException         When the source is missing or a file cannot be written.
     */
    public function store(AssetSource $source, string $folder, string $type, ?string $constraints = null): StoredFiles
    {
        if (! is_file($source->path) || ! is_readable($source->path)) {
            throw RuntimeException::sourceNotFound($source->path);
        }

        $directory = $this->ensureDirectory("{$this->directory}/{$folder}/{$type}");
        $base      = SafeFilename::from(pathinfo($source->filename, PATHINFO_FILENAME));
        $extension = SafeFilename::from(pathinfo($source->filename, PATHINFO_EXTENSION), fallback: 'bin');
        $path      = "{$directory}/{$base}.{$extension}";

        Filesystem::copy($source->path, $this->absolute($path));

        $detected = mime_content_type($this->absolute($path));
        $mimeType = false === $detected ? 'application/octet-stream' : $detected;
        if (null === $this->converter) {
            return new StoredFiles($path, $mimeType);
        }

        $imageLg   = "{$directory}/{$base}-lg.jpg";
        $thumbnail = "{$directory}/{$base}-sm.jpg";
        $this->converter->convert(
            $this->absolute($path),
            $this->absolute($imageLg),
            ImageOperations::large($constraints),
        );
        $this->converter->convert($this->absolute($imageLg), $this->absolute($thumbnail), ImageOperations::thumbnail());

        return new StoredFiles($path, $mimeType, $imageLg, $thumbnail);
    }

    private function absolute(string $path): string
    {
        return rtrim($this->publicPath, characters: '/') . (str_starts_with($path, '/') ? $path : "/{$path}");
    }

    /**
     * @throws RuntimeException
     */
    private function ensureDirectory(string $path): string
    {
        Filesystem::ensureDirectory($this->absolute($path));

        return $path;
    }
}
