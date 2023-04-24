<?php

namespace Contenir\Asset;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use RuntimeException;

class AssetManagerFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        $config = $container->get('config')['asset'] ?? [];
        if (empty($config)) {
            throw new RuntimeException('No resource config provided');
        }

        $assetRepository = $container->get($config['repository']['asset']);

        $manager = new AssetManager(
            $assetRepository
        );

        return $manager;
    }
}
