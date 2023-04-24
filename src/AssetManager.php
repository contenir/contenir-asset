<?php

namespace Contenir\Asset;

use Contenir\Asset\AssetEntity;
use Contenir\Asset\AssetRepository;
use RuntimeException;

/**
 * The AuthManager service is responsible for user's login/logout and simple access
 * filtering. The access filtering feature checks whether the current visitor
 * is allowed to see the given page or not.
 */
class AssetManager implements AssetManagerInterface
{
    public const DOCUMENT_TYPE_IMAGE     = 'image';
    public const DOCUMENT_TYPE_DOCUMENT  = 'document';

    /**
     * User Repository
     * @var \Application\Repository\AssetRepository
     */
    private $assetRepository;

    /**
     * Constructs the service.
     */
    public function __construct(
        AssetRepository $assetRepository
    ) {
        $this->assetRepository  = $assetRepository;
    }

    public function findOneById($assetId)
    {
        return $this->assetRepository->findOne([
            'asset_id' => $assetId
        ]);
    }

    public function findByType($typeId)
    {
        return $this->assetRepository->find([
            'type' => $typeId
        ]);
    }

    protected function getAssetPath(
        ResourceInterface $targetEntity,
        $type = self::DOCUMENT_TYPE_IMAGE
    ) {
        $folderName             = 'asset';
        if ($targetEntity) {
            $folderName = sprintf(
                '%s-%s',
                $targetEntity->getResourceId(),
                join('-', $targetEntity->getPrimaryKeys())
            );
        }

        $assetPath              = sprintf(
            '/asset/user/%s/%s',
            $folderName,
            $type
        );

        if (! file_exists('./public' . $assetPath)) {
            $result             = mkdir('./public' . $assetPath, 0777, true);
            if (! $result) {
                throw new RuntimeException(sprintf(
                    'Cannot write to target path %s',
                    $assetPath
                ));
            }
        }

        return $assetPath;
    }

    public function sequenceAsset($data = [])
    {
        $this->commitTransaction(function () use ($data) {
            foreach ($data as $index => $assetId) {
                $this->updateLookup('asset', [
                    'sequence' => $index + 1
                ], [
                    'asset_id' => $assetId
                ]);
            }
        });

        return true;
    }

    public function addAsset(
        ResourceInterface $targetEntity,
        UserEntity $userEntity,
        AbstractEntity $parentEntity = null,
        $type = AssetManager::DOCUMENT_TYPE_IMAGE
    ) {
        $asset                  = $this->assetRepository->create();
        $asset->populate([
            'type' => $type
        ]);

        if ($targetEntity) {
            foreach ($targetEntity->getPrimaryKeys() as $key => $value) {
                $asset->{$key} = $value;
            }
        }

        if ($userEntity) {
            $asset->user_id     = $userEntity->user_id;
        }

        if ($parentEntity) {
            foreach ($parentEntity->getPrimaryKeys() as $key => $value) {
                $asset->{$key} = $value;
            }
        }

        $this->assetRepository->save($asset);

        return $asset;
    }

    public function updateAsset(
        AssetEntity $asset,
        UserEntity $user = null,
        array $values = [],
        array $data = []
    ) {
        $asset->populate($values);

        $this->saveAsset(
            $asset,
            $data
        );

        $this->getLogger()->notice(sprintf(
            'Updated artwork data'
        ), [
            'user_id' => $user->user_id ?? null,
            'entry_id' => $asset->entry_id ?? null
        ]);
    }

    public function saveAsset(AssetEntity $asset)
    {
        $this->commitTransaction(function () use ($asset) {
            $this->assetRepository->save($asset);
        });
    }

    public function deleteAsset(AssetEntity $asset)
    {
        $this->commitTransaction(function () use ($asset) {
            $this->assetRepository->delete([
                'asset_id' => $asset->asset_id
            ]);
        });
    }

    public function storeAsset(
        AssetEntity $asset,
        ResourceInterface $targetEntity,
        $type,
        $srcPath,
        $constraints = null
    ) {
        if (is_array($srcPath)) {
            $srcFilename        = $srcPath['name'];
            $srcPath            = $srcPath['tmp_name'];
        } else {
            $srcFilename        = basename($srcPath);
        }

        $srcPathParts           = pathinfo($srcFilename);

        $filePath               = $this->getAssetPath($targetEntity, $type);
        $fileName               = $this->getSafeFilename($srcPathParts['filename']);

        $ext                    = $srcPathParts['extension'] ?? null;

        if (! $asset->title) {
            $asset->title       = $this->getSafeFilename($srcFilename);
        }
        $asset->path            = sprintf('%s/%s.%s', $filePath, $fileName, $ext);
        $asset->mime_type       = mime_content_type($srcPath);
        $asset->active          = 'active';

        $result                 = @copy($srcPath, './public' . $asset->path);
        if ($result === false) {
            $errorInfo          = error_get_last();
            throw new RuntimeException(sprintf('Cannot copy file - %s', $errorInfo['message']));
        }

        $convertExec = "convert";

        if ($convertExec) {
            $asset->image_lg    = sprintf('%s/%s-lg.%s', $filePath, $fileName, 'jpg');
            $asset->thumbnail   = sprintf('%s/%s-sm.%s', $filePath, $fileName, 'jpg');

            if (! file_exists('./public' . $filePath)) {
                $result = mkdir('./public' . $filePath, 0777, true);
                if (! $result) {
                    throw new RuntimeException(sprintf(
                        'Cannot write to target path %s',
                        $filePath
                    ));
                }
            }

            if ($constraints) {
                exec($convertExec . ' "' . realpath('./public') . $asset->path . '"[0] -colorspace sRGB -strip -resize ' . $constraints . '^ -gravity center -extent ' . $constraints . ' -unsharp 0x0.75 -quality 85% "' . realpath('./public') . $asset->image_lg . '"');
            } else {
                exec($convertExec . ' "' . realpath('./public') . $asset->path . '"[0] -colorspace sRGB -strip -resize 2000x2000 -unsharp 0x0.75 -quality 85% "' . realpath('./public') . $asset->image_lg . '"');
            }

            exec($convertExec . ' "' . realpath('./public') . $asset->image_lg . '"[0] -colorspace sRGB -strip -resize \'540x540^>\' -gravity center -unsharp 0x0.75 -crop 540x540+0+0 "' . realpath('./public') . $asset->thumbnail . '"');
        }

        $this->saveAsset($asset);

        $this->getLogger()->notice(sprintf(
            'Added new artwork to %s',
            $filePath
        ), [
            'entry_id' => $asset->entry_id ?? null
        ]);

        return $asset;
    }

    public function removeAsset(
        AssetEntity $asset,
        UserEntity $user = null
    ) {
        if ($asset->path) {
            @unlink('./public' . $asset->path);
        }

        if ($asset->image_lg) {
            @unlink('./public' . $asset->image_lg);
        }

        if ($asset->thumbnail) {
            @unlink('./public' . $asset->thumbnail);
        }

        $this->getLogger()->notice(sprintf(
            'Removed artwork from %s',
            $asset->path
        ), [
            'user_id' => $user->user_id ?? null,
            'entry_id' => $asset->entry_id ?? null
        ]);
    }
}
