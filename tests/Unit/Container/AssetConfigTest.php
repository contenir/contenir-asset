<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Unit\Container;

use Contenir\Asset\Container\AssetConfig;
use Contenir\Asset\Container\Services;
use Contenir\Asset\Container\SizeSets;
use Contenir\Asset\Exception\RuntimeException;
use ContenirTest\Asset\TestAsset\Container\InMemoryContainer;
use ContenirTest\Asset\TestAsset\Entity\Asset;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use stdClass;

#[CoversClass(AssetConfig::class)]
#[CoversClass(SizeSets::class)]
#[CoversClass(Services::class)]
#[CoversClass(RuntimeException::class)]
#[Group('unit')]
final class AssetConfigTest extends TestCase
{
    private static function config(mixed $config): AssetConfig
    {
        return AssetConfig::from(new InMemoryContainer(['config' => $config]));
    }

    #[Test]
    public function parsesSizeSetsDroppingInvalidEntries(): void
    {
        $config = self::config([
            'view' => [
                'sizes' => [
                    'card'  => [480 => '480x', 960 => '960x', 'bad' => [1]],
                    'hero'  => [1200, 1600],
                    7       => [100],
                    'wrong' => 'not an array',
                ],
            ],
        ]);

        static::assertSame(
            ['card' => [480 => '480x', 960 => '960x'], 'hero' => [1200, 1600]],
            $config->sizeSets('view.sizes'),
        );
    }

    #[Test]
    public function readsStringsByDotPath(): void
    {
        $config = self::config(['asset' => ['srcset' => ['helper' => '/bin/convert'], 'empty' => '', 'number' => 5]]);

        static::assertSame(
            ['/bin/convert', null, 'fallback', null, null],
            [
                $config->string('asset.srcset.helper'),
                $config->string('asset.empty'),
                $config->string('asset.missing.deeper', 'fallback'),
                $config->string('asset.number'),
                $config->string('asset.srcset.helper.too.deep'),
            ],
        );
    }

    #[Test]
    public function requiredStringReturnsValue(): void
    {
        static::assertSame(
            Asset::class,
            self::config(['asset' => ['entity' => Asset::class]])->requiredString('asset.entity'),
        );
    }

    #[Test]
    public function requiredStringThrowsWhenMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration "asset.entity" is required');

        self::config([])->requiredString('asset.entity');
    }

    #[Test]
    public function servicesRejectsWrongType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Service "logger" must be an instance of Psr\Log\LoggerInterface');

        Services::get(new InMemoryContainer(['logger' => new stdClass()]), 'logger', LoggerInterface::class);
    }

    #[Test]
    public function servicesReturnsServiceOfExpectedType(): void
    {
        $logger = $this->createStub(LoggerInterface::class);

        static::assertSame($logger, Services::get(
            new InMemoryContainer(['logger' => $logger]),
            'logger',
            LoggerInterface::class,
        ));
    }

    #[Test]
    public function sizeSetsDefaultToEmpty(): void
    {
        static::assertSame([], self::config([])->sizeSets('view.sizes'));
    }

    #[Test]
    public function stringOrFallsBackToDefault(): void
    {
        $config = self::config(['asset' => ['public_path' => '/srv/public']]);

        static::assertSame(
            ['/srv/public', '/asset/user'],
            [
                $config->stringOr('asset.public_path', './public'),
                $config->stringOr('asset.storage_directory', '/asset/user'),
            ],
        );
    }

    #[Test]
    public function toleratesMissingOrMalformedConfig(): void
    {
        static::assertSame(
            [null, null],
            [
                AssetConfig::from(new InMemoryContainer())->string('asset.entity'),
                self::config('not an array')->string('asset.entity'),
            ],
        );
    }
}
