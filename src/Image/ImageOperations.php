<?php

declare(strict_types=1);

namespace Contenir\Asset\Image;

use Contenir\Asset\Exception\InvalidArgumentException;

use function preg_match;

/**
 * The ImageMagick operations the package performs, as argument lists.
 *
 * @api
 */
final readonly class ImageOperations
{
    private const string GEOMETRY = '/^\d+(\.\d+)?x(\d+(\.\d+)?)?$/';

    /**
     * @throws InvalidArgumentException
     */
    public static function geometry(string $value): string
    {
        if (1 !== preg_match(self::GEOMETRY, $value)) {
            throw InvalidArgumentException::invalidConstraints($value);
        }

        return $value;
    }

    /**
     * Large display copy: fills and crops to $constraints when given,
     * otherwise fits within 2000x2000.
     *
     * @return list<string>
     *
     * @throws InvalidArgumentException When $constraints is not WIDTHxHEIGHT.
     */
    public static function large(?string $constraints = null): array
    {
        $common = ['-colorspace', 'sRGB', '-strip'];
        if (null === $constraints) {
            return [...$common, '-resize', '2000x2000', '-unsharp', '0x0.75', '-quality', '85%'];
        }

        $geometry = self::geometry($constraints);

        return [
            ...$common,
            '-resize',
            "{$geometry}^",
            '-gravity',
            'center',
            '-extent',
            $geometry,
            '-unsharp',
            '0x0.75',
            '-quality',
            '85%',
        ];
    }

    /**
     * Fit within $dimensions ("480x" or "480x320"), as used by the legacy
     * dot-dimension resizer.
     *
     * @return list<string>
     *
     * @throws InvalidArgumentException When $dimensions is not WIDTHx[HEIGHT].
     */
    public static function resize(string $dimensions): array
    {
        return [
            '-colorspace',
            'sRGB',
            '-strip',
            '-resize',
            self::geometry($dimensions),
            '-unsharp',
            '0x0.75',
            '-quality',
            '85%',
        ];
    }

    /**
     * 540px square thumbnail, centre-cropped.
     *
     * @return list<string>
     */
    public static function thumbnail(): array
    {
        return [
            '-colorspace',
            'sRGB',
            '-strip',
            '-resize',
            '540x540^>',
            '-gravity',
            'center',
            '-unsharp',
            '0x0.75',
            '-crop',
            '540x540+0+0',
        ];
    }
}
