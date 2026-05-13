<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Psr\Container\ContainerInterface;

class AssetAspectFactory
{
    public function __invoke(ContainerInterface $container): AssetAspect
    {
        $config     = $container->has('config') ? $container->get('config') : [];
        $publicPath = (string) ($config['asset']['cache']['public_path'] ?? './public');

        return new AssetAspect($publicPath);
    }
}
