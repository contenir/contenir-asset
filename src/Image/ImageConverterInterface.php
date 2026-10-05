<?php

declare(strict_types=1);

namespace Contenir\Asset\Image;

use Contenir\Asset\Exception\RuntimeException;

/**
 * Writes a converted copy of an image. Implementations receive operations
 * from {@see ImageOperations}, already validated.
 *
 * @api
 */
interface ImageConverterInterface
{
    /**
     * @param list<string> $operations converter arguments between the source and destination
     *
     * @throws RuntimeException When the conversion fails.
     */
    public function convert(string $source, string $destination, array $operations): void;
}
