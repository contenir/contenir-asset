<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Psr\Container\ContainerInterface;

class AssetSizeFactory
{
    public function __invoke(ContainerInterface $container): AssetSize
    {
        $config     = $container->has('config') ? $container->get('config') : [];
        $publicPath = (string) ($config['asset']['cache']['public_path'] ?? './public');

        return new AssetSize($publicPath);
    }
}
