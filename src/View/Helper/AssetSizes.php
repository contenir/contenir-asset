<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Laminas\View\Helper\AbstractHelper;

use function array_keys;
use function count;
use function implode;
use function is_array;
use function is_numeric;
use function is_string;

/**
 * AssetSizes - Generates the sizes attribute for responsive images
 *
 * Usage:
 * <?= $this->assetSizes('responsive') ?>
 * <?= $this->assetSizes(['768' => '100vw', '1024' => '50vw', '100vw']) ?>
 */
class AssetSizes extends AbstractHelper
{
    private array $presets = [];

    public function __construct(array $config = [])
    {
        $this->presets = $config['presets'] ?? [];
    }

    /**
     * Generate sizes attribute
     *
     * @param string|array $sizes Preset name or array of sizes
     * @return string Sizes attribute value
     */
    public function __invoke(string|array $sizes): string
    {
        if (is_string($sizes)) {
            $sizes = $this->presets[$sizes] ?? [];
        }

        if (empty($sizes)) {
            return '';
        }

        $parts = [];
        $keys = array_keys($sizes);
        $lastIndex = count($sizes) - 1;

        foreach ($sizes as $index => $value) {
            $key = $keys[$index];

            if ($index === $lastIndex) {
                // Last entry - no media query
                $parts[] = $value;
            } elseif (is_numeric($key)) {
                // Numeric key - auto-generate media query
                $parts[] = "(max-width: {$key}px) {$value}";
            } else {
                // Custom media query
                $parts[] = "{$key} {$value}";
            }
        }

        return $this->escapeHtmlAttr(implode(', ', $parts));
    }

    public function setPresets(array $presets): self
    {
        $this->presets = $presets;
        return $this;
    }
}