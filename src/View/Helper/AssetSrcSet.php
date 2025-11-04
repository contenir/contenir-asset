<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Contenir\Asset\Service\AssetUrlGenerator;
use InvalidArgumentException;
use Laminas\View\Helper\AbstractHelper;

use function array_diff;
use function array_merge;
use function implode;
use function is_array;
use function is_object;
use function is_string;
use function reset;
use function uasort;

/**
 * AssetSrcSet - Generates responsive <picture> elements with modern image formats
 *
 * Usage examples:
 *
 * Basic:
 * <?= $this->assetSrcSet($image, 'responsive') ?>
 *
 * Advanced with breakpoints:
 * <?= $this->assetSrcSet($image, [
 *     'preset' => 'hero',
 *     'formats' => ['avif', 'webp', 'jpg'],
 *     'crop' => 'cover',
 *     'focal' => [0.5, 0.3],
 *     'breakpoints' => [
 *         'mobile' => ['width' => 768, 'sizes' => ['400x600' => '400w'], 'crop' => 'portrait'],
 *         'desktop' => ['width' => 1024, 'sizes' => ['1600x900' => '1600w']],
 *     ],
 *     'img' => ['class' => 'hero-image', 'alt' => 'Hero', 'loading' => 'eager', 'fetchpriority' => 'high'],
 *     'picture' => ['class' => 'hero-picture'],
 *     'sizes' => '(max-width: 768px) 100vw, 50vw',
 * ]) ?>
 */
class AssetSrcSet extends AbstractHelper
{
    private array $presets = [];
    private array $defaultFormats = ['avif', 'webp', 'jpg'];
    private array $defaultImgAttrs = [
        'loading' => 'lazy',
        'decoding' => 'async',
    ];
    private array $lazyLoadConfig = [
        'enabled' => true,
        'picture_attr' => 'data-lazysrc',
        'source_srcset_attr' => 'data-lazysrc-srcset',
        'source_sizes_attr' => 'data-lazysrc-sizes',
        'img_src_attr' => 'data-lazysrc-src',
        'img_srcset_attr' => 'data-lazysrc-srcset',
        'img_sizes_attr' => 'data-lazysrc-sizes',
    ];

    public function __construct(
        private AssetUrlGenerator $urlGenerator,
        array $config = []
    ) {
        $this->presets = $config['presets'] ?? [];
        $defaultFormats = $config['default_formats'] ?? $this->defaultFormats;
        // Remove duplicates that can occur when config is merged from multiple sources
        $this->defaultFormats = array_values(array_unique($defaultFormats));
        $this->lazyLoadConfig = array_merge($this->lazyLoadConfig, $config['lazy_load'] ?? []);
    }

    /**
     * Main invocation
     *
     * @param string|array|object $image Image path, array with metadata, or asset object
     * @param string|array $options Preset name or configuration array
     */
    public function __invoke(mixed $image, string|array $options = []): string
    {
        // Normalize input
        $imagePath = $this->extractImagePath($image);
        $metadata = $this->extractMetadata($image);
        $config = $this->normalizeConfig($options);

        // Merge metadata focal points if available
        if (! isset($config['focal']) && isset($metadata['focal_x'], $metadata['focal_y'])) {
            $config['focal'] = [$metadata['focal_x'], $metadata['focal_y']];
        }

        // Generate picture element
        return $this->buildPictureElement($imagePath, $metadata, $config);
    }

    /**
     * Set presets configuration
     */
    public function setPresets(array $presets): self
    {
        $this->presets = $presets;
        return $this;
    }

    /**
     * Set default formats
     */
    public function setDefaultFormats(array $formats): self
    {
        $this->defaultFormats = $formats;
        return $this;
    }

    private function normalizeConfig(string|array $options): array
    {
        // String preset reference
        if (is_string($options)) {
            if (! isset($this->presets[$options])) {
                throw new InvalidArgumentException("Unknown preset: {$options}");
            }

            return $this->presets[$options];
        }

        // Array config - apply preset if specified
        if (isset($options['preset'])) {
            $preset = $this->presets[$options['preset']] ?? [];
            $options = array_merge($preset, $options);
            unset($options['preset']);
        }

        return $options;
    }

    private function buildPictureElement(string $imagePath, array $metadata, array $config): string
    {
        $formats = $config['formats'] ?? $this->defaultFormats;
        // Remove duplicate formats (can occur when config is merged from multiple sources)
        $formats = array_values(array_unique($formats));
        $pictureAttrs = $config['picture'] ?? [];
        $imgAttrs = array_merge($this->defaultImgAttrs, $config['img'] ?? []);

        // Determine if lazy loading is enabled for this element
        $useLazyLoad = $this->shouldUseLazyLoad($imgAttrs);

        // Add lazy load attribute to picture element if enabled
        if ($useLazyLoad && $this->lazyLoadConfig['enabled']) {
            $pictureAttrs[$this->lazyLoadConfig['picture_attr']] = true;
        }

        $html = '<picture' . $this->renderAttributes($pictureAttrs) . '>';

        // Breakpoints (art direction - different crops/sizes for different viewports)
        if (isset($config['breakpoints'])) {
            $html .= $this->buildBreakpointSources($imagePath, $metadata, $config, $useLazyLoad);
        } else {
            // Standard responsive images with format variants
            $html .= $this->buildFormatSources($imagePath, $metadata, $config, $formats, $useLazyLoad);
        }

        // Fallback img element
        $html .= $this->buildImgElement($imagePath, $metadata, $config, $imgAttrs, $useLazyLoad);

        $html .= '</picture>';

        return $html;
    }

    private function buildBreakpointSources(
        string $imagePath,
        array $metadata,
        array $config,
        bool $useLazyLoad
    ): string {
        $html = '';
        $breakpoints = $config['breakpoints'];

        // Sort by breakpoint width descending (largest first)
        uasort($breakpoints, fn ($a, $b) => ($b['width'] ?? 0) <=> ($a['width'] ?? 0));

        foreach ($breakpoints as $breakpoint => $breakpointConfig) {
            $mediaQuery = isset($breakpointConfig['width'])
                ? "(min-width: {$breakpointConfig['width']}px)"
                : null;

            // Merge breakpoint config with global config
            $mergedConfig = array_merge($config, $breakpointConfig);

            // Generate sources for each format at this breakpoint
            foreach ($config['formats'] ?? $this->defaultFormats as $format) {
                $srcset = $this->buildSrcSet(
                    $imagePath,
                    $metadata,
                    $breakpointConfig['sizes'] ?? $config['sizes'] ?? [],
                    $mergedConfig,
                    $format
                );

                if ($srcset) {
                    $attrs = [
                        'type' => $this->getMimeType($format),
                        'srcset' => $srcset,
                    ];

                    if ($mediaQuery) {
                        $attrs['media'] = $mediaQuery;
                    }

                    if (isset($mergedConfig['sizes'])) {
                        $attrs['sizes'] = $mergedConfig['sizes'];
                    }

                    $html .= '<source' . $this->renderAttributes($attrs) . '>';
                }
            }
        }

        return $html;
    }

    private function buildFormatSources(
        string $imagePath,
        array $metadata,
        array $config,
        array $formats,
        bool $useLazyLoad
    ): string {
        $html = '';

        // Remove jpg from format variants (it will be in img fallback)
        $formats = array_diff($formats, ['jpg', 'jpeg']);

        // 'dimensions' = array of size definitions ['800x800' => '2x']
        // 'sizes' = HTML sizes attribute string '(max-width: 768px) 50vw'
        $dimensions = $config['dimensions'] ?? [];

        foreach ($formats as $format) {
            $srcset = $this->buildSrcSet(
                $imagePath,
                $metadata,
                $dimensions,
                $config,
                $format
            );

            if ($srcset) {
                $attrs = [
                    'type' => $this->getMimeType($format),
                ];

                // Use lazy load attributes if enabled
                if ($useLazyLoad && $this->lazyLoadConfig['enabled']) {
                    $attrs[$this->lazyLoadConfig['source_srcset_attr']] = $srcset;
                    if (isset($config['sizes'])) {
                        $attrs[$this->lazyLoadConfig['source_sizes_attr']] = $config['sizes'];
                    }
                } else {
                    $attrs['srcset'] = $srcset;
                    if (isset($config['sizes'])) {
                        $attrs['sizes'] = $config['sizes'];
                    }
                }

                $html .= '<source' . $this->renderAttributes($attrs) . '>';
            }
        }

        return $html;
    }

    private function buildImgElement(
        string $imagePath,
        array $metadata,
        array $config,
        array $imgAttrs,
        bool $useLazyLoad
    ): string {
        // 'dimensions' = array of size definitions ['800x800' => '2x']
        $dimensions = $config['dimensions'] ?? [];

        // Get smallest dimension for fallback (sort by area)
        $defaultSize = '1200x800';
        if (!empty($dimensions)) {
            $sortedDimensions = $dimensions;
            uksort($sortedDimensions, function ($a, $b) {
                [$aWidth, $aHeight] = explode('x', $a);
                [$bWidth, $bHeight] = explode('x', $b);
                $aArea = (int)$aWidth * (int)$aHeight;
                $bArea = (int)$bWidth * (int)$bHeight;
                return $aArea <=> $bArea;
            });
            $defaultSize = array_key_first($sortedDimensions);
        }

        $src = $this->urlGenerator->generate($imagePath, [
            'size' => $defaultSize,
            'crop' => $config['crop'] ?? 'cover',
            'focal' => $config['focal'] ?? [0.5, 0.5],
            'format' => 'jpg',
        ]);

        $attrs = $imgAttrs;

        // Use lazy load attributes if enabled
        if ($useLazyLoad && $this->lazyLoadConfig['enabled']) {
            $attrs[$this->lazyLoadConfig['img_src_attr']] = $src;
        } else {
            $attrs['src'] = $src;
        }

        return '<img' . $this->renderAttributes($attrs) . '>';
    }

    private function buildSrcSet(
        string $imagePath,
        array $metadata,
        array $sizes,
        array $config,
        string $format
    ): string {
        if (empty($sizes)) {
            return '';
        }

        // Sort dimensions from smallest to largest by calculating area (width * height)
        $sortedSizes = $sizes;
        uksort($sortedSizes, function ($a, $b) {
            [$aWidth, $aHeight] = explode('x', $a);
            [$bWidth, $bHeight] = explode('x', $b);
            $aArea = (int)$aWidth * (int)$aHeight;
            $bArea = (int)$bWidth * (int)$bHeight;
            return $aArea <=> $bArea;
        });

        $srcsetParts = [];

        foreach ($sortedSizes as $size => $descriptor) {
            $url = $this->urlGenerator->generate($imagePath, [
                'size' => $size,
                'crop' => $config['crop'] ?? 'cover',
                'focal' => $config['focal'] ?? [0.5, 0.5],
                'format' => $format,
            ]);

            $srcsetParts[] = $url . ' ' . $descriptor;
        }

        return implode(', ', $srcsetParts);
    }

    private function renderAttributes(array $attrs): string
    {
        $html = '';
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if ($value === true) {
                $html .= ' ' . htmlspecialchars($key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            } else {
                $html .= ' ' . htmlspecialchars($key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '="'
                    . htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
            }
        }

        return $html;
    }

    private function getMimeType(string $format): string
    {
        return match ($format) {
            'avif' => 'image/avif',
            'webp' => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'image/' . $format,
        };
    }

    private function extractImagePath(mixed $image): string
    {
        if (is_string($image)) {
            return $image;
        }

        if (is_array($image)) {
            return $image['path'] ?? $image['image_lg'] ?? '';
        }

        if (is_object($image)) {
            return $image->path ?? $image->image_lg ?? '';
        }

        throw new InvalidArgumentException('Invalid image input');
    }

    private function extractMetadata(mixed $image): array
    {
        if (is_array($image)) {
            return $image;
        }

        if (is_object($image)) {
            return (array) $image;
        }

        return [];
    }

    /**
     * Determine if lazy loading should be used for this element
     *
     * Lazy loading is enabled if:
     * - Global lazy load config is enabled AND
     * - The img has data-lazysrc attribute OR
     * - The img has loading="lazy" attribute (and not loading="eager")
     */
    private function shouldUseLazyLoad(array $imgAttrs): bool
    {
        if (!$this->lazyLoadConfig['enabled']) {
            return false;
        }

        // Explicitly enabled with data-lazysrc
        if (isset($imgAttrs['data-lazysrc']) && $imgAttrs['data-lazysrc']) {
            return true;
        }

        // Check loading attribute
        if (isset($imgAttrs['loading'])) {
            return $imgAttrs['loading'] === 'lazy';
        }

        // Default: use lazy load if enabled
        return true;
    }
}