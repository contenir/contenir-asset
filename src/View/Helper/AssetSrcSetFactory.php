<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Contenir\Asset\Container\AssetConfig;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @api
 */
final readonly class AssetSrcSetFactory
{
    /**
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:deprecated-class Builds the deprecated legacy helper for sites still using it.
     */
    public function __invoke(ContainerInterface $container): AssetSrcSet
    {
        $config = AssetConfig::from($container);

        return new AssetSrcSet(
            $config->string('view_helper_config.assetsrcset.root_path')
            ?? $config->stringOr('asset.public_path', './public'),
            $config->sizeSets('view_helper_config.assetsrcset.sizes'),
        );
    }
}
