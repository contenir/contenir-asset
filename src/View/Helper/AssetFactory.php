<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Contenir\Asset\AssetManager;
use Contenir\Asset\AssetManagerInterface;
use Contenir\Asset\Container\AssetConfig;
use Contenir\Asset\Container\Services;
use Contenir\Asset\Exception\RuntimeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @api
 */
final readonly class AssetFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws RuntimeException
     */
    public function __invoke(ContainerInterface $container): Asset
    {
        $manager = AssetConfig::from($container)->stringOr('asset.asset_manager', AssetManager::class);

        return new Asset(Services::get($container, $manager, AssetManagerInterface::class));
    }
}
