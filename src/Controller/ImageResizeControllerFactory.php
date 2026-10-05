<?php

declare(strict_types=1);

namespace Contenir\Asset\Controller;

use Contenir\Asset\Container\AssetConfig;
use Contenir\Asset\Image\ImageMagickConverter;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @api
 */
final readonly class ImageResizeControllerFactory
{
    /**
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:deprecated-class Builds the deprecated legacy controller for sites still using it.
     */
    public function __invoke(ContainerInterface $container): ImageResizeController
    {
        $config = AssetConfig::from($container);
        $helper = $config->string('asset.srcset.helper');

        return new ImageResizeController(
            $config->stringOr('asset.public_path', './public'),
            null === $helper ? null : new ImageMagickConverter($helper),
        );
    }
}
