<?php

declare(strict_types=1);

namespace Contenir\Asset\Service;

use Psr\Container\ContainerInterface;

class ImageCacheFactory
{
    public function __invoke(ContainerInterface $container): ImageCache
    {
        $config = $container->get('config')['contenir_asset'] ?? [];

        $cachePath = $config['cache']['path'] ?? '/cache/images';
        $publicPath = $config['cache']['public_path'] ?? './public';

        return new ImageCache($cachePath, $publicPath);
    }
}