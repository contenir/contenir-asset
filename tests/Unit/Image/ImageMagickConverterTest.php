<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Unit\Image;

use Contenir\Asset\Exception\RuntimeException;
use Contenir\Asset\Image\ImageMagickConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImageMagickConverter::class)]
#[CoversClass(RuntimeException::class)]
#[Group('unit')]
final class ImageMagickConverterTest extends TestCase
{
    #[Test]
    public function defaultRunnerExecutesTheBinary(): void
    {
        (new ImageMagickConverter('true'))->convert('/in.jpg', '/out.jpg', []);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("exit code 1: 'false'");

        (new ImageMagickConverter('false'))->convert('/in.jpg', '/out.jpg', []);
    }

    #[Test]
    public function escapesEveryArgumentAndUsesTheFirstFrame(): void
    {
        $commands  = [];
        $converter = new ImageMagickConverter('/usr/bin/convert', static function (string $command) use (
            &$commands,
        ): int {
            $commands[] = $command;

            return 0;
        });

        $converter->convert('/in/my file.pdf', "/out/it's.jpg", ['-resize', '480x']);

        static::assertSame(["'/usr/bin/convert' '/in/my file.pdf[0]' '-resize' '480x' '/out/it'\\''s.jpg'"], $commands);
    }

    #[Test]
    public function failedCommandThrows(): void
    {
        $converter = new ImageMagickConverter('convert', static fn(string $command): int => 1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Image conversion failed with exit code 1');

        $converter->convert('/in.jpg', '/out.jpg', []);
    }
}
