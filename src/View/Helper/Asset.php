<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Contenir\Asset\AssetManagerInterface;
use Contenir\Asset\Model\Entity\BaseAssetEntity;
use Throwable;

/**
 * Loads an asset by id in templates: `$this->asset($id)?->path`.
 *
 * @api
 */
final class Asset
{
    public function __construct(
        private readonly AssetManagerInterface $assetManager,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(int|string|null $assetId): ?BaseAssetEntity
    {
        return null === $assetId || '' === $assetId ? null : $this->assetManager->findOneById($assetId);
    }
}
