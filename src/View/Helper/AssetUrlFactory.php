<?php

namespace Contenir\Asset\View\Helper;

use Contenir\Asset\View\Helper\AssetUrl;
use Psr\Container\ContainerInterface;

class AssetUrlFactory
{
    public function __invoke(ContainerInterface $container): AssetUrl
    {
        $config = $container->get('config')['asset']['cdn'] ?? [];

        return new AssetUrl($config);
    }
}
