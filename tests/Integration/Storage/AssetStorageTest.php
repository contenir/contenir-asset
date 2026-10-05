<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Integration\Storage;

use Contenir\Asset\Exception\RuntimeException;
use Contenir\Asset\Storage\AssetSource;
use Contenir\Asset\Storage\AssetStorage;
use Contenir\Asset\Storage\Filesystem;
use Contenir\Asset\Storage\StoredFiles;
use ContenirTest\Asset\TestAsset\Image\FakeImageConverter;
use ContenirTest\Asset\Trait\TempDirectoryTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function count;
use function is_file;

#[CoversClass(AssetStorage::class)]
#[CoversClass(Filesystem::class)]
#[CoversClass(StoredFiles::class)]
#[CoversClass(RuntimeException::class)]
#[Group('integration')]
final class AssetStorageTest extends TestCase
{
    use TempDirectoryTrait;

    #[Test]
    public function copiesSourceUnderSafeNameWithoutDerivatives(): void
    {
        $this->createImage('/upload/My Photo.PNG', 10, 10);
        $storage = new AssetStorage($this->publicPath);

        $stored = $storage->store(
            new AssetSource("{$this->publicPath}/upload/My Photo.PNG", 'My Photo.PNG'),
            'entry-7',
            'image',
        );

        static::assertEquals(
            [new StoredFiles('/asset/user/entry-7/image/my-photo.png', 'image/png'), true],
            [$stored, is_file("{$this->publicPath}/asset/user/entry-7/image/my-photo.png")],
        );
    }

    #[Test]
    public function rejectsMissingSource(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist or is not readable');

        (new AssetStorage($this->publicPath))->store(new AssetSource('/no/such/file.jpg', 'file.jpg'), 'x', 'image');
    }

    #[Test]
    public function removeDeletesExistingFilesAndIgnoresTheRest(): void
    {
        $this->createFile('/asset/a.jpg', 'a');
        $this->createFile('/asset/b.jpg', 'b');

        (new AssetStorage($this->publicPath))->remove('/asset/a.jpg', null, '', '/asset/missing.jpg', 'asset/b.jpg');

        static::assertSame(
            [false, false],
            [is_file("{$this->publicPath}/asset/a.jpg"), is_file("{$this->publicPath}/asset/b.jpg")],
        );
    }

    #[Test]
    public function reportsDirectoryThatCannotBeCreated(): void
    {
        $this->createImage('/upload/a.png', 10, 10);
        $this->createFile('/asset/user/blocked', 'a file where a directory should be');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot create asset directory');

        (new AssetStorage($this->publicPath))->store(
            new AssetSource("{$this->publicPath}/upload/a.png", 'a.png'),
            'blocked',
            'image',
        );
    }

    #[Test]
    public function writesLargeCopyAndThumbnailThroughConverter(): void
    {
        $this->createImage('/upload/a.png', 10, 10);
        $converter = new FakeImageConverter();
        $storage   = new AssetStorage($this->publicPath, '/files', $converter);

        $stored = $storage->store(
            new AssetSource("{$this->publicPath}/upload/a.png", 'a.png'),
            'entry-1',
            'image',
            '800x600',
        );

        static::assertSame(
            ['/files/entry-1/image/a-lg.jpg', '/files/entry-1/image/a-sm.jpg', 2, '800x600'],
            [
                $stored->imageLg,
                $stored->thumbnail,
                count($converter->calls),
                $converter->calls[0]['operations'][8] ?? null,
            ],
        );
    }

    protected function setUp(): void
    {
        $this->setUpTempDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
    }
}
