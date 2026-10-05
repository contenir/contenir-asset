<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Contenir\Asset\Container\AssetConfig;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @api
 */
final readonly class AssetAspectFactory
{
    /**
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): AssetAspect
    {
        $config = AssetConfig::from($container);

        return new AssetAspect(
            $config->string('asset.public_path')
            ?? $config->stringOr('asset.cache.public_path', './public'),
        );
    }
}
