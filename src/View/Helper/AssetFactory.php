<?php

namespace Contenir\Asset\View\Helper;

use Psr\Container\ContainerInterface;

class AssetFactory
{
    public function __invoke(ContainerInterface $container): Asset
    {
        $config            = $container->get('config')['asset'] ?? [];
        $assetManagerClass = $config['asset_manager']           ?? null;
        $assetManager      = $container->get($assetManagerClass);

        return new Asset($assetManager);
    }
}
