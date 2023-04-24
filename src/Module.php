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
        $config = [
            'asset' => [
                'asset_manager' => AssetManager::class,
                'repository'    => [
                    'asset' => Model\Repository\BaseAssetRepository::class,
                ],
                'srcset' => [
                    'sizes' => [
                        'small' => [
                            640  => 320,
                            960  => 480,
                            1440 => 720
                        ],
                        'medium' => [
                            640  => 480,
                            960  => 720,
                            1440 => 1080,
                            2400 => 1800
                        ],
                        'large' => [
                            640  => 640,
                            960  => 960,
                            1440 => 1440,
                            2400 => 2400
                        ]
                    ],
                    'helper' => '/usr/local/bin/convert'
                ]
            ],
            'service_manager' => [
                'factories' => [
                    AssetManager::class                         => AssetManagerFactory::class,
                    Model\Entity\BaseAssetEntity::class         => InvokableFactory::class,
                    Model\Repository\BaseAssetRepository::class => RepositoryFactory::class
                ]
            ],
            'view_helpers' => [
                'aliases' => [
                    'asset'        => View\Helper\Asset::class,
                    'Asset'        => View\Helper\Asset::class,
                    'assetContent' => View\Helper\AssetContent::class,
                    'AssetContent' => View\Helper\AssetContent::class,
                    'assetSrcSet'  => View\Helper\AssetSrcSet::class,
                    'AssetSrcSet'  => View\Helper\AssetSrcSet::class,
                    'assetUrl'     => View\Helper\AssetUrl::class,
                    'AssetUrl'     => View\Helper\AssetUrl::class
                ],
                'factories' => [
                    View\Helper\Asset::class        => View\Helper\AssetFactory::class,
                    View\Helper\AssetContent::class => InvokableFactory::class,
                    View\Helper\AssetSrcSet::class  => View\Helper\AssetSrcSetFactory::class,
                    View\Helper\AssetUrl::class     => View\Helper\AssetUrlFactory::class,
                ],
            ],
        ];

        return $config;
    }
}
