<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Unit\View\Helper;

use Contenir\Asset\View\Helper\AssetSizes;
use Contenir\Asset\View\Helper\SizeOptions;
use Laminas\Escaper\Escaper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function str_contains;

#[CoversClass(AssetSizes::class)]
#[CoversClass(SizeOptions::class)]
#[Group('unit')]
final class AssetSizesTest extends TestCase
{
    /**
     * @return array<string, array{string|array<array-key, mixed>|null, string}>
     */
    public static function optionsProvider(): array
    {
        return [
            'named set'         => ['card', '(max-width: 768px) 100px, 600px'],
            'plain array'       => [[768 => 100, 900], '(max-width: 768px) 100px, 900px'],
            'sizes key array'   => [['sizes' => [1200 => 600, 'default' => 900]], '(max-width: 1200px) 600px, 900px'],
            'sizes key name'    => [['sizes' => 'card'], '(max-width: 768px) 100px, 600px'],
            'media query key'   => [
                ['(orientation: portrait)' => 300, 0 => 800],
                '(orientation: portrait) 300px, 800px',
            ],
            'unknown set'       => ['missing', ''],
            'no options'        => [null, ''],
            'invalid sizes key' => [['sizes' => 5], ''],
        ];
    }

    /**
     * @param string|array<array-key, mixed>|null $options
     */
    #[DataProvider('optionsProvider')]
    #[Test]
    public function buildsSizesAttribute(string|array|null $options, string $expected): void
    {
        $helper = new AssetSizes(['card' => [768 => 100, 'default' => 600]]);

        static::assertSame((new Escaper())->escapeHtmlAttr($expected), $helper($options));
    }

    #[Test]
    public function escapesForAnAttribute(): void
    {
        $sizes = (new AssetSizes())(['"><script>' => 1, 0 => 2]);

        static::assertSame([false, true], [str_contains($sizes, '<'), str_contains($sizes, '&lt;script&gt;')]);
    }
}
