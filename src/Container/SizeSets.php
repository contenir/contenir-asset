<?php

declare(strict_types=1);

namespace Contenir\Asset\Container;

use function is_array;
use function is_int;
use function is_string;

/**
 * Parses configured size sets: named lists of widths or WIDTHxHEIGHT
 * strings, keyed by srcset width or media-query breakpoint. Invalid
 * entries are dropped rather than failing the page.
 *
 * @internal
 */
final readonly class SizeSets
{
    /**
     * @return array<string, array<int|string, int|string>>
     *
     * @mago-expect analysis:mixed-assignment Configuration is untyped input; validated here.
     */
    public static function parse(mixed $value): array
    {
        $sets = [];
        foreach (is_array($value) ? $value : [] as $name => $sizes) {
            if (! is_string($name) || ! is_array($sizes)) {
                continue;
            }

            $sets[$name] = self::sizes($sizes);
        }

        return $sets;
    }

    /**
     * @param array<array-key, mixed> $sizes
     *
     * @return array<int|string, int|string>
     *
     * @mago-expect analysis:mixed-assignment Configuration is untyped input; validated here.
     */
    public static function sizes(array $sizes): array
    {
        $valid = [];
        foreach ($sizes as $key => $size) {
            if (! is_int($size) && ! is_string($size)) {
                continue;
            }

            $valid[$key] = $size;
        }

        return $valid;
    }
}
