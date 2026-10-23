<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Integration\Image;

use Contenir\Asset\Exception\RuntimeException;
use Contenir\Asset\Image\LegacyResizePaths;
use Contenir\Asset\Image\LegacyResizer;
use Contenir\Asset\Image\LegacyResizerFactory;
use Contenir\Asset\Storage\Filesystem;
use ContenirTest\Asset\TestAsset\Container\InMemoryContainer;
use ContenirTest\Asset\TestAsset\Image\FakeImageConverter;
use ContenirTest\Asset\Trait\TempDirectoryTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function is_file;
use function realpath;

/**
 * @mago-expect analysis:deprecated-class Tests the deprecated legacy resizer.
 */
#[CoversClass(LegacyResizer::class)]
#[CoversClass(LegacyResizerFactory::class)]
#[CoversClass(LegacyResizePaths::class)]
#[CoversClass(Filesystem::class)]
#[CoversClass(RuntimeException::class)]
#[Group('integration')]
final class LegacyResizerTest extends TestCase
{
    use TempDirectoryTrait;

    private FakeImageConverter $converter;

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function rejectedRequestProvider(): array
    {
        return [
            'missing file'         => ['gallery', '20x', 'none.png'],
            'path traversal'       => ['..', '20x', 'secret.txt'],
            'malformed dimensions' => ['gallery', '20;rm', 'photo.png'],
            'no parameters'        => ['', '', ''],
        ];
    }

    /**
     * @mago-expect analysis:deprecated-class Tests the deprecated legacy resizer factory.
     */
    #[Test]
    public function factoryConfiguresTheConverterFromTheSrcsetHelper(): void
    {
        $resizer = (new LegacyResizerFactory())(new InMemoryContainer([
            'config' => ['asset' => ['public_path' => $this->publicPath, 'srcset' => ['helper' => '/usr/bin/convert']]],
        ]));

        static::assertTrue($resizer->isConfigured());
    }

    /**
     * @mago-expect analysis:deprecated-class Tests the deprecated legacy resizer factory.
     */
    #[Test]
    public function factoryLeavesResizingUnconfiguredWithoutAHelper(): void
    {
        $resizer = (new LegacyResizerFactory())(new InMemoryContainer([
            'config' => ['asset' => ['public_path' => $this->publicPath]],
        ]));

        static::assertSame([false, null], [$resizer->isConfigured(), $resizer->resize('gallery', '20x', 'photo.png')]);
    }

    #[Test]
    public function generatesTheResizedImageAndNamesIt(): void
    {
        $destination = $this->resizer()->resize('gallery', '20x', 'photo.png');

        static::assertSame(
            [realpath($this->publicPath) . '/asset/gallery/.20x/photo.png', true, '20x'],
            [
                $destination,
                null !== $destination && is_file($destination),
                $this->converter->calls[0]['operations'][4] ?? null,
            ],
        );
    }

    #[Test]
    public function namesAnExistingResizeWithoutConverting(): void
    {
        $this->createFile('/asset/gallery/.20x/photo.png', 'cached');

        $destination = $this->resizer()->resize('gallery', '20x', 'photo.png');

        static::assertSame(['cached', []], [file_get_contents((string) $destination), $this->converter->calls]);
    }

    #[DataProvider('rejectedRequestProvider')]
    #[Test]
    public function refusesUnsafeOrMissingFiles(string $folder, string $dimensions, string $filename): void
    {
        static::assertSame([null, []], [
            $this->resizer()->resize($folder, $dimensions, $filename),
            $this->converter->calls,
        ]);
    }

    #[Test]
    public function reportsADestinationThatCannotBeCreated(): void
    {
        $this->createFile('/asset/gallery/.20x', 'a file where the directory should be');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Cannot create/');

        $this->resizer()->resize('gallery', '20x', 'photo.png');
    }

    protected function setUp(): void
    {
        $this->setUpTempDirectory();
        $this->converter = new FakeImageConverter();
        $this->createImage('/asset/gallery/photo.png', 40, 20);
        $this->createFile('/secret.txt', 'top secret');
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
    }

    /**
     * @mago-expect analysis:deprecated-class Tests the deprecated legacy resizer.
     */
    private function resizer(): LegacyResizer
    {
        return new LegacyResizer($this->publicPath, $this->converter);
    }
}
