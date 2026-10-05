<?php

declare(strict_types=1);

namespace Contenir\Asset\Exception;

use RuntimeException as SplRuntimeException;

use function sprintf;

/**
 * @api
 */
final class RuntimeException extends SplRuntimeException implements ExceptionInterface
{
    public static function cannotCopy(string $source, string $destination): self
    {
        return new self(sprintf('Cannot copy %s to %s', $source, $destination));
    }

    public static function cannotCreateDirectory(string $path): self
    {
        return new self(sprintf('Cannot create asset directory %s', $path));
    }

    public static function cannotRemove(string $path): self
    {
        return new self(sprintf('Cannot remove %s', $path));
    }

    public static function conversionFailed(string $command, int $exitCode): self
    {
        return new self(sprintf('Image conversion failed with exit code %d: %s', $exitCode, $command));
    }

    public static function invalidService(string $name, string $expected): self
    {
        return new self(sprintf('Service "%s" must be an instance of %s', $name, $expected));
    }

    public static function notConfigured(string $key): self
    {
        return new self(sprintf('Configuration "%s" is required', $key));
    }

    public static function sourceNotFound(string $source): self
    {
        return new self(sprintf('Asset source file %s does not exist or is not readable', $source));
    }
}
