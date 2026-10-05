<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Integration\Controller;

use Contenir\Asset\Controller\ImageResizeController;
use Contenir\Asset\Controller\ImageResizeControllerFactory;
use Contenir\Asset\Controller\LegacyResizePaths;
use Contenir\Asset\Exception\RuntimeException;
use Contenir\Asset\Storage\Filesystem;
use ContenirTest\Asset\TestAsset\Container\InMemoryContainer;
use ContenirTest\Asset\TestAsset\Image\FakeImageConverter;
use ContenirTest\Asset\Trait\TempDirectoryTrait;
use Laminas\Http\Response;
use Laminas\Router\RouteMatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function is_file;
use function str_contains;

/**
 * @mago-expect analysis:deprecated-class Tests the deprecated legacy controller.
 */
#[CoversClass(ImageResizeController::class)]
#[CoversClass(ImageResizeControllerFactory::class)]
#[CoversClass(LegacyResizePaths::class)]
#[CoversClass(Filesystem::class)]
#[CoversClass(RuntimeException::class)]
#[Group('integration')]
final class ImageResizeControllerTest extends TestCase
{
    use TempDirectoryTrait;

    private FakeImageConverter $converter;

    /**
     * @return array<string, array{array<string, string>}>
     */
    public static function rejectedRequestProvider(): array
    {
        return [
            'missing file'         => [['folder' => 'gallery', 'dimensions' => '20x', 'filename' => 'none.png']],
            'path traversal'       => [['folder' => '..', 'dimensions' => '20x', 'filename' => 'secret.txt']],
            'encoded traversal'    => [['folder' => '%2E%2E', 'dimensions' => '20x', 'filename' => 'secret.txt']],
            'malformed dimensions' => [['folder' => 'gallery', 'dimensions' => '20;rm', 'filename' => 'photo.png']],
            'no route parameters'  => [[]],
        ];
    }

    /**
     * @mago-expect analysis:deprecated-class Tests the deprecated legacy controller factory.
     */
    #[Test]
    public function factoryConfiguresConverterFromSrcsetHelper(): void
    {
        $factory = new ImageResizeControllerFactory();

        static::assertInstanceOf(ImageResizeController::class, $factory(new InMemoryContainer([
            'config' => ['asset' => ['public_path' => $this->publicPath, 'srcset' => ['helper' => '/usr/bin/convert']]],
        ])));
    }

    /**
     * @mago-expect analysis:deprecated-class Tests the deprecated legacy controller factory.
     */
    #[Test]
    public function factoryLeavesResizingUnconfiguredWithoutHelper(): void
    {
        $controller = (new ImageResizeControllerFactory())(new InMemoryContainer([
            'config' => ['asset' => ['public_path' => $this->publicPath]],
        ]));

        static::assertSame(501, $controller->resizeAction()->getStatusCode());
    }

    #[Test]
    public function generatesAndServesTheResizedImage(): void
    {
        $response = $this->dispatch(['folder' => 'gallery', 'dimensions' => '20x', 'filename' => 'photo.png']);

        static::assertSame(
            [200, 'image/png', true, '20x'],
            [
                $response->getStatusCode(),
                $response->getHeaders()->get('Content-Type')?->getFieldValue(),
                is_file("{$this->publicPath}/asset/gallery/.20x/photo.png"),
                $this->converter->calls[0]['operations'][4] ?? null,
            ],
        );
    }

    #[Test]
    public function reportsDestinationThatCannotBeCreated(): void
    {
        $this->createFile('/asset/gallery/.20x', 'a file where the directory should be');

        $response = $this->dispatch(['folder' => 'gallery', 'dimensions' => '20x', 'filename' => 'photo.png']);

        static::assertSame([500, true], [
            $response->getStatusCode(),
            str_contains((string) $response->getContent(), 'Cannot create'),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    #[DataProvider('rejectedRequestProvider')]
    #[Test]
    public function respondsNotFoundForUnsafeOrMissingFiles(array $params): void
    {
        static::assertSame([404, []], [$this->dispatch($params)->getStatusCode(), $this->converter->calls]);
    }

    /**
     * @mago-expect analysis:deprecated-class Tests the deprecated legacy controller.
     */
    #[Test]
    public function respondsNotFoundWithoutRouteMatch(): void
    {
        static::assertSame(
            404,
            (new ImageResizeController($this->publicPath, $this->converter))->resizeAction()->getStatusCode(),
        );
    }

    #[Test]
    public function respondsNotImplementedWithoutConverter(): void
    {
        $response = $this->dispatch([
            'folder'     => 'gallery',
            'dimensions' => '20x',
            'filename'   => 'photo.png',
        ], converter: null);

        static::assertSame(501, $response->getStatusCode());
    }

    #[Test]
    public function servesAnExistingResizeWithoutConverting(): void
    {
        $this->createFile('/asset/gallery/.20x/photo.png', 'cached');

        $response = $this->dispatch(['folder' => 'gallery', 'dimensions' => '20x', 'filename' => 'photo.png']);

        static::assertSame(['cached', []], [$response->getContent(), $this->converter->calls]);
    }

    protected function setUp(): void
    {
        $this->setUpTempDirectory();
        $this->converter = new FakeImageConverter();
        $this->createImage('/asset/gallery/photo.png', 40, 20);
        $this->createFile('/secret.txt', 'top secret');
    }

    protected function tearDown(): void
    {
        $this->tearDownTempDirectory();
    }

    /**
     * @param array<string, string> $params
     *
     * @mago-expect analysis:deprecated-class Tests the deprecated legacy controller.
     */
    private function dispatch(array $params, ?FakeImageConverter $converter = new FakeImageConverter()): Response
    {
        $this->converter = $converter ?? $this->converter;
        $controller      = new ImageResizeController($this->publicPath, $converter);
        $controller->getEvent()->setRouteMatch(new RouteMatch($params));

        return $controller->resizeAction();
    }
}
