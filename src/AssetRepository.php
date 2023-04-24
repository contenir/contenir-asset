<?php

namespace Contenir\Asset;

use Contenir\Db\Model\Repository\AbstractRepository;

class AssetRepository extends AbstractRepository
{
    /**
     * @var string|array|TableIdentifier
     */
    protected $table = 'asset';
}
