<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Contenir\Asset\Service\AssetUrlGenerator;
use Laminas\View\Helper\AbstractHelper;

/**
 * AssetUrl - Simple helper to generate single asset URL
 *
 * Usage:
 * <?= $this->assetUrl($image, ['size' => '800x600', 'format' => 'webp']) ?>
 */
class AssetUrl extends AbstractHelper
{
    public function __construct(
        private AssetUrlGenerator $urlGenerator
    ) {
    }

    /**
     * Generate asset URL
     *
     * @param string $imagePath Image path
     * @param array $options Options (size, format, crop, focal)
     * @return string Asset URL
     */
    public function __invoke(string $imagePath, array $options = []): string
    {
        return $this->urlGenerator->generate($imagePath, $options);
    }
}