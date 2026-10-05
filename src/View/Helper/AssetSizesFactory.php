<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Contenir\Asset\Container\AssetConfig;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @api
 */
final readonly class AssetSizesFactory
{
    /**
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): AssetSizes
    {
        return new AssetSizes(AssetConfig::from($container)->sizeSets('view_helper_config.assetsizes.sizes'));
    }
}
