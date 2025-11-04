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

            // Image presets aligned with common device widths and PageSpeed Insights
            // Core breakpoints: 360px (mobile), 768px (tablet), 1024px (small desktop),
            // 1366px (desktop), 1920px (FHD). Each with 1x and 2x variants.
            'presets' => [
                // Full-width hero images (16:9 aspect ratio)
                'hero' => [
                    'dimensions' => [
                        '360x203' => '360w',    // Mobile @ 1x (PageSpeed default)
                        '720x405' => '720w',    // Mobile @ 2x
                        '768x432' => '768w',    // Tablet portrait @ 1x
                        '1024x576' => '1024w',  // Tablet landscape / small desktop @ 1x
                        '1366x768' => '1366w',  // Desktop @ 1x (most common laptop)
                        '1536x864' => '1536w',  // Tablet @ 2x
                        '1920x1080' => '1920w', // FHD @ 1x / Desktop @ ~1.4x
                        '2048x1152' => '2048w', // Small desktop @ 2x
                        '2732x1536' => '2732w', // Desktop @ 2x
                        '3840x2160' => '3840w', // 4K @ 1x / FHD @ 2x
                    ],
                    'crop' => 'cover',
                ],
                // Square thumbnails
                'thumbnail' => [
                    'dimensions' => [
                        '200x200' => '200w',   // Small @ 1x
                        '400x400' => '400w',   // Small @ 2x / Medium @ 1x
                        '600x600' => '600w',   // Medium @ 2x / Large @ 1x
                        '800x800' => '800w',   // Large @ 2x
                    ],
                    'crop' => 'cover',
                ],
                // Portrait images (2:3 aspect ratio)
                'portrait' => [
                    'dimensions' => [
                        '360x540' => '360w',   // Mobile @ 1x
                        '720x1080' => '720w',  // Mobile @ 2x
                        '768x1152' => '768w',  // Tablet @ 1x
                        '1024x1536' => '1024w', // Tablet @ 2x / Desktop @ 1x
                        '1366x2049' => '1366w', // Desktop @ 1x
                        '1536x2304' => '1536w', // Tablet @ 3x / Desktop @ ~1.5x
                    ],
                    'crop' => 'cover',
                ],
                // Responsive content images (16:9 aspect ratio)
                'responsive' => [
                    'dimensions' => [
                        '360x203' => '360w',   // Mobile @ 1x
                        '720x405' => '720w',   // Mobile @ 2x
                        '768x432' => '768w',   // Tablet @ 1x
                        '1024x576' => '1024w', // Desktop @ 1x
                        '1366x768' => '1366w', // Large desktop @ 1x
                        '1536x864' => '1536w', // Tablet @ 2x
                        '1920x1080' => '1920w', // FHD @ 1x
                    ],
                    'crop' => 'cover',
                ],
            ],

            // Allowed dimensions for security (empty = all presets allowed)
            'allowed_dimensions' => [],
        ];
    }
}