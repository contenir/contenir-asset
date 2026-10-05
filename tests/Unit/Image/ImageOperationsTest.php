<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Unit\Image;

use Contenir\Asset\Exception\InvalidArgumentException;
use Contenir\Asset\Image\ImageOperations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImageOperations::class)]
#[CoversClass(InvalidArgumentException::class)]
#[Group('unit')]
final class ImageOperationsTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function invalidGeometryProvider(): array
    {
        return [
            'empty'               => [''],
            'no separator'        => ['800'],
            'shell metacharacter' => ['800x600; rm -rf /'],
            'negative'            => ['-800x600'],
            'height only'         => ['x600'],
        ];
    }

    #[Test]
    public function largeFillsAndCropsToConstraints(): void
    {
        static::assertSame(
            [
                '-colorspace',
                'sRGB',
                '-strip',
                '-resize',
                '800x600^',
                '-gravity',
                'center',
                '-extent',
                '800x600',
                '-unsharp',
                '0x0.75',
                '-quality',
                '85%',
            ],
            ImageOperations::large('800x600'),
        );
    }

    #[Test]
    public function largeFitsWithin2000PixelsByDefault(): void
    {
        static::assertSame(
            ['-colorspace', 'sRGB', '-strip', '-resize', '2000x2000', '-unsharp', '0x0.75', '-quality', '85%'],
            ImageOperations::large(),
        );
    }

    #[DataProvider('invalidGeometryProvider')]
    #[Test]
    public function rejectsMalformedGeometry(string $geometry): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must look like "800x600"');

        ImageOperations::geometry($geometry);
    }

    #[Test]
    public function resizeFitsWithinDimensions(): void
    {
        static::assertSame(
            ['-colorspace', 'sRGB', '-strip', '-resize', '480x', '-unsharp', '0x0.75', '-quality', '85%'],
            ImageOperations::resize('480x'),
        );
    }

    #[Test]
    public function thumbnailIsA540PixelCentreCrop(): void
    {
        static::assertContains('540x540+0+0', ImageOperations::thumbnail());
    }
}
