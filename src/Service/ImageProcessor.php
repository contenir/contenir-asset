<?php

declare(strict_types=1);

namespace Contenir\Asset\Service;

use InvalidArgumentException;
use RuntimeException;

use function escapeshellarg;
use function exec;
use function file_exists;
use function implode;
use function preg_match;
use function sprintf;

/**
 * ImageProcessor - Generates responsive images using ImageMagick
 */
class ImageProcessor
{
    public function __construct(
        private string $imageMagickPath,
        private array $quality,
        private array $optimization
    ) {
    }

    /**
     * Generate a resized image
     *
     * @param string $sourcePath Source image path
     * @param string $destPath Destination image path
     * @param string $dimensions Dimensions in format "WxH" or "W"
     * @param array $options Processing options (crop, focal, format)
     * @throws RuntimeException If generation fails
     */
    public function generate(
        string $sourcePath,
        string $destPath,
        string $dimensions,
        array $options = []
    ): void {
        if (! file_exists($sourcePath)) {
            throw new InvalidArgumentException("Source image not found: {$sourcePath}");
        }

        [$width, $height] = $this->parseDimensions($dimensions);

        $cropMode = $options['crop'] ?? 'cover';
        $focalX = $options['focal_x'] ?? $options['focal'][0] ?? 0.5;
        $focalY = $options['focal_y'] ?? $options['focal'][1] ?? 0.5;
        $format = $options['format'] ?? 'jpg';

        $command = $this->buildImageMagickCommand(
            $sourcePath,
            $destPath,
            $width,
            $height,
            $cropMode,
            $focalX,
            $focalY,
            $format
        );

        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException(
                "ImageMagick failed: " . implode("\n", $output)
            );
        }
    }

    /**
     * Parse dimensions string into width and height
     *
     * @param string $dimensions Format: "800x600" or "800"
     * @return array{int, int|null} [width, height]
     */
    private function parseDimensions(string $dimensions): array
    {
        if (preg_match('/^(\d+)x(\d+)$/', $dimensions, $matches)) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        if (preg_match('/^(\d+)$/', $dimensions, $matches)) {
            return [(int) $matches[1], null];
        }

        throw new InvalidArgumentException("Invalid dimensions format: {$dimensions}");
    }

    /**
     * Build ImageMagick command
     */
    private function buildImageMagickCommand(
        string $source,
        string $dest,
        int $width,
        ?int $height,
        string $cropMode,
        float $focalX,
        float $focalY,
        string $format
    ): string {
        $source = escapeshellarg($source);
        $dest = escapeshellarg($dest);
        $quality = $this->quality[$format] ?? 85;

        // Base command
        $cmd = $this->imageMagickPath . ' ' . $source;

        // Auto-orient based on EXIF data
        $cmd .= ' -auto-orient';

        // Colorspace optimization
        if ($this->optimization['colorspace'] ?? false) {
            $cmd .= ' -colorspace ' . escapeshellarg($this->optimization['colorspace']);
        }

        // Handle different crop modes
        if ($height !== null) {
            switch ($cropMode) {
                case 'cover':
                    // Resize to cover area, then crop with gravity
                    $gravity = $this->calculateGravity($focalX, $focalY);
                    $cmd .= sprintf(
                        ' -resize %dx%d^ -gravity %s -extent %dx%d',
                        $width,
                        $height,
                        $gravity,
                        $width,
                        $height
                    );
                    break;

                case 'contain':
                    // Resize to fit within dimensions
                    $cmd .= sprintf(' -resize %dx%d', $width, $height);
                    break;

                case 'fill':
                    // Resize and add borders/background
                    $cmd .= sprintf(
                        ' -resize %dx%d -gravity center -background white -extent %dx%d',
                        $width,
                        $height,
                        $width,
                        $height
                    );
                    break;

                case 'exact':
                    // Force exact dimensions (may distort)
                    $cmd .= sprintf(' -resize %dx%d!', $width, $height);
                    break;

                default:
                    throw new InvalidArgumentException("Unknown crop mode: {$cropMode}");
            }
        } else {
            // Width only
            $cmd .= sprintf(' -resize %d', $width);
        }

        // Strip metadata
        if ($this->optimization['strip_metadata'] ?? true) {
            $cmd .= ' -strip';
        }

        // Interlacing (progressive for JPEG, plane for PNG)
        if ($this->optimization['interlace'] ?? false) {
            $interlace = match($format) {
                'jpg', 'jpeg' => 'Plane',
                'png' => 'PNG',
                default => $this->optimization['interlace']
            };
            $cmd .= ' -interlace ' . escapeshellarg($interlace);
        }

        // Quality
        $cmd .= ' -quality ' . (int) $quality;

        // Sampling factor for better JPEG compression
        if ($format === 'jpg' || $format === 'jpeg') {
            $cmd .= ' -sampling-factor 4:2:0';
        }

        // Output file
        $cmd .= ' ' . $dest;

        return $cmd;
    }

    /**
     * Calculate ImageMagick gravity from focal point coordinates
     *
     * @param float $focalX 0.0 to 1.0 (left to right)
     * @param float $focalY 0.0 to 1.0 (top to bottom)
     * @return string ImageMagick gravity value
     */
    private function calculateGravity(float $focalX, float $focalY): string
    {
        // Map focal point to ImageMagick gravity points
        // This is a simplified 9-point grid
        if ($focalY < 0.33) {
            // Top row
            if ($focalX < 0.33) {
                return 'NorthWest';
            }
            if ($focalX > 0.66) {
                return 'NorthEast';
            }
            return 'North';
        }

        if ($focalY > 0.66) {
            // Bottom row
            if ($focalX < 0.33) {
                return 'SouthWest';
            }
            if ($focalX > 0.66) {
                return 'SouthEast';
            }
            return 'South';
        }

        // Middle row
        if ($focalX < 0.33) {
            return 'West';
        }
        if ($focalX > 0.66) {
            return 'East';
        }
        return 'Center';
    }

    /**
     * Get supported image formats
     *
     * @return array<string>
     */
    public function getSupportedFormats(): array
    {
        return ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif'];
    }
}