<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Contenir\Asset\Container\AssetConfig;
use Laminas\Http\PhpEnvironment\Request;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Takes scheme and host from the MVC "Request" service (or the PHP
 * environment), with "asset.cdn.host" overriding the host.
 *
 * @api
 */
final readonly class AssetUrlFactory
{
    /**
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment The container's Request service is untyped; checked with instanceof.
     */
    public function __invoke(ContainerInterface $container): AssetUrl
    {
        $request = $container->has('Request') ? $container->get('Request') : null;
        $uri     = ($request instanceof Request ? $request : new Request())->getUri();
        $port    = (int) $uri->getPort();
        $host    = $uri->getHost() ?? 'localhost';
        $default = 0 === $port || 80 === $port || 443 === $port ? $host : "{$host}:{$port}";

        return new AssetUrl(
            $uri->getScheme() ?? 'https',
            AssetConfig::from($container)->string('asset.cdn.host') ?? $default,
        );
    }
}
