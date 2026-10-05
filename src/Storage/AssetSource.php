<?php

declare(strict_types=1);

namespace Contenir\Asset\Storage;

use Contenir\Asset\Exception\InvalidArgumentException;

use function basename;
use function is_array;
use function is_string;

/**
 * A file to store as an asset: a path on disk plus the original file name
 * (which differs from the path for PHP uploads).
 *
 * @api
 */
final readonly class AssetSource
{
    public function __construct(
        public string $path,
        public string $filename,
    ) {}

    /**
     * Accepts a file path, or a PHP upload array (an entry of $_FILES with
     * "name" and "tmp_name").
     *
     * @throws InvalidArgumentException
     *
     * @mago-expect analysis:mixed-assignment Upload arrays are untyped input; validated here.
     */
    public static function from(mixed $source): self
    {
        if (is_string($source) && '' !== $source) {
            return new self($source, basename($source));
        }

        $name = is_array($source) ? $source['name'] ?? null : null;
        $path = is_array($source) ? $source['tmp_name'] ?? null : null;
        if (! is_string($name) || ! is_string($path) || '' === $path) {
            throw InvalidArgumentException::invalidSource($source);
        }

        return new self($path, $name);
    }
}
