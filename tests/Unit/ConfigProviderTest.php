<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Unit;

use Contenir\Asset\AssetManager;
use Contenir\Asset\AssetManagerFactory;
use Contenir\Asset\ConfigProvider;
use Contenir\Asset\Image\LegacyResizer;
use Contenir\Asset\Image\LegacyResizerFactory;
use Contenir\Asset\View\Helper\AssetSrcSet;
use Contenir\Asset\View\Helper\AssetUrl;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_intersect_key;
use function array_keys;
use function count;

#[CoversClass(ConfigProvider::class)]
#[Group('unit')]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function defaultsToTheConventionalPathsWithNoEntity(): void
    {
        static::assertSame(
            ['entity' => null, 'public_path' => './public', 'storage_directory' => '/asset/user'],
            array_intersect_key((new ConfigProvider())->getAssetDefaults(), [
                'entity'            => 1,
                'public_path'       => 1,
                'storage_directory' => 1,
            ]),
        );
    }

    #[Test]
    public function exposesEachConfigSectionPublicly(): void
    {
        $provider = new ConfigProvider();

        static::assertSame(
            [
                'asset'              => $provider->getAssetDefaults(),
                'dependencies'       => $provider->getDependencies(),
                'view_helpers'       => $provider->getViewHelperConfig(),
                'view_helper_config' => $provider->getViewHelperDefaults(),
            ],
            $provider(),
        );
    }

    #[Test]
    public function registersEachViewHelperUnderBothSpellings(): void
    {
        $helpers = (new ConfigProvider())->getViewHelperConfig();

        static::assertSame(
            [['assetSrcset' => AssetSrcSet::class, 'assetUrl' => AssetUrl::class, 'AssetUrl' => AssetUrl::class], 6],
            [
                array_intersect_key($helpers['aliases'], ['assetUrl' => 1, 'AssetUrl' => 1, 'assetSrcset' => 1]),
                count($helpers['factories']),
            ],
        );
    }

    #[Test]
    public function registersTheAssetManagerAndTheLegacyResizer(): void
    {
        static::assertSame(
            [AssetManager::class => AssetManagerFactory::class, LegacyResizer::class => LegacyResizerFactory::class],
            (new ConfigProvider())->getDependencies()['factories'],
        );
    }

    #[Test]
    public function routesNothing(): void
    {
        static::assertSame(
            ['asset', 'dependencies', 'view_helpers', 'view_helper_config'],
            array_keys((new ConfigProvider())()),
        );
    }
}
