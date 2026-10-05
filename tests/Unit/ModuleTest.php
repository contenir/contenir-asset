<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Unit;

use Contenir\Asset\AssetManager;
use Contenir\Asset\AssetManagerFactory;
use Contenir\Asset\Controller\ImageResizeController;
use Contenir\Asset\Controller\ImageResizeControllerFactory;
use Contenir\Asset\Module;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_intersect_key;

#[CoversClass(Module::class)]
#[Group('unit')]
final class ModuleTest extends TestCase
{
    #[Test]
    public function registersServicesControllerAndDefaults(): void
    {
        $config = (new Module())->getConfig();

        static::assertSame(
            [
                AssetManagerFactory::class,
                ImageResizeControllerFactory::class,
                ['entity' => null, 'public_path' => './public', 'storage_directory' => '/asset/user'],
            ],
            [
                $config['service_manager']['factories'][AssetManager::class] ?? null,
                $config['controllers']['factories'][ImageResizeController::class] ?? null,
                array_intersect_key($config['asset'] ?? [], [
                    'entity'            => 1,
                    'public_path'       => 1,
                    'storage_directory' => 1,
                ]),
            ],
        );
    }
}
