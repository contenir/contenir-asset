<?php

declare(strict_types=1);

namespace Contenir\Asset\Controller;

use Contenir\Asset\Exception\ExceptionInterface;
use Contenir\Asset\Image\ImageConverterInterface;
use Contenir\Asset\Image\ImageOperations;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;

use function date;
use function file_get_contents;
use function is_file;
use function is_string;
use function mime_content_type;
use function strlen;
use function urldecode;

/**
 * Legacy on-demand image resizer serving the dot-notation `.<dimensions>`
 * scheme (e.g. `/asset/<folder>/.480x/<file>`) paired with
 * {@see \Contenir\Asset\View\Helper\AssetSrcSet}. A web server serves
 * already generated files directly; this action only runs on a miss.
 *
 * @api
 *
 * @deprecated Superseded by contenir/contenir-asset-laminas-mvc, which serves
 *             on-demand variants (incl. WebP/AVIF) at
 *             `/asset/<folder>/_variant/<dimensions>/<file>.<fmt>`. Kept working
 *             for sites still on the `.<dimensions>` scheme.
 */
final class ImageResizeController extends AbstractActionController
{
    public function __construct(
        private readonly string $publicPath = './public',
        private readonly ?ImageConverterInterface $converter = null,
    ) {}

    private static function file(string $path): Response
    {
        $content  = (string) file_get_contents($path);
        $mimeType = mime_content_type($path);
        $response = self::respond(Response::STATUS_CODE_200, $content);
        $response->getHeaders()
            ->addHeaders([
                'Content-Type'         => false === $mimeType ? 'application/octet-stream' : $mimeType,
                'Content-Length'       => (string) strlen($content),
                'X-Contenir-Generated' => date('r'),
            ]);

        return $response;
    }

    private static function param(mixed $value): string
    {
        return is_string($value) ? urldecode($value) : '';
    }

    private static function respond(int $status, string $content): Response
    {
        $response = new Response();
        $response->setStatusCode($status);
        $response->setContent($content);

        return $response;
    }

    public function resizeAction(): Response
    {
        if (null === $this->converter) {
            return self::respond(Response::STATUS_CODE_501, 'Image resizing is not configured');
        }

        $match = $this->getEvent()->getRouteMatch();
        $paths = LegacyResizePaths::resolve(
            $this->publicPath,
            self::param($match?->getParam('folder')),
            self::param($match?->getParam('dimensions')),
            self::param($match?->getParam('filename')),
        );
        if (null === $paths) {
            return self::respond(Response::STATUS_CODE_404, 'File not found');
        }

        try {
            if (! is_file($paths->destination)) {
                $paths->prepare();
                $this->converter->convert(
                    $paths->source,
                    $paths->destination,
                    ImageOperations::resize($paths->dimensions),
                );
            }
        } catch (ExceptionInterface $e) {
            return self::respond(Response::STATUS_CODE_500, $e->getMessage());
        }

        return self::file($paths->destination);
    }
}
