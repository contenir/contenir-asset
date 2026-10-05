<?php

declare(strict_types=1);

namespace ContenirTest\Asset\TestAsset\Image;

use Contenir\Asset\Image\ImageConverterInterface;
use Override;

use function copy;

/**
 * Copies the source to the destination and records each call.
 */
final class FakeImageConverter implements ImageConverterInterface
{
    /**
     * @var list<array{source: string, destination: string, operations: list<string>}>
     */
    public array $calls = [];

    #[Override]
    public function convert(string $source, string $destination, array $operations): void
    {
        $this->calls[] = ['source' => $source, 'destination' => $destination, 'operations' => $operations];
        copy($source, $destination);
    }
}
