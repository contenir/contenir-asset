<?php

declare(strict_types=1);

namespace Contenir\Asset\Container;

use Contenir\Asset\Exception\RuntimeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function array_key_exists;
use function explode;
use function is_array;
use function is_string;

/**
 * Typed reader over the untyped application configuration, addressed by
 * dot paths such as "asset.public_path".
 *
 * @internal
 */
final readonly class AssetConfig
{
    /**
     * @param array<array-key, mixed> $config
     */
    private function __construct(
        private array $config,
    ) {}

    /**
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment Configuration is untyped input; accessors validate it.
     */
    public static function from(ContainerInterface $container): self
    {
        $config = $container->has('config') ? $container->get('config') : [];

        return new self(is_array($config) ? $config : []);
    }

    /**
     * @throws RuntimeException When the value is missing.
     */
    public function requiredString(string $path): string
    {
        return $this->string($path) ?? throw RuntimeException::notConfigured($path);
    }

    /**
     * Named size sets, as used by the srcset and sizes helpers.
     *
     * @return array<string, array<int|string, int|string>>
     */
    public function sizeSets(string $path): array
    {
        return SizeSets::parse($this->value($path));
    }

    /**
     * A non-empty string at a dot path such as "asset.srcset.helper".
     *
     * @mago-expect analysis:mixed-assignment Configuration is untyped input; validated here.
     */
    public function string(string $path, ?string $default = null): ?string
    {
        $value = $this->value($path);

        return is_string($value) && '' !== $value ? $value : $default;
    }

    /**
     * A non-empty string at a dot path, or $default.
     */
    public function stringOr(string $path, string $default): string
    {
        return $this->string($path) ?? $default;
    }

    /**
     * @mago-expect analysis:mixed-assignment Configuration is untyped input; callers validate it.
     */
    private function value(string $path): mixed
    {
        $value = $this->config;
        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
