<?php

namespace Contenir\Asset;

use Contenir\Db\Model\Repository\Factory\RepositoryFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;

class Module
{
    /**
     * Retrieve default laminas-paginator config for laminas-mvc context.
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'service_manager' => [
            	'factories' => [
	                AssetEntity::class => InvokableFactory::class,
	                AssetManager::class => AssetManagerFactory::class,
    	            AssetRepository::class => RepositoryFactory::class
            	]
            ]
        ];
    }
}
