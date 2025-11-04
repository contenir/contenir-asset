<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Contenir\Asset\Service\AssetUrlGenerator;
use Psr\Container\ContainerInterface;

class AssetSrcSetFactory
{
    public function __invoke(ContainerInterface $container): AssetSrcSet
    {
        $config = $container->get('config')['contenir_asset'] ?? [];

        $urlGenerator = $container->get(AssetUrlGenerator::class);

        return new AssetSrcSet($urlGenerator, [
            'presets' => $config['presets'] ?? [],
            'default_formats' => $config['default_formats'] ?? ['avif', 'webp', 'jpg'],
        ]);
    }
}