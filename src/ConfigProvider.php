<?php

declare(strict_types=1);

namespace Contenir\Asset;

use Contenir\Asset\Handler\ImageResizeHandler;
use Contenir\Asset\Handler\ImageResizeHandlerFactory;
use Contenir\Asset\Service\AssetUrlGenerator;
use Contenir\Asset\Service\AssetUrlGeneratorFactory;
use Contenir\Asset\Service\ImageCache;
use Contenir\Asset\Service\ImageCacheFactory;
use Contenir\Asset\Service\ImageProcessor;
use Contenir\Asset\Service\ImageProcessorFactory;
use Contenir\Asset\View\Helper\AssetSizes;
use Contenir\Asset\View\Helper\AssetSizesFactory;
use Contenir\Asset\View\Helper\AssetSrcSet;
use Contenir\Asset\View\Helper\AssetSrcSetFactory;
use Contenir\Asset\View\Helper\AssetUrl;
use Contenir\Asset\View\Helper\AssetUrlFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'view_helpers' => $this->getViewHelpers(),
            'contenir_asset' => $this->getAssetConfig(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'factories' => [
                ImageProcessor::class => ImageProcessorFactory::class,
                ImageCache::class => ImageCacheFactory::class,
                ImageResizeHandler::class => ImageResizeHandlerFactory::class,
                AssetUrlGenerator::class => AssetUrlGeneratorFactory::class,
            ],
        ];
    }

    public function getViewHelpers(): array
    {
        return [
            'aliases' => [
                'assetSrcSet' => AssetSrcSet::class,
                'AssetSrcSet' => AssetSrcSet::class,
                'assetSizes' => AssetSizes::class,
                'AssetSizes' => AssetSizes::class,
                'assetUrl' => AssetUrl::class,
                'AssetUrl' => AssetUrl::class,
            ],
            'factories' => [
                AssetSrcSet::class => AssetSrcSetFactory::class,
                AssetSizes::class => AssetSizesFactory::class,
                AssetUrl::class => AssetUrlFactory::class,
            ],
        ];
    }

    public function getAssetConfig(): array
    {
        return [
            // Image processor configuration
            'processor' => [
                'imagemagick_path' => '/usr/local/bin/convert',
                'quality' => [
                    'jpg' => 85,
                    'jpeg' => 85,
                    'webp' => 85,
                    'avif' => 75,
                    'png' => 90,
                ],
                'optimization' => [
                    'strip_metadata' => true,
                    'progressive' => true,
                    'colorspace' => 'sRGB',
                    'interlace' => 'Plane',
                ],
            ],

            // Cache configuration
            'cache' => [
                'path' => '/cache/images',
                'public_path' => './public',
            ],

            // Default formats (in preference order)
            'default_formats' => ['avif', 'webp', 'jpg'],

            // Image presets
            'presets' => [
                'hero' => [
                    'sizes' => [
                        '1920x1080' => '1920w',
                        '1280x720' => '1280w',
                        '960x540' => '960w',
                        '640x360' => '640w',
                    ],
                    'crop' => 'cover',
                    'formats' => ['avif', 'webp', 'jpg'],
                ],
                'thumbnail' => [
                    'sizes' => [
                        '400x400' => '2x',
                        '200x200' => '1x',
                    ],
                    'crop' => 'cover',
                ],
                'portrait' => [
                    'sizes' => [
                        '800x1200' => '800w',
                        '600x900' => '600w',
                        '400x600' => '400w',
                    ],
                    'crop' => 'cover',
                ],
                'responsive' => [
                    'sizes' => [
                        '1600x900' => '1600w',
                        '1200x675' => '1200w',
                        '800x450' => '800w',
                        '400x225' => '400w',
                    ],
                    'crop' => 'cover',
                ],
            ],

            // Allowed dimensions for security (empty = all presets allowed)
            'allowed_dimensions' => [],
        ];
    }
}