<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Unit\Storage;

use Contenir\Asset\Storage\SafeFilename;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SafeFilename::class)]
#[Group('unit')]
final class SafeFilenameTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function nameProvider(): array
    {
        return [
            'spaces and case'   => ['Annual Report 2024', 'annual-report-2024'],
            'accents'           => ['Zoë Café', 'zoe-cafe'],
            'path traversal'    => ['../../etc/passwd', 'etc-passwd'],
            'keeps underscores' => ['photo_final', 'photo_final'],
            'nothing safe'      => ['***', 'asset'],
            'empty'             => ['', 'asset'],
            'invalid utf-8'     => ["bad\xffname", 'bad-name'],
        ];
    }

    #[DataProvider('nameProvider')]
    #[Test]
    public function producesUrlSafeNames(string $name, string $expected): void
    {
        static::assertSame($expected, SafeFilename::from($name));
    }

    #[Test]
    public function usesGivenFallback(): void
    {
        static::assertSame('bin', SafeFilename::from('', fallback: 'bin'));
    }
}
