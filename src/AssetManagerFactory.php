<?php

namespace Contenir\Asset;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AssetManagerFactory implements FactoryInterface
{
    public function __invoke(
    	ContainerInterface $container,
    	$requestedName,
    	array $options = null
    ) {
        $assetRepository = $container->get(AssetRepository::class);

        $manager = new AssetManager(
            $assetRepository
        );

        return $manager;
    }
}
