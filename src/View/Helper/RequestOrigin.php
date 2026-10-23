<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use function in_array;
use function is_string;
use function str_contains;
use function strtolower;

/**
 * The scheme and host of the current request, read from the server
 * variables PHP fills, so the asset helpers need no framework's request.
 *
 * @internal
 */
final readonly class RequestOrigin
{
    public function __construct(
        public string $scheme,
        public string $host,
    ) {}

    /**
     * HTTPS when the connection or a forwarding proxy says so, else HTTP;
     * the host as sent, with a non-standard port kept. Off the web (a CLI
     * run, say) there is no request, so it is https://localhost.
     *
     * @param array<array-key, mixed> $server As $_SERVER
     */
    public static function fromServer(array $server): self
    {
        $host = self::string($server, 'HTTP_HOST') ?? self::string($server, 'SERVER_NAME');
        if (null === $host) {
            return new self('https', 'localhost');
        }

        $port = self::string($server, 'SERVER_PORT');
        if (null !== $port && ! str_contains($host, ':') && ! in_array($port, ['80', '443'], strict: true)) {
            $host = "{$host}:{$port}";
        }

        return new self(self::isSecure($server) ? 'https' : 'http', $host);
    }

    /**
     * @param array<array-key, mixed> $server
     */
    private static function isSecure(array $server): bool
    {
        $https     = strtolower(self::string($server, 'HTTPS') ?? '');
        $forwarded = strtolower(self::string($server, 'HTTP_X_FORWARDED_PROTO') ?? '');

        return ('' !== $https && 'off' !== $https) || 'https' === $forwarded;
    }

    /**
     * @param array<array-key, mixed> $server
     *
     * @mago-expect analysis:mixed-assignment Server variables are untyped input; checked here.
     */
    private static function string(array $server, string $name): ?string
    {
        $value = $server[$name] ?? null;

        return is_string($value) && '' !== $value ? $value : null;
    }
}
