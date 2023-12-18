<?php

namespace Contenir\Asset\View\Helper;

use Psr\Container\ContainerInterface;

class AssetSizesFactory
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName
    ): AssetSizes {
        $config = $container->get('config') ?? [];
        $helper = new AssetSizes($config);

        if (isset($config['view_helper_config']['assetsizes'])) {
            $configHelper = $config['view_helper_config']['assetsizes'];
            if (isset($configHelper['sizes'])) {
                $helper->setSizes($configHelper['sizes']);
            }
        }

        return $helper;
    }
}
