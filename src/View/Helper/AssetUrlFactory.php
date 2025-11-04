<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Contenir\Asset\Service\AssetUrlGenerator;
use Psr\Container\ContainerInterface;

class AssetUrlFactory
{
    public function __invoke(ContainerInterface $container): AssetUrl
    {
        $urlGenerator = $container->get(AssetUrlGenerator::class);
        return new AssetUrl($urlGenerator);
    }
}