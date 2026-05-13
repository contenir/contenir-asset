<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Unit\View\Helper;

use Contenir\Asset\View\Helper\AssetSize;
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
final class AssetSizeTest extends TestCase
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

    public function testReturnsDimensionsForExistingImage(): void
    {
        $helper = new AssetSize($this->publicPath);

        self::assertSame(['width' => 800, 'height' => 450], $helper($this->imagePath));
    }

    public function testReturnsNullWhenFileIsMissing(): void
    {
        $helper = new AssetSize($this->publicPath);

        self::assertNull($helper('/assets/does-not-exist.png'));
    }

    public function testReturnsNullWhenPathIsEmpty(): void
    {
        $helper = new AssetSize($this->publicPath);

        self::assertNull($helper(''));
    }

    public function testReturnsNullWhenInputIsUnsupported(): void
    {
        $helper = new AssetSize($this->publicPath);

        self::assertNull($helper(42));
    }

    public function testAcceptsObjectWithPathProperty(): void
    {
        $helper = new AssetSize($this->publicPath);

        self::assertSame(
            ['width' => 800, 'height' => 450],
            $helper((object) ['path' => $this->imagePath])
        );
    }
}
