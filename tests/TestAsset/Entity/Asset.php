<?php

declare(strict_types=1);

namespace ContenirTest\Asset\TestAsset\Entity;

use Contenir\Asset\Model\Entity\BaseAssetEntity;
use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Table;

/**
 * An application's asset entity: the base columns plus a link to entries.
 */
#[Table('asset')]
final class Asset extends BaseAssetEntity
{
    #[Column('entry_id')]
    public ?int $entryId = null;
}
