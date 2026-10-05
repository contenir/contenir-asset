<?php

declare(strict_types=1);

namespace Contenir\Asset\View\Helper;

use Laminas\Escaper\Escaper;

use function array_key_last;
use function implode;
use function is_int;
use function trim;

/**
 * Builds an escaped `sizes` attribute value from breakpoints: integer keys
 * become "(max-width: Npx)", string keys are used as media queries, and
 * the last entry is the unconditional default.
 *
 *     <img sizes="<?= $this->assetSizes([768 => 100, 1200 => 600, 'default' => 900]) ?>">
 *     // (max-width: 768px) 100px, (max-width: 1200px) 600px, 900px
 *
 * @api
 */
final class AssetSizes
{
    /**
     * @param array<string, array<int|string, int|string>> $sizes named size sets from configuration
     */
    public function __construct(
        private readonly array $sizes = [],
        private readonly Escaper $escaper = new Escaper(),
    ) {}

    /**
     * @param string|array<array-key, mixed>|null $options a size set name, an array of sizes, or ['sizes' => ...]
     */
    public function __invoke(string|array|null $options = null): string
    {
        $sizes = SizeOptions::resolve($options, $this->sizes);
        $last  = array_key_last($sizes);
        $value = [];
        foreach ($sizes as $breakpoint => $width) {
            $query = match (true) {
                $breakpoint === $last => '',
                is_int($breakpoint) => "(max-width: {$breakpoint}px)",
                default             => $breakpoint,
            };
            $value[] = trim("{$query} {$width}px");
        }

        return $this->escaper->escapeHtmlAttr(implode(', ', $value));
    }
}
