<?php

namespace Contenir\Asset\Model\Entity;

use Contenir\Db\Model\Entity\AbstractEntity;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

abstract class BaseAssetEntity extends AbstractEntity implements
    ResourceInterface
{
    public function getResourceId()
    {
        return 'asset';
    }
}
