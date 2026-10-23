<?php

declare(strict_types=1);

namespace Contenir\Asset\Image;

use Contenir\Asset\Container\AssetConfig;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Builds the legacy resizer over `asset.public_path`, converting through
 * the ImageMagick binary at `asset.srcset.helper`; with no binary configured
 * the resizer refuses to resize.
 *
 * @api
 */
final readonly class LegacyResizerFactory
{
    /**
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:deprecated-class Builds the deprecated legacy resizer for sites still using it.
     */
    public function __invoke(ContainerInterface $container): LegacyResizer
    {
        $config = AssetConfig::from($container);
        $helper = $config->string('asset.srcset.helper');

        return new LegacyResizer(
            $config->stringOr('asset.public_path', './public'),
            null === $helper ? null : new ImageMagickConverter($helper),
        );
    }
}
