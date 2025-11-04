<?php

declare(strict_types=1);

namespace Contenir\Asset\Service;

use Psr\Container\ContainerInterface;

class AssetUrlGeneratorFactory
{
    public function __invoke(ContainerInterface $container): AssetUrlGenerator
    {
        $config = $container->get('config')['contenir_asset'] ?? [];
        $cachePath = $config['cache']['path'] ?? '/cache/images';

        return new AssetUrlGenerator($cachePath);
    }
}