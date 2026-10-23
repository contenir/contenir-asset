<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Unit\View\Helper;

use Contenir\Asset\Container\AssetConfig;
use Contenir\Asset\View\Helper\AssetUrl;
use Contenir\Asset\View\Helper\AssetUrlFactory;
use Contenir\Asset\View\Helper\RequestOrigin;
use ContenirTest\Asset\TestAsset\Container\InMemoryContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssetUrl::class)]
#[CoversClass(AssetUrlFactory::class)]
#[CoversClass(RequestOrigin::class)]
#[CoversClass(AssetConfig::class)]
#[Group('unit')]
final class AssetUrlTest extends TestCase
{
    /**
     * @return array<string, array{array<string, string>, string|null, string}>
     */
    public static function urlProvider(): array
    {
        return [
            'request host'      => [
                ['HTTPS' => 'on', 'HTTP_HOST' => 'www.example.com', 'SERVER_PORT' => '443'],
                null,
                'https://www.example.com/asset/a.jpg',
            ],
            'non-standard port' => [
                ['HTTP_HOST' => 'localhost', 'SERVER_PORT' => '8080'],
                null,
                'http://localhost:8080/asset/a.jpg',
            ],
            'cdn host'          => [
                ['HTTPS' => 'on', 'HTTP_HOST' => 'www.example.com'],
                'cdn.example.com',
                'https://cdn.example.com/asset/a.jpg',
            ],
        ];
    }

    /**
     * @param array<string, string> $server
     */
    #[DataProvider('urlProvider')]
    #[Test]
    public function combinesSchemeHostAndPath(array $server, ?string $cdnHost, string $expected): void
    {
        $helper = (new AssetUrlFactory($server))(new InMemoryContainer([
            'config' => ['asset' => ['cdn' => ['host' => $cdnHost]]],
        ]));

        static::assertSame($expected, $helper('/asset/a.jpg'));
    }

    #[Test]
    public function readsTheServerVariablesWhenGivenNone(): void
    {
        static::assertStringStartsWith('http', (new AssetUrlFactory())(new InMemoryContainer())('/a.jpg'));
    }
}
