<?php

declare(strict_types=1);

namespace ContenirTest\Asset\TestAsset\Entity;

use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\Table;

/**
 * The user who uploads assets; linked through the user_id column.
 */
#[Table('member')]
final class Member
{
    #[Id]
    #[Column('user_id')]
    public int $userId;
}
