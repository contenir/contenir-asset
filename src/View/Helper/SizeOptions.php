<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Contenir\Asset\Container\SizeSets;

use function is_array;
use function is_string;

/**
 * Resolves the sizes argument shared by the srcset and sizes helpers: the
 * name of a configured size set, an array with a "sizes" key, or a plain
 * array of sizes.
 *
 * @internal
 */
final readonly class SizeOptions
{
    /**
     * @param array<string, array<int|string, int|string>> $configured
     *
     * @return array<int|string, int|string>
     *
     * @mago-expect analysis:mixed-assignment Template input is untyped; validated here.
     */
    public static function resolve(mixed $options, array $configured): array
    {
        if (is_string($options)) {
            return $configured[$options] ?? [];
        }

        if (! is_array($options)) {
            return [];
        }

        $sizes = $options['sizes'] ?? $options;
        if (is_string($sizes)) {
            return $configured[$sizes] ?? [];
        }

        return is_array($sizes) ? SizeSets::sizes($sizes) : [];
    }
}
