<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Unit\View\Helper;

use Contenir\Asset\Container\AssetConfig;
use Contenir\Asset\View\Helper\AssetUrl;
use Contenir\Asset\View\Helper\AssetUrlFactory;
use ContenirTest\Asset\TestAsset\Container\InMemoryContainer;
use Laminas\Http\PhpEnvironment\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssetUrl::class)]
#[CoversClass(AssetUrlFactory::class)]
#[CoversClass(AssetConfig::class)]
#[Group('unit')]
final class AssetUrlTest extends TestCase
{
    /**
     * @return array<string, array{string, string|null, string}>
     */
    public static function urlProvider(): array
    {
        return [
            'request host'      => ['https://www.example.com/page', null, 'https://www.example.com/asset/a.jpg'],
            'non-standard port' => ['http://localhost:8080/page', null, 'http://localhost:8080/asset/a.jpg'],
            'cdn host'          => [
                'https://www.example.com/page',
                'cdn.example.com',
                'https://cdn.example.com/asset/a.jpg',
            ],
        ];
    }

    private static function request(string $uri): Request
    {
        $request = new Request();
        $request->setUri($uri);

        return $request;
    }

    #[DataProvider('urlProvider')]
    #[Test]
    public function combinesSchemeHostAndPath(string $requestUri, ?string $cdnHost, string $expected): void
    {
        $helper = (new AssetUrlFactory())(new InMemoryContainer([
            'Request' => self::request($requestUri),
            'config'  => ['asset' => ['cdn' => ['host' => $cdnHost]]],
        ]));

        static::assertSame($expected, $helper('/asset/a.jpg'));
    }

    #[Test]
    public function fallsBackToEnvironmentRequest(): void
    {
        static::assertStringStartsWith('http', (new AssetUrlFactory())(new InMemoryContainer())('/a.jpg'));
    }
}
