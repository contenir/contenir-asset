<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Integration;

use Contenir\Asset\AssetManager;
use Contenir\Asset\AssetManagerFactory;
use Contenir\Asset\Exception\InvalidArgumentException;
use Contenir\Asset\Exception\RuntimeException;
use Contenir\Asset\View\Helper\Asset as AssetHelper;
use Contenir\Asset\View\Helper\AssetFactory;
use Contenir\Asset\View\Helper\AssetSizes;
use Contenir\Asset\View\Helper\AssetSizesFactory;
use Contenir\Db\Model\EntityManager;
use Contenir\Db\Model\Metadata\MetadataFactoryInterface;
use ContenirTest\Asset\TestAsset\Container\InMemoryContainer;
use ContenirTest\Asset\TestAsset\Entity\Asset;
use ContenirTest\Asset\TestAsset\Entity\Entry;
use ContenirTest\Asset\Trait\AssetDatabaseTrait;
use ContenirTest\Asset\Trait\TempDirectoryTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(AssetManagerFactory::class)]
#[CoversClass(AssetFactory::class)]
#[CoversClass(AssetSizesFactory::class)]
#[CoversClass(InvalidArgumentException::class)]
#[CoversClass(RuntimeException::class)]
#[Group('integration')]
final class FactoriesTest extends TestCase
{
    use AssetDatabaseTrait;
    use TempDirectoryTrait;

    #[Test]
    public function buildsManagerWithoutConverterOrLogger(): void
    {
        static::assertSame(
            1,
            (new AssetManagerFactory())($this->container(['entity' => Asset::class]))->findOneById(1)?->assetId,
        );
    }

    #[Test]
    public function buildsWorkingManagerAndAssetHelper(): void
    {
        $logger    = $this->createStub(LoggerInterface::class);
        $container = $this->container(['entity' => Asset::class, 'convert' => 'convert', 'logger' => 'log'], [
            'log' => $logger,
        ]);
        $manager = (new AssetManagerFactory())($container);
        $helper  = (new AssetFactory())(new InMemoryContainer([
            ...[AssetManager::class => $manager],
            'config' => [],
        ]));

        static::assertSame([1, AssetHelper::class], [$helper(1)?->assetId, $helper::class]);
    }

    #[Test]
    public function managerRejectsNonAssetEntity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('"' . Entry::class . '" must extend');

        (new AssetManagerFactory())($this->container(['entity' => Entry::class]));
    }

    #[Test]
    public function managerRequiresAnEntityClass(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration "asset.entity" is required');

        (new AssetManagerFactory())($this->container([]));
    }

    #[Test]
    public function sizesFactoryReadsNamedSets(): void
    {
        $helper = (new AssetSizesFactory())(new InMemoryContainer([
            'config' => [
                'view_helper_config' => ['assetsizes' => ['sizes' => ['one' => ['default' => 300]]]],
            ],
        ]));

        static::assertSame([AssetSizes::class, '300px'], [$helper::class, $helper('one')]);
    }

    protected function setUp(): void
    {
        $this->setUpTempDirectory();
        $this->setUpAssetDatabase("INSERT INTO asset (asset_id, type) VALUES (1, 'image')");
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
    }

    /**
     * @param array<string, mixed> $asset
     * @param array<string, mixed> $services
     */
    private function container(array $asset, array $services = []): InMemoryContainer
    {
        return new InMemoryContainer([
            'config'                        => ['asset' => $asset + ['public_path' => $this->publicPath]],
            EntityManager::class            => $this->em,
            MetadataFactoryInterface::class => $this->metadata,
            ...$services,
        ]);
    }
}
