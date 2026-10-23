<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Contenir\Asset\Container\AssetConfig;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Takes scheme and host from the request PHP is serving (the server
 * variables, so any framework will do), with "asset.cdn.host" overriding
 * the host.
 *
 * @api
 */
final readonly class AssetUrlFactory
{
    /**
     * @param array<array-key, mixed>|null $server Server variables to read in place of $_SERVER
     */
    public function __construct(
        private ?array $server = null,
    ) {}

    /**
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:undefined-variable PHP's $_SERVER superglobal, which the analyzer does not know.
     * @mago-expect analysis:mixed-argument The superglobal is untyped; RequestOrigin validates each variable it reads.
     */
    public function __invoke(ContainerInterface $container): AssetUrl
    {
        $origin = RequestOrigin::fromServer($this->server ?? $_SERVER);

        return new AssetUrl(
            $origin->scheme,
            AssetConfig::from($container)->string('asset.cdn.host') ?? $origin->host,
        );
    }
}
