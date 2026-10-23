<?php

declare(strict_types=1);

namespace Contenir\Asset;

/**
 * Container wiring for the asset records: the configuration defaults, the
 * AssetManager and legacy resizer services, and the asset view helpers,
 * in the shape Mezzio and laminas-config-aggregator read. {@see Module}
 * hands the same wiring to laminas-mvc.
 *
 * Nothing here routes a request: the framework adapters
 * (contenir/contenir-asset-laminas-mvc, contenir/contenir-asset-mezzio)
 * register the resize route over {@see Image\LegacyResizer}.
 *
 * @api
 */
final readonly class ConfigProvider
{
    /**
     * The `asset` defaults a site overrides in its own configuration.
     *
     * @return array<string, mixed>
     */
    public function getAssetDefaults(): array
    {
        return [
            'entity'            => null,
            'asset_manager'     => AssetManager::class,
            'public_path'       => './public',
            'storage_directory' => '/asset/user',
            'convert'           => 'convert',
            'logger'            => null,
            'cdn'               => ['host' => null],
            'srcset'            => ['helper' => '/usr/local/bin/convert'],
        ];
    }

    /**
     * @return array{factories: array<class-string, class-string>}
     */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                AssetManager::class        => AssetManagerFactory::class,
                Image\LegacyResizer::class => Image\LegacyResizerFactory::class,
            ],
        ];
    }

    /**
     * @return array{aliases: array<string, class-string>, factories: array<class-string, class-string>}
     */
    public function getViewHelperConfig(): array
    {
        return [
            'aliases'   => [
                'asset'       => View\Helper\Asset::class,
                'Asset'       => View\Helper\Asset::class,
                'assetAspect' => View\Helper\AssetAspect::class,
                'AssetAspect' => View\Helper\AssetAspect::class,
                'assetSize'   => View\Helper\AssetSize::class,
                'AssetSize'   => View\Helper\AssetSize::class,
                'assetSizes'  => View\Helper\AssetSizes::class,
                'AssetSizes'  => View\Helper\AssetSizes::class,
                'assetSrcset' => View\Helper\AssetSrcSet::class,
                'assetSrcSet' => View\Helper\AssetSrcSet::class,
                'AssetSrcset' => View\Helper\AssetSrcSet::class,
                'AssetSrcSet' => View\Helper\AssetSrcSet::class,
                'assetUrl'    => View\Helper\AssetUrl::class,
                'AssetUrl'    => View\Helper\AssetUrl::class,
            ],
            'factories' => [
                View\Helper\Asset::class       => View\Helper\AssetFactory::class,
                View\Helper\AssetAspect::class => View\Helper\AssetAspectFactory::class,
                View\Helper\AssetSize::class   => View\Helper\AssetSizeFactory::class,
                View\Helper\AssetSizes::class  => View\Helper\AssetSizesFactory::class,
                View\Helper\AssetSrcSet::class => View\Helper\AssetSrcSetFactory::class,
                View\Helper\AssetUrl::class    => View\Helper\AssetUrlFactory::class,
            ],
        ];
    }

    /**
     * The named size sets the srcset and sizes helpers read, empty until a
     * site defines them.
     *
     * @return array<string, array{sizes: array<string, mixed>}>
     */
    public function getViewHelperDefaults(): array
    {
        return [
            'assetsrcset' => ['sizes' => []],
            'assetsizes'  => ['sizes' => []],
        ];
    }

    /**
     * @return array{
     *     asset: array<string, mixed>,
     *     dependencies: array{factories: array<class-string, class-string>},
     *     view_helpers: array{aliases: array<string, class-string>, factories: array<class-string, class-string>},
     *     view_helper_config: array<string, array{sizes: array<string, mixed>}>
     * }
     */
    public function __invoke(): array
    {
        return [
            'asset'              => $this->getAssetDefaults(),
            'dependencies'       => $this->getDependencies(),
            'view_helpers'       => $this->getViewHelperConfig(),
            'view_helper_config' => $this->getViewHelperDefaults(),
        ];
    }
}
