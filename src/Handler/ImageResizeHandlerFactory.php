<?php

declare(strict_types=1);

namespace Contenir\Asset\Handler;

use Contenir\Asset\Service\ImageCache;
use Contenir\Asset\Service\ImageProcessor;
use Psr\Container\ContainerInterface;

class ImageResizeHandlerFactory
{
    public function __invoke(ContainerInterface $container): ImageResizeHandler
    {
        $config = $container->get('config')['contenir_asset'] ?? [];

        $processor = $container->get(ImageProcessor::class);
        $cache = $container->get(ImageCache::class);
        $publicPath = $config['cache']['public_path'] ?? './public';
        $allowedDimensions = $config['allowed_dimensions'] ?? [];

        return new ImageResizeHandler($processor, $cache, $publicPath, $allowedDimensions);
    }
}