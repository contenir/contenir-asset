<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Unit\View\Helper;

use Contenir\Asset\View\Helper\RequestOrigin;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequestOrigin::class)]
#[Group('unit')]
final class RequestOriginTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function serverProvider(): array
    {
        return [
            'plain http'               => [['HTTP_HOST' => 'www.example.com'], 'http', 'www.example.com'],
            'https on'                 => [
                ['HTTPS' => 'on', 'HTTP_HOST' => 'www.example.com'],
                'https',
                'www.example.com',
            ],
            'https off'                => [
                ['HTTPS' => 'off', 'HTTP_HOST' => 'www.example.com'],
                'http',
                'www.example.com',
            ],
            'behind a proxy'           => [
                ['HTTP_X_FORWARDED_PROTO' => 'https', 'HTTP_HOST' => 'www.example.com'],
                'https',
                'www.example.com',
            ],
            'standard port left out'   => [
                ['HTTP_HOST' => 'www.example.com', 'SERVER_PORT' => '80'],
                'http',
                'www.example.com',
            ],
            'other port kept'          => [
                ['HTTP_HOST' => 'localhost', 'SERVER_PORT' => '8080'],
                'http',
                'localhost:8080',
            ],
            'port already in the host' => [
                ['HTTP_HOST' => 'localhost:8080', 'SERVER_PORT' => '8080'],
                'http',
                'localhost:8080',
            ],
            'server name fallback'     => [['SERVER_NAME' => 'example.test'], 'http', 'example.test'],
            'non-string host ignored'  => [
                ['HTTP_HOST' => ['x'], 'SERVER_NAME' => 'example.test'],
                'http',
                'example.test',
            ],
            'no request at all'        => [[], 'https', 'localhost'],
        ];
    }

    /**
     * @param array<string, mixed> $server
     */
    #[DataProvider('serverProvider')]
    #[Test]
    public function readsSchemeAndHostFromTheServerVariables(array $server, string $scheme, string $host): void
    {
        $origin = RequestOrigin::fromServer($server);

        static::assertSame([$scheme, $host], [$origin->scheme, $origin->host]);
    }
}
