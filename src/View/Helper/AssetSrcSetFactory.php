<?php

namespace Contenir\Asset\View\Helper;

use Psr\Container\ContainerInterface;

class AssetSrcSetFactory
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName
    ): AssetSrcSet {
        $config = $container->get('config') ?? [];
        $helper = new AssetSrcSet($config);

        if (isset($config['view_helper_config']['assetsrcset'])) {
            $configHelper = $config['view_helper_config']['assetsrcset'];
            if (isset($configHelper['root_path'])) {
                $helper->setRootPath($configHelper['root_path']);
            }
            if (isset($configHelper['sizes'])) {
                $helper->setSizes($configHelper['sizes']);
            }
        }

        return $helper;
    }
}
