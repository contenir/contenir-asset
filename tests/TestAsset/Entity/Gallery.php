<?php

declare(strict_types=1);

namespace ContenirTest\Asset\TestAsset\Entity;

use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\Table;

/**
 * An owner whose key column the asset entity does not map.
 */
#[Table('gallery')]
final class Gallery
{
    #[Id]
    #[Column('gallery_id')]
    public int $galleryId = 5;
}
