<?php

namespace Contenir\Asset;

use Contenir\Db\Model\Entity\AbstractEntity;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

class AssetEntity extends AbstractEntity implements
    ResourceInterface
{
    protected $primaryKeys = [
        'asset_id'
    ];

    protected $columns = [
        'asset_id',
        'user_id',
        'entity_id',
        'resource_type_id',
        'type',
        'layout',
        'theme',
        'size',
        'title',
        'slug',
        'alt',
        'caption',
        'attribution',
        'thumbnail',
        'image_lg',
        'path',
        'video',
        'logo',
        'url',
        'call_to_action',
        'publish',
        'expires',
        'created',
        'modified',
        'active'
    ];

    public function getResourceId()
    {
        return 'asset';
    }
}
