<?php

declare(strict_types=1);

namespace Contenir\Asset\Image;

use Closure;
use Contenir\Asset\Exception\RuntimeException;
use Override;

use function array_map;
use function escapeshellarg;
use function exec;
use function implode;

/**
 * Runs ImageMagick's convert (or magick) binary. Every argument is shell
 * escaped. The first frame of multi-frame sources (PDF, GIF) is used.
 *
 * @api
 */
final readonly class ImageMagickConverter implements ImageConverterInterface
{
    /**
     * @var Closure(string): int
     */
    private Closure $runner;

    /**
     * @param (Closure(string): int)|null $runner executes a shell command and returns its exit code
     */
    public function __construct(
        private string $binary = 'convert',
        ?Closure $runner = null,
    ) {
        $this->runner = $runner ?? self::exec(...);
    }

    private static function exec(string $command): int
    {
        $output   = [];
        $exitCode = 0;
        exec("{$command} 2>&1", $output, $exitCode);

        return $exitCode;
    }

    #[Override]
    public function convert(string $source, string $destination, array $operations): void
    {
        $command = implode(' ', [
            escapeshellarg($this->binary),
            escapeshellarg("{$source}[0]"),
            ...array_map(escapeshellarg(...), $operations),
            escapeshellarg($destination),
        ]);

        $exitCode = ($this->runner)($command);
        if (0 !== $exitCode) {
            throw RuntimeException::conversionFailed($command, $exitCode);
        }
    }
}
