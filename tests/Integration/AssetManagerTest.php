<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Integration;

use Contenir\Asset\AssetManager;
use Contenir\Asset\Exception\InvalidArgumentException;
use Contenir\Asset\Model\AssetLinker;
use Contenir\Asset\Model\Entity\BaseAssetEntity;
use Contenir\Asset\Storage\AssetSource;
use Contenir\Asset\Storage\AssetStorage;
use Contenir\Asset\Storage\SafeFilename;
use ContenirTest\Asset\TestAsset\Entity\Asset;
use ContenirTest\Asset\TestAsset\Entity\Entry;
use ContenirTest\Asset\TestAsset\Entity\Gallery;
use ContenirTest\Asset\TestAsset\Entity\Member;
use ContenirTest\Asset\TestAsset\Image\FakeImageConverter;
use ContenirTest\Asset\Trait\AssetDatabaseTrait;
use ContenirTest\Asset\Trait\TempDirectoryTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

use function count;
use function is_file;
use function pathinfo;

use const PATHINFO_FILENAME;

#[CoversClass(AssetManager::class)]
#[CoversClass(AssetLinker::class)]
#[CoversClass(BaseAssetEntity::class)]
#[CoversClass(InvalidArgumentException::class)]
#[Group('integration')]
final class AssetManagerTest extends TestCase
{
    use AssetDatabaseTrait;
    use TempDirectoryTrait;

    private AssetManager $manager;

    private FakeImageConverter $converter;

    #[Test]
    public function addAssetLinksTargetAndUserByKeyColumns(): void
    {
        $asset = $this->manager->addAsset($this->entry(), $this->member(), type: AssetManager::DOCUMENT_TYPE_DOCUMENT);

        static::assertSame(
            [[['asset_id' => 4, 'type' => 'document', 'user_id' => 3, 'entry_id' => 7]], Asset::class],
            [$this->rows('SELECT asset_id, type, user_id, entry_id FROM asset WHERE asset_id = 4'), $asset::class],
        );
    }

    #[Test]
    public function addAssetSkipsKeysTheAssetDoesNotMapAndUnsavedOwners(): void
    {
        $parent           = new Asset();
        $unsaved          = new Entry();
        $unsaved->entryId = 9;

        $asset = $this->manager->addAsset($unsaved, null, $parent);

        static::assertSame([9, null], [$asset->entryId, $asset->userId]);
    }

    #[Test]
    public function assetSourceAndSafeFilenameAreUsedForUploads(): void
    {
        static::assertSame(
            'holiday',
            SafeFilename::from(pathinfo(AssetSource::from('/x/Holiday.JPG')->filename, PATHINFO_FILENAME)),
        );
    }

    #[Test]
    public function deleteAssetRemovesTheRowOnly(): void
    {
        $this->createFile('/b.jpg', 'b');
        $asset = $this->manager->findOneById(1);
        static::assertNotNull($asset);

        $this->manager->deleteAsset($asset);

        static::assertSame([[], true], [
            $this->rows('SELECT * FROM asset WHERE asset_id = 1'),
            is_file("{$this->publicPath}/b.jpg"),
        ]);
    }

    #[Test]
    public function findsAssetsByIdAndByTypeInSequenceOrder(): void
    {
        $byType = $this->manager->findByType('image');

        static::assertSame(
            ['b', null, [2, 1]],
            [
                $this->manager->findOneById(1)?->title,
                $this->manager->findOneById(99),
                [$byType[0]->assetId ?? null, $byType[1]->assetId ?? null],
            ],
        );
    }

    #[Test]
    public function folderOfEntityWithoutKeyValueHasEmptyKeyPart(): void
    {
        static::assertSame('entry-', (new AssetLinker($this->metadata))->folderFor(new Entry()));
    }

    #[Test]
    public function folderUsesResourceIdAndKeys(): void
    {
        static::assertSame('entry-7', (new AssetLinker($this->metadata))->folderFor($this->entry()));
    }

    #[Test]
    public function linkSkipsOwnerKeysTheAssetDoesNotMap(): void
    {
        $asset = new Asset();
        (new AssetLinker($this->metadata))->link($asset, new Gallery());

        static::assertSame([null, null, 'asset'], [$asset->entryId, $asset->assetId, $asset->getResourceId()]);
    }

    #[Test]
    public function removeAssetDeletesTheFilesOnly(): void
    {
        $this->createFile('/b.jpg', 'b');
        $asset = $this->manager->findOneById(1);
        static::assertNotNull($asset);

        $this->manager->removeAsset($asset);

        static::assertSame([false, 1], [
            is_file("{$this->publicPath}/b.jpg"),
            count($this->rows('SELECT * FROM asset WHERE asset_id = 1')),
        ]);
    }

    #[Test]
    public function sequenceAssetNumbersAssetsInGivenOrderSkippingUnknownIds(): void
    {
        $this->manager->sequenceAsset([3, 99, '1', 2]);

        static::assertSame(
            [
                ['asset_id' => 1, 'sequence' => 3],
                ['asset_id' => 2, 'sequence' => 4],
                ['asset_id' => 3, 'sequence' => 1],
            ],
            $this->rows('SELECT asset_id, sequence FROM asset ORDER BY asset_id'),
        );
    }

    #[Test]
    public function storeAssetCopiesFileRecordsPathsAndSaves(): void
    {
        $this->createImage('/upload/Holiday Photo.png', 20, 10);
        $asset = $this->manager->addAsset($this->entry());

        $this->manager->storeAsset($asset, $this->entry(), 'image', [
            'name'     => 'Holiday Photo.png',
            'tmp_name' => "{$this->publicPath}/upload/Holiday Photo.png",
        ]);

        static::assertSame(
            [
                [
                    'title'     => 'holiday-photo',
                    'path'      => '/asset/user/entry-7/image/holiday-photo.png',
                    'mime_type' => 'image/png',
                    'active'    => 'active',
                    'image_lg'  => '/asset/user/entry-7/image/holiday-photo-lg.jpg',
                    'thumbnail' => '/asset/user/entry-7/image/holiday-photo-sm.jpg',
                ],
            ],
            $this->rows(
                "SELECT title, path, mime_type, active, image_lg, thumbnail FROM asset WHERE asset_id = {$asset->assetId}",
            ),
        );
    }

    #[Test]
    public function storeAssetKeepsAnExistingTitle(): void
    {
        $this->createImage('/upload/x.png', 5, 5);
        $asset        = $this->manager->addAsset($this->entry());
        $asset->title = 'Kept';

        $this->manager->storeAsset($asset, $this->entry(), 'image', "{$this->publicPath}/upload/x.png");

        static::assertSame('Kept', $asset->title);
    }

    #[Test]
    public function updateAssetAssignsPropertiesAndLogs(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(static::once())->method('notice')->with('Updated asset', static::arrayHasKey('asset_id'));
        $asset = $this->manager->findOneById(1);
        static::assertNotNull($asset);

        $this->manager($logger)->updateAsset($asset, $this->member(), ['title' => 'Renamed', 'sequence' => 9]);

        static::assertSame(
            [['title' => 'Renamed', 'sequence' => 9]],
            $this->rows('SELECT title, sequence FROM asset WHERE asset_id = 1'),
        );
    }

    #[Test]
    public function updateAssetRejectsUnknownProperty(): void
    {
        $asset = $this->manager->findOneById(1);
        static::assertNotNull($asset);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"image_lg" is not a mapped column property of ' . Asset::class);

        $this->manager->updateAsset($asset, values: ['image_lg' => '/x.jpg']);
    }

    #[Test]
    public function updateAssetWithoutUser(): void
    {
        $asset = $this->manager->findOneById(2);
        static::assertNotNull($asset);

        $this->manager->updateAsset($asset, values: ['title' => 'No user']);

        static::assertSame([['title' => 'No user']], $this->rows('SELECT title FROM asset WHERE asset_id = 2'));
    }

    protected function setUp(): void
    {
        $this->setUpTempDirectory();
        $this->setUpAssetDatabase(
            "INSERT INTO entry VALUES (7, 'Entry seven')",
            'INSERT INTO member VALUES (3)',
            "INSERT INTO asset (asset_id, type, title, path, sequence) VALUES (1, 'image', 'b', '/b.jpg', 2)",
            "INSERT INTO asset (asset_id, type, title, path, sequence) VALUES (2, 'image', 'a', '/a.jpg', 1)",
            "INSERT INTO asset (asset_id, type, title, path, sequence) VALUES (3, 'document', 'c', '/c.pdf', 0)",
        );
        $this->converter = new FakeImageConverter();
        $this->manager   = $this->manager();
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
    }

    private function entry(): Entry
    {
        $entry = $this->em->getRepository(Entry::class)->find(7);
        static::assertNotNull($entry);

        return $entry;
    }

    private function manager(?LoggerInterface $logger = null): AssetManager
    {
        $storage = new AssetStorage($this->publicPath, '/asset/user', $this->converter);

        return null === $logger
            ? new AssetManager($this->em, Asset::class, $storage, new AssetLinker($this->metadata))
            : new AssetManager($this->em, Asset::class, $storage, new AssetLinker($this->metadata), $logger);
    }

    private function member(): Member
    {
        $member = $this->em->getRepository(Member::class)->find(3);
        static::assertNotNull($member);

        return $member;
    }
}
