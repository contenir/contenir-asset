<?php

namespace Contenir\Asset\View\Helper;

use Psr\Container\ContainerInterface;

class AssetSrcSetFactory
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName
    ) {
        $config = $container->get('config')['asset']['srcset'] ?? [];

        return new $requestedName($config);
    }
}
