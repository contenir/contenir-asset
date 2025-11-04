<?php

declare(strict_types=1);

namespace Contenir\Asset\Service;

use Psr\Container\ContainerInterface;

class ImageProcessorFactory
{
    public function __invoke(ContainerInterface $container): ImageProcessor
    {
        $config = $container->get('config')['contenir_asset'] ?? [];

        $imageMagickPath = $config['processor']['imagemagick_path'] ?? '/usr/local/bin/convert';
        $quality = $config['processor']['quality'] ?? [
            'jpg' => 85,
            'webp' => 85,
            'avif' => 75,
        ];
        $optimization = $config['processor']['optimization'] ?? [
            'strip_metadata' => true,
            'progressive' => true,
            'colorspace' => 'sRGB',
        ];

        return new ImageProcessor($imageMagickPath, $quality, $optimization);
    }
}