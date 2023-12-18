<?php

namespace Contenir\Asset\View\Helper;

use Laminas\View\Helper\AbstractHtmlElement;

class AssetSizes extends AbstractHtmlElement
{
    protected $sizes = [];

    public function getSizes()
    {
        return $this->sizes;
    }

    public function setSizes(array $sizes = [])
    {
        $this->sizes = $sizes;
    }

    public function __invoke(
        $options = null
    ) {
        $value = [];
        $sizes = $options['sizes'] ?? $options;

        if (is_string($sizes)) {
            $sizes = $this->sizes[$sizes] ?? [];
        } elseif (is_array($sizes)) {
            $sizes = $options['sizes'] ?? [];
        }

        foreach ($sizes as $mediaQuery => $width) {
            if ($mediaQuery == array_key_last($sizes)) {
                $mediaQuery = '';
            } elseif (is_numeric($mediaQuery)) {
                $mediaQuery = sprintf('(max-width: %spx)', $mediaQuery);
            }

            $value[] = trim(sprintf('%s %spx', $mediaQuery, $width));
        }

        return $this->getView()->escapeHtmlAttr(join(', ', $value));
    }
}
