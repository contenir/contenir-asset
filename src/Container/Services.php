<?php

declare(strict_types=1);

namespace Contenir\Asset\Container;

use Contenir\Asset\Exception\RuntimeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Fetches services with a type check, so misconfiguration fails with a
 * clear message rather than a TypeError deep in construction.
 *
 * @internal
 */
final readonly class Services
{
    /**
     * @template S of object
     *
     * @param class-string<S> $type
     *
     * @return S
     *
     * @throws ContainerExceptionInterface
     * @throws RuntimeException
     *
     * @mago-expect analysis:mixed-assignment Container services are untyped; the type is checked here.
     */
    public static function get(ContainerInterface $container, string $name, string $type): object
    {
        $service = $container->get($name);
        if (! $service instanceof $type) {
            throw RuntimeException::invalidService($name, $type);
        }

        return $service;
    }
}
