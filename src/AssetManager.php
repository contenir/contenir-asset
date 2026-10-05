<?php

declare(strict_types=1);

namespace Contenir\Asset;

use Contenir\Asset\Exception\RuntimeException;
use Contenir\Asset\Model\AssetLinker;
use Contenir\Asset\Model\Entity\BaseAssetEntity;
use Contenir\Asset\Storage\AssetSource;
use Contenir\Asset\Storage\AssetStorage;
use Contenir\Asset\Storage\SafeFilename;
use Contenir\Db\Model\EntityManager;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Override;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

use function array_values;
use function pathinfo;

use const PATHINFO_FILENAME;

/**
 * Creates, stores, updates and removes assets: rows through the entity
 * manager, files through {@see AssetStorage}. Every write runs in a
 * transaction.
 *
 * @api
 */
final readonly class AssetManager implements AssetManagerInterface
{
    public const string DOCUMENT_TYPE_IMAGE    = 'image';
    public const string DOCUMENT_TYPE_DOCUMENT = 'document';

    /**
     * @param class-string<BaseAssetEntity> $assetClass the application's asset entity
     */
    public function __construct(
        private EntityManager $em,
        private string $assetClass,
        private AssetStorage $storage,
        private AssetLinker $linker,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Create and save an asset linked to $target and, optionally, to the
     * uploading user and a parent record (their key columns are copied
     * onto matching asset columns). The asset entity class must be
     * constructible without arguments.
     *
     * @throws Throwable
     *
     * @mago-expect analysis:unsafe-instantiation Asset entities are documented as argument-less constructible.
     */
    public function addAsset(
        ResourceInterface $target,
        ?object $user = null,
        ?object $parent = null,
        string $type = self::DOCUMENT_TYPE_IMAGE,
    ): BaseAssetEntity {
        $asset       = new $this->assetClass();
        $asset->type = $type;
        foreach ([$target, $user, $parent] as $owner) {
            if (null === $owner) {
                continue;
            }

            $this->linker->link($asset, $owner);
        }

        $this->saveAsset($asset);

        return $asset;
    }

    /**
     * Delete the asset's row. Its files are kept; call
     * {@see self::removeAsset()} to delete them.
     *
     * @throws Throwable
     */
    public function deleteAsset(BaseAssetEntity $asset): void
    {
        $this->em->transactional(
            /** @throws Throwable */
            fn(): null => $this->em->delete($asset),
        );
    }

    /**
     * Assets of a type, in sequence order.
     *
     * @return list<BaseAssetEntity>
     *
     * @throws Throwable
     */
    #[Override]
    public function findByType(string $type): array
    {
        return $this->em->getRepository($this->assetClass)->findBy(['type' => $type], [
            'sequence' => 'ASC',
            'assetId'  => 'ASC',
        ]);
    }

    /**
     * @throws Throwable
     */
    #[Override]
    public function findOneById(int|string $assetId): ?BaseAssetEntity
    {
        return $this->em->getRepository($this->assetClass)->find($assetId);
    }

    /**
     * Delete the asset's files (original, large copy and thumbnail). The
     * row is kept; call {@see self::deleteAsset()} to delete it.
     *
     * @throws RuntimeException When a file cannot be deleted.
     */
    public function removeAsset(BaseAssetEntity $asset): void
    {
        $this->storage->remove($asset->path, $asset->imageLg, $asset->thumbnail);
        $this->logger->notice('Removed asset files', ['asset_id' => $asset->assetId, 'path' => $asset->path]);
    }

    /**
     * @throws Throwable
     */
    public function saveAsset(BaseAssetEntity $asset): void
    {
        $this->em->transactional(
            /** @throws Throwable */
            fn(): null => $this->em->save($asset),
        );
    }

    /**
     * Set each asset's sequence to its position in $assetIds (from 1).
     * Unknown ids are skipped.
     *
     * @param list<int|string> $assetIds
     *
     * @throws Throwable
     */
    public function sequenceAsset(array $assetIds): void
    {
        $this->em->transactional(
            /** @throws Throwable */
            function () use ($assetIds): void {
                foreach (array_values($assetIds) as $index => $assetId) {
                    $asset = $this->findOneById($assetId);
                    if (null === $asset) {
                        continue;
                    }

                    $asset->sequence = $index + 1;
                    $this->em->save($asset);
                }
            },
        );
    }

    /**
     * Copy a file (a path or a PHP upload array) into the target's asset
     * folder, generate image derivatives, record the paths on the asset
     * and save it.
     *
     * @param string|array{name: string, tmp_name: string} $source
     * @param string|null                                   $constraints "WIDTHxHEIGHT" to fill-and-crop the large copy
     *
     * @throws Throwable
     */
    public function storeAsset(
        BaseAssetEntity $asset,
        ResourceInterface $target,
        string $type,
        string|array $source,
        ?string $constraints = null,
    ): BaseAssetEntity {
        $file   = AssetSource::from($source);
        $stored = $this->storage->store($file, $this->linker->folderFor($target), $type, $constraints);

        $asset->title     ??= SafeFilename::from(pathinfo($file->filename, PATHINFO_FILENAME));
        $asset->path      = $stored->path;
        $asset->mimeType  = $stored->mimeType;
        $asset->active    = 'active';
        $asset->imageLg   = $stored->imageLg;
        $asset->thumbnail = $stored->thumbnail;
        $this->saveAsset($asset);

        $this->logger->notice('Stored asset file', ['asset_id' => $asset->assetId, 'path' => $stored->path]);

        return $asset;
    }

    /**
     * Assign property values (by property name) and save.
     *
     * @param array<string, mixed> $values
     *
     * @throws Throwable
     */
    public function updateAsset(BaseAssetEntity $asset, ?object $user = null, array $values = []): void
    {
        $this->linker->assign($asset, $values);
        $this->saveAsset($asset);
        $this->logger->notice('Updated asset', [
            'asset_id' => $asset->assetId,
            'user'     => null === $user ? null : $user::class,
        ]);
    }
}
