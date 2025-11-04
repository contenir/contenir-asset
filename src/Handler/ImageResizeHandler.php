<?php

declare(strict_types=1);

namespace Contenir\Asset\Handler;

use Contenir\Asset\Service\ImageCache;
use Contenir\Asset\Service\ImageProcessor;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function finfo_file;
use function finfo_open;
use function file_exists;
use function filesize;
use function fopen;

use const FILEINFO_MIME_TYPE;

/**
 * ImageResizeHandler - PSR-15 handler for on-demand image generation
 */
class ImageResizeHandler implements RequestHandlerInterface
{
    public function __construct(
        private ImageProcessor $processor,
        private ImageCache $cache,
        private string $publicPath,
        private array $allowedDimensions = []
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Extract route parameters
        $dimensions = $request->getAttribute('dimensions');
        $imagePathFull = $request->getAttribute('image_path');

        // Extract format from requested path (e.g., "library/news/sm/file.avif")
        // The source image will have its original extension (e.g., file.jpg)
        $pathInfo = pathinfo($imagePathFull);
        $format = $pathInfo['extension'] ?? 'jpg';
        $imagePathWithoutExt = ($pathInfo['dirname'] !== '.' ? $pathInfo['dirname'] . '/' : '') . $pathInfo['filename'];

        // Find source image - try common extensions
        $imagePath = null;
        foreach (['jpg', 'jpeg', 'png', 'gif', 'webp'] as $ext) {
            $testPath = $this->publicPath . '/' . ltrim($imagePathWithoutExt . '.' . $ext, '/');
            if (file_exists($testPath)) {
                $imagePath = $imagePathWithoutExt . '.' . $ext;
                break;
            }
        }

        if (!$imagePath) {
            $debug = "imagePathFull: $imagePathFull\n";
            $debug .= "imagePathWithoutExt: $imagePathWithoutExt\n";
            $debug .= "Tried extensions: jpg, jpeg, png, gif, webp\n";
            $debug .= "Test path example: " . $this->publicPath . '/' . ltrim($imagePathWithoutExt . '.jpg', '/');
            return $this->errorResponse("Source image not found\n\n$debug", 404);
        }

        // Validate dimensions if whitelist is configured
        if (! empty($this->allowedDimensions) && ! $this->isAllowedDimension($dimensions)) {
            return $this->errorResponse('Invalid dimensions', 400);
        }

        // Get cache path
        $cachePath = $this->cache->getCachePath($imagePath, $dimensions, $format);
        $filesystemPath = $this->cache->getFilesystemPath($cachePath);

        // Return cached image if exists
        if ($this->cache->exists($cachePath)) {
            return $this->serveImage($filesystemPath);
        }

        // Check source image exists
        $sourcePath = $this->publicPath . '/' . ltrim($imagePath, '/');
        if (! file_exists($sourcePath)) {
            return $this->errorResponse('Source image not found', 404);
        }

        // Generate image
        try {
            $this->cache->ensureDirectory($cachePath);

            $this->processor->generate(
                $sourcePath,
                $filesystemPath,
                $dimensions,
                ['format' => $format]
            );

            return $this->serveImage($filesystemPath);
        } catch (\Exception $e) {
            return $this->errorResponse('Image generation failed: ' . $e->getMessage(), 500);
        }
    }

    private function serveImage(string $path): ResponseInterface
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $path);
        finfo_close($finfo);

        $stream = new Stream(fopen($path, 'r'));

        return (new Response())
            ->withHeader('Content-Type', $mimeType)
            ->withHeader('Content-Length', (string) filesize($path))
            ->withHeader('Cache-Control', 'public, max-age=31536000, immutable')
            ->withHeader('X-Generated-By', 'Contenir-Asset')
            ->withBody($stream);
    }

    private function errorResponse(string $message, int $status): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write($message);

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'text/plain');
    }

    private function isAllowedDimension(string $dimensions): bool
    {
        return in_array($dimensions, $this->allowedDimensions, true);
    }
}