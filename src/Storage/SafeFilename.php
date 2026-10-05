<?php

declare(strict_types=1);

namespace Contenir\Asset\Storage;

use Transliterator;

use function class_exists;
use function preg_replace;
use function strtolower;
use function trim;

/**
 * Turns an arbitrary (often user-supplied) file name into one that is safe
 * in a URL and on any file system: lower-case ASCII letters, digits, "-"
 * and "_" only. Accented letters are transliterated when the intl
 * extension is available ("Zoë" becomes "zoe"), and dropped otherwise.
 *
 * @api
 */
final readonly class SafeFilename
{
    /**
     * @param string $name     file name without extension
     * @param string $fallback used when nothing safe remains
     */
    public static function from(string $name, string $fallback = 'asset'): string
    {
        $slug =
            preg_replace(
                pattern: '/[^a-z0-9_]+/',
                replacement: '-',
                subject: strtolower(self::ascii($name)),
            ) ?? '';
        $slug = trim($slug, characters: '-_');

        return '' === $slug ? $fallback : $slug;
    }

    private static function ascii(string $name): string
    {
        $transliterator = class_exists(Transliterator::class) ? Transliterator::create('Any-Latin; Latin-ASCII') : null;
        $ascii          = $transliterator?->transliterate($name);

        return false === $ascii || null === $ascii ? $name : $ascii;
    }
}
