<?php

namespace Contenir\Asset\View\Helper;

use Laminas\View\Helper\ServerUrl;

class AssetUrl extends ServerUrl
{
    protected $scheme;
    protected $host;

    public function __construct($options)
    {
        $this->host = $options['host'] ?? null;
    }
}
