<?php

namespace Contenir\Asset;

interface AssetManagerInterface
{
    /**
     * @return string
     */
    public function findOneById($assetId);
}
