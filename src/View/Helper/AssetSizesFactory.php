<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Psr\Container\ContainerInterface;

class AssetSizesFactory
{
    public function __invoke(ContainerInterface $container): AssetSizes
    {
        $config = $container->get('config')['contenir_asset'] ?? [];

        // Convert size presets to sizes format if needed
        $sizesPresets = [];
        foreach ($config['presets'] ?? [] as $name => $preset) {
            if (isset($preset['sizes_attr'])) {
                $sizesPresets[$name] = $preset['sizes_attr'];
            }
        }

        return new AssetSizes(['presets' => $sizesPresets]);
    }
}