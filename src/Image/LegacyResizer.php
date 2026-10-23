<?php

declare(strict_types=1);

namespace Contenir\Asset\Image;

use Contenir\Asset\Exception\ExceptionInterface;

use function is_file;

/**
 * The legacy on-demand resizer behind the dot-notation `.<dimensions>`
 * scheme (e.g. `/asset/<folder>/.480x/<file>`) that
 * {@see \Contenir\Asset\View\Helper\AssetSrcSet} emits. A web server serves
 * already generated files directly; the framework adapters' resize route
 * calls this only on a miss, and answers with the file it names.
 *
 * @api
 *
 * @deprecated Superseded by the keyed variants of contenir/contenir-asset-laminas-mvc
 *             and contenir/contenir-asset-mezzio, served (incl. WebP/AVIF) at
 *             `/asset/<folder>/_variant/<name>/<file>`. Kept working for sites
 *             still on the `.<dimensions>` scheme.
 */
final readonly class LegacyResizer
{
    public function __construct(
        private string $publicPath = './public',
        private ?ImageConverterInterface $converter = null,
    ) {}

    /**
     * Whether a converter is configured; without one nothing can be resized.
     */
    public function isConfigured(): bool
    {
        return null !== $this->converter;
    }

    /**
     * The path of the resized file, generated unless it exists already, or
     * null when the dimensions are malformed or the source is not a file
     * inside `<public_path>/asset`.
     *
     * @throws ExceptionInterface When the destination cannot be prepared or the conversion fails.
     */
    public function resize(string $folder, string $dimensions, string $filename): ?string
    {
        if (null === $this->converter) {
            return null;
        }

        $paths = LegacyResizePaths::resolve($this->publicPath, $folder, $dimensions, $filename);
        if (null === $paths) {
            return null;
        }

        if (! is_file($paths->destination)) {
            $paths->prepare();
            $this->converter->convert($paths->source, $paths->destination, ImageOperations::resize($paths->dimensions));
        }

        return $paths->destination;
    }
}
