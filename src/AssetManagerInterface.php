<?php

declare(strict_types=1);

namespace Contenir\Asset;

use Contenir\Asset\Model\Entity\BaseAssetEntity;

/**
 * Read access to assets, as used by the asset() view helper.
 *
 * @api
 */
interface AssetManagerInterface
{
    /**
     * @return list<BaseAssetEntity>
     */
    public function findByType(string $type): array;

    public function findOneById(int|string $assetId): ?BaseAssetEntity;
}
