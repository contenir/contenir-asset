<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Integration\View\Helper;

use Contenir\Asset\View\Helper\AssetAspect;
use Contenir\Asset\View\Helper\AssetAspectFactory;
use Contenir\Asset\View\Helper\AssetSize;
use Contenir\Asset\View\Helper\AssetSizeFactory;
use Contenir\Asset\View\Helper\AssetSrcSet;
use Contenir\Asset\View\Helper\AssetSrcSetFactory;
use Contenir\Asset\View\Helper\ImageInfo;
use ContenirTest\Asset\TestAsset\Container\InMemoryContainer;
use ContenirTest\Asset\TestAsset\Entity\Asset;
use ContenirTest\Asset\Trait\TempDirectoryTrait;
use Laminas\Escaper\Escaper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssetAspect::class)]
#[CoversClass(AssetAspectFactory::class)]
#[CoversClass(AssetSize::class)]
#[CoversClass(AssetSizeFactory::class)]
#[CoversClass(AssetSrcSet::class)]
#[CoversClass(AssetSrcSetFactory::class)]
#[CoversClass(ImageInfo::class)]
#[Group('integration')]
final class ImageHelpersTest extends TestCase
{
    use TempDirectoryTrait;

    #[Test]
    public function aspectIsHeightOverWidthAsPercentage(): void
    {
        $aspect       = new AssetAspect($this->publicPath);
        $entity       = new Asset();
        $entity->path = '/asset/landscape.png';

        static::assertSame(
            ['56.25', '56.25', '', '', '50', ''],
            [
                $aspect('/asset/landscape.png'),
                $aspect($entity),
                $aspect('/asset/missing.png'),
                $aspect('/asset/not-an-image.png'),
                $aspect('/asset/missing.png', fallback: 50.0),
                $aspect(''),
            ],
        );
    }

    #[Test]
    public function factoriesReadPublicPathWithLegacyFallback(): void
    {
        $current = new InMemoryContainer(['config' => ['asset' => ['public_path' => $this->publicPath]]]);
        $legacy  = new InMemoryContainer(['config' => ['asset' => ['cache' => ['public_path' => $this->publicPath]]]]);

        static::assertSame(
            ['56.25', ['width' => 800, 'height' => 450]],
            [
                (new AssetAspectFactory())($legacy)('/asset/landscape.png'),
                (new AssetSizeFactory())($current)('/asset/landscape.png'),
            ],
        );
    }

    #[Test]
    public function sizeReturnsDimensionsOrNull(): void
    {
        $size = new AssetSize($this->publicPath);

        static::assertSame(
            [['width' => 800, 'height' => 450], null, null],
            [$size(['path' => '/asset/landscape.png']), $size('/asset/missing.png'), $size('/asset/not-an-image.png')],
        );
    }

    /**
     * @mago-expect analysis:deprecated-class Tests the deprecated legacy srcset helper factory.
     */
    #[Test]
    public function srcsetFactoryReadsRootPathAndSizeSets(): void
    {
        $this->createImage('/asset/x.png', 10, 10);
        $helper = (new AssetSrcSetFactory())(new InMemoryContainer([
            'config' => [
                'view_helper_config' => [
                    'assetsrcset' => ['root_path' => $this->publicPath, 'sizes' => ['s' => [100 => '100']]],
                ],
            ],
        ]));

        static::assertSame(
            (new Escaper())->escapeHtmlAttr('/asset/.100x/x.png 100w'),
            $helper('/asset/x.png', ['sizes' => 's']),
        );
    }

    /**
     * @mago-expect analysis:deprecated-class Tests the deprecated legacy srcset helper.
     */
    #[Test]
    public function srcsetHandlesFilesWithoutExtension(): void
    {
        $this->createImage('/asset/raw', 10, 10);

        static::assertSame(
            (new Escaper())->escapeHtmlAttr('/asset/.100x/raw 100w'),
            (new AssetSrcSet($this->publicPath))('/asset/raw', [100 => '100']),
        );
    }

    /**
     * @mago-expect analysis:deprecated-class Tests the deprecated legacy srcset helper.
     */
    #[Test]
    public function srcsetListsDotDimensionUrls(): void
    {
        $this->createImage('/asset/my photos/big shot.png', 10, 10);
        $srcset = new AssetSrcSet($this->publicPath, ['card' => [480 => '480', 960 => '960x540']]);

        static::assertSame(
            (new Escaper())->escapeHtmlAttr(
                '/asset/my%20photos/.480x/big%20shot.png 480w, /asset/my%20photos/.960x540/big%20shot.png 960w',
            ),
            $srcset('/asset/my photos/big shot.png', options: 'card'),
        );
    }

    /**
     * @mago-expect analysis:deprecated-class Tests the deprecated legacy srcset helper.
     */
    #[Test]
    public function srcsetReturnsThePathForMissingFiles(): void
    {
        $srcset = new AssetSrcSet($this->publicPath);

        static::assertSame(
            [(new Escaper())->escapeHtmlAttr('/asset/missing.png'), ''],
            [$srcset('/asset/missing.png', options: 'card'), $srcset(null)],
        );
    }

    protected function setUp(): void
    {
        $this->setUpTempDirectory();
        $this->createImage('/asset/landscape.png', 800, 450);
        $this->createFile('/asset/not-an-image.png', 'text');
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
    }
}
