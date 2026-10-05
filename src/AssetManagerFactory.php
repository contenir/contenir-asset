<?php

declare(strict_types=1);

namespace Contenir\Asset;

use Contenir\Asset\Container\AssetConfig;
use Contenir\Asset\Container\Services;
use Contenir\Asset\Exception\InvalidArgumentException;
use Contenir\Asset\Exception\RuntimeException;
use Contenir\Asset\Image\ImageMagickConverter;
use Contenir\Asset\Model\AssetLinker;
use Contenir\Asset\Model\Entity\BaseAssetEntity;
use Contenir\Asset\Storage\AssetStorage;
use Contenir\Db\Model\EntityManager;
use Contenir\Db\Model\Metadata\MetadataFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function is_a;

/**
 * Builds the AssetManager from "asset.entity" (required), the
 * EntityManager and MetadataFactoryInterface services, and the optional
 * "asset.convert" binary and "asset.logger" service.
 *
 * @api
 */
final readonly class AssetManagerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function __invoke(ContainerInterface $container): AssetManager
    {
        $config = AssetConfig::from($container);
        $entity = $config->requiredString('asset.entity');
        if (! is_a($entity, BaseAssetEntity::class, allow_string: true)) {
            throw InvalidArgumentException::notAnAssetEntity($entity);
        }

        $convert = $config->string('asset.convert');
        $logger  = $config->string('asset.logger');

        return new AssetManager(
            Services::get($container, EntityManager::class, EntityManager::class),
            $entity,
            new AssetStorage(
                $config->stringOr('asset.public_path', './public'),
                $config->stringOr('asset.storage_directory', '/asset/user'),
                null === $convert ? null : new ImageMagickConverter($convert),
            ),
            new AssetLinker(Services::get(
                $container,
                MetadataFactoryInterface::class,
                MetadataFactoryInterface::class,
            )),
            null === $logger ? new NullLogger() : Services::get($container, $logger, LoggerInterface::class),
        );
    }
}
