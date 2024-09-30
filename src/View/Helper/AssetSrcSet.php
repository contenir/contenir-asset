<?php

namespace Contenir\Asset\View\Helper;

use Laminas\View\Helper\AbstractHtmlElement;

class AssetSrcSet extends AbstractHtmlElement
{
    protected $rootPath;
    protected $sizes = [];

    public function __construct()
    {
        $this->setRootPath(realpath('./public/'));
    }

    public function getSizes()
    {
        return $this->sizes;
    }

    public function setSizes(array $sizes = [])
    {
        $this->sizes = $sizes;
    }

    public function getRootPath()
    {
        return $this->rootPath;
    }

    public function setRootPath($rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function __invoke(
        $filepath = null,
        $options = null
    ) {
        $srcset = [];
        $sizes  = $options['sizes'] ?? $options;

        if (! file_exists($this->rootPath . $filepath)) {
            return $filepath;
        }

        if (is_string($sizes)) {
            $sizes = $this->sizes[$sizes] ?? [];
        } elseif (is_array($sizes)) {
            $sizes = $options['sizes'] ?? [];
        }

        foreach ($this->generateSrc($filepath, $sizes) as $size => $destFile) {
            $srcset[] = sprintf('%s %sw', $destFile, $size);
        }

        return $this->getView()->escapeHtmlAttr(join(', ', $srcset));
    }

    protected function generateSrc($filepath, $sizes)
    {
        $parts = @pathinfo($filepath);

        if (!$parts['filename']) {
            return false;
        }

        foreach ($sizes as $size => $resize) {
            $dimensions       = explode('x', $resize);
            $resizeDimensions = (count($dimensions) > 1) ? $resize : sprintf('%sx', $dimensions[0]);

            $destFile = sprintf(
                "%s/.%s/%s.%s",
                implode('/', array_map('urlencode', explode('/', $parts['dirname']))),
                $resizeDimensions,
                implode('/', array_map('urlencode', explode('/', $parts['filename']))),
                $parts['extension']
            );

            yield $size => $destFile;
        }
    }
}
