<?php

namespace Contenir\Asset\View\Helper;

use Contenir\Asset\AssetManagerInterface;
use Laminas\View\Helper\AbstractHelper;

class Asset extends AbstractHelper
{
    protected $assetManager;

    public function __construct(AssetManagerInterface $assetManager = null)
    {
        $this->assetManager = $assetManager;
    }

    public function __invoke($assetId)
    {
        return $this->assetManager->findOneById($assetId);
    }
}
