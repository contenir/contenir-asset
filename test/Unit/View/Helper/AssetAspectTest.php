<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Unit\View\Helper;

use Contenir\Asset\View\Helper\AssetAspect;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function imagecreatetruecolor;
use function imagedestroy;
use function imagepng;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

#[Group('unit')]
final class AssetAspectTest extends TestCase
{
    private string $publicPath;
    private string $imagePath;

    protected function setUp(): void
    {
        $this->publicPath = sys_get_temp_dir() . '/contenir-asset-' . uniqid('', true);
        mkdir($this->publicPath . '/assets', 0777, true);

        $this->imagePath = '/assets/landscape.png';
        $image           = imagecreatetruecolor(800, 450);
        imagepng($image, $this->publicPath . $this->imagePath);
        imagedestroy($image);
    }

    protected function tearDown(): void
    {
        @unlink($this->publicPath . $this->imagePath);
        @rmdir($this->publicPath . '/assets');
        @rmdir($this->publicPath);
    }

    public function testReturnsPaddingPercentageForExistingImage(): void
    {
        $helper = new AssetAspect($this->publicPath);

        self::assertSame('56.25', $helper($this->imagePath));
    }

    public function testReturnsEmptyStringWhenFileIsMissing(): void
    {
        $helper = new AssetAspect($this->publicPath);

        self::assertSame('', $helper('/assets/does-not-exist.png'));
    }

    public function testReturnsFallbackWhenFileIsMissing(): void
    {
        $helper = new AssetAspect($this->publicPath);

        self::assertSame('56.25', $helper('/assets/does-not-exist.png', 56.25));
    }

    public function testReturnsEmptyStringWhenPathIsEmpty(): void
    {
        $helper = new AssetAspect($this->publicPath);

        self::assertSame('', $helper(''));
    }

    public function testReturnsEmptyStringWhenInputIsUnsupported(): void
    {
        $helper = new AssetAspect($this->publicPath);

        self::assertSame('', $helper(42));
    }

    #[DataProvider('inputShapeProvider')]
    public function testAcceptsStringArrayAndObjectInputs(mixed $input): void
    {
        $helper = new AssetAspect($this->publicPath);

        self::assertSame('56.25', $helper($input));
    }

    public static function inputShapeProvider(): array
    {
        $path = '/assets/landscape.png';

        return [
            'string path'               => [$path],
            'array with path key'       => [['path' => $path]],
            'object with path property' => [(object) ['path' => $path]],
        ];
    }
}
