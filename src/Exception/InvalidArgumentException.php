<?php

declare(strict_types=1);

namespace Contenir\Asset\Exception;

use InvalidArgumentException as SplInvalidArgumentException;

use function get_debug_type;
use function sprintf;

/**
 * @api
 */
final class InvalidArgumentException extends SplInvalidArgumentException implements ExceptionInterface
{
    public static function invalidConstraints(string $constraints): self
    {
        return new self(sprintf('Image constraints must look like "800x600", got "%s"', $constraints));
    }

    public static function invalidSource(mixed $source): self
    {
        return new self(sprintf(
            'An asset source must be a file path or an upload array with "name" and "tmp_name", got %s',
            get_debug_type($source),
        ));
    }

    public static function notAnAssetEntity(string $className): self
    {
        return new self(sprintf(
            'Asset entity class "%s" must extend Contenir\Asset\Model\Entity\BaseAssetEntity',
            $className,
        ));
    }

    public static function unknownProperty(string $className, string $property): self
    {
        return new self(sprintf('"%s" is not a mapped column property of %s', $property, $className));
    }
}
