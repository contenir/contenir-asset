<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Contenir\Asset\Container\AssetConfig;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @api
 */
final readonly class AssetSizeFactory
{
    /**
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): AssetSize
    {
        $config = AssetConfig::from($container);

        return new AssetSize(
            $config->string('asset.public_path')
            ?? $config->stringOr('asset.cache.public_path', './public'),
        );
    }
}
