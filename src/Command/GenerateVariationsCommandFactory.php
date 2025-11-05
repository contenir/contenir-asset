<?php

declare(strict_types=1);

namespace Contenir\Asset\Command;

use Contenir\Asset\Service\ImageCache;
use Contenir\Asset\Service\ImageProcessor;
use Psr\Container\ContainerInterface;

class GenerateVariationsCommandFactory
{
    public function __invoke(ContainerInterface $container): GenerateVariationsCommand
    {
        $config = $container->get('config');
        $assetConfig = $config['contenir_asset'] ?? [];

        return new GenerateVariationsCommand(
            $container->get(ImageProcessor::class),
            $container->get(ImageCache::class),
            $assetConfig,
            $assetConfig['public_path'] ?? getcwd() . '/public'
        );
    }
}