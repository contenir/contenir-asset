<?php

declare(strict_types=1);

namespace Contenir\Asset\Storage;

use Closure;
use Contenir\Asset\Exception\RuntimeException;

use function copy;
use function is_dir;
use function mkdir;
use function restore_error_handler;
use function set_error_handler;
use function unlink;

/**
 * File operations that report failure as a RuntimeException carrying
 * PHP's reason, instead of emitting a warning and returning false.
 *
 * @internal
 */
final readonly class Filesystem
{
    /**
     * @throws RuntimeException
     */
    public static function copy(string $source, string $destination): void
    {
        self::run(
            static fn(): bool => copy($source, $destination),
            static fn(string $reason): RuntimeException => RuntimeException::cannotCopy(
                $source,
                "{$destination} ({$reason})",
            ),
        );
    }

    /**
     * @throws RuntimeException
     */
    public static function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        self::run(
            static fn(): bool => mkdir($path, permissions: 0o775, recursive: true),
            static fn(string $reason): RuntimeException => RuntimeException::cannotCreateDirectory(
                "{$path} ({$reason})",
            ),
        );
    }

    /**
     * @throws RuntimeException
     */
    public static function remove(string $path): void
    {
        self::run(
            static fn(): bool => unlink($path),
            static fn(string $reason): RuntimeException => RuntimeException::cannotRemove("{$path} ({$reason})"),
        );
    }

    /**
     * @param Closure(): bool                     $operation
     * @param Closure(string): RuntimeException   $failure
     *
     * @throws RuntimeException
     *
     * @mago-expect analysis:unused-parameter set_error_handler() passes the error level first; only the message is used.
     */
    private static function run(Closure $operation, Closure $failure): void
    {
        $reason = 'unknown error';
        set_error_handler(static function (int $level, string $message) use (&$reason): bool {
            $reason = $message;

            return true;
        });

        try {
            $succeeded = $operation();
        } finally {
            restore_error_handler();
        }

        if (! $succeeded) {
            throw $failure($reason);
        }
    }
}
