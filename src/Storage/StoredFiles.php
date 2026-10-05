<?php

declare(strict_types=1);

namespace Contenir\Asset\Storage;

/**
 * Public paths (relative to the web root) of a stored file and its
 * derivatives.
 *
 * @api
 */
final readonly class StoredFiles
{
    public function __construct(
        public string $path,
        public string $mimeType,
        public ?string $imageLg = null,
        public ?string $thumbnail = null,
    ) {}
}
