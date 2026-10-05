<?php

declare(strict_types=1);

namespace ContenirTest\Asset\TestAsset\Entity;

use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\Table;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Override;

/**
 * A record assets are attached to.
 */
#[Table('entry')]
final class Entry implements ResourceInterface
{
    #[Id]
    #[Column('entry_id')]
    public int $entryId;

    #[Column]
    public string $title = '';

    #[Override]
    public function getResourceId(): string
    {
        return 'entry';
    }
}
