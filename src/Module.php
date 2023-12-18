<?php

namespace Contenir\Asset;

use Contenir\Db\Model\Repository\Factory\RepositoryFactory;
use Laminas\Router\Http\Regex;
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
                    'helper' => '/usr/local/bin/convert'
                ]
            ],
            'router' => [
                'routes' => [
                    'imageresize' => [
                        'type'    => Regex::class,
                        'options' => [
                            'regex'    => '/asset/(?<folder>[a-zA-Z0-9_\-\/]+).*/\.(?<dimensions>[\d\.]+x[\d\.]*)/(?<filename>.*)',
                            'defaults' => [
                                'controller' => Controller\ImageResizeController::class,
                                'action'     => 'index'
                            ],
                            'spec' => '/asset/%folder%/.%dimensions%/%filename%',
                        ],
                    ],
                ]
            ],
            'controllers' => [
                'factories' => [
                    Controller\ImageResizeController::class => InvokableFactory::class,
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
                    'assetSizes'   => View\Helper\AssetSizes::class,
                    'AssetSizes'   => View\Helper\AssetSizes::class,
                    'assetSrcset'  => View\Helper\AssetSrcSet::class,
                    'assetSrcSet'  => View\Helper\AssetSrcSet::class,
                    'AssetSrcset'  => View\Helper\AssetSrcSet::class,
                    'AssetSrcSet'  => View\Helper\AssetSrcSet::class,
                    'assetUrl'     => View\Helper\AssetUrl::class,
                    'AssetUrl'     => View\Helper\AssetUrl::class
                ],
                'factories' => [
                    View\Helper\Asset::class        => View\Helper\AssetFactory::class,
                    View\Helper\AssetContent::class => InvokableFactory::class,
                    View\Helper\AssetSizes::class   => View\Helper\AssetSizesFactory::class,
                    View\Helper\AssetSrcSet::class  => View\Helper\AssetSrcSetFactory::class,
                    View\Helper\AssetUrl::class     => View\Helper\AssetUrlFactory::class,
                ],
            ],
            'view_helper_config' => [
                'assetsrcset' => [
                    'sizes' => [],
                ],
                'assetsizes' => [
                    'sizes' => [],
                ],
            ]
        ];

        return $config;
    }
}
