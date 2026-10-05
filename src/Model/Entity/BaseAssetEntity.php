<?php

declare(strict_types=1);

namespace Contenir\Asset\Model\Entity;

use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Override;

/**
 * Mapped base for an application's asset entity. Declares the standard
 * asset columns; the application extends it with #[Table] and any extra
 * columns (typically foreign keys such as entry_id that link an asset to
 * the record it belongs to):
 *
 *     #[Table('asset')]
 *     final class Asset extends BaseAssetEntity
 *     {
 *         #[Column('entry_id')]
 *         public ?int $entryId = null;
 *     }
 *
 * @api
 *
 * @mago-expect lint:class-name Keeps the 1.x class name so applications only change their subclass bodies.
 */
abstract class BaseAssetEntity implements ResourceInterface
{
    #[Id(generated: true)]
    #[Column('asset_id')]
    public ?int $assetId = null;

    #[Column]
    public string $type;

    #[Column('user_id')]
    public ?int $userId = null;

    #[Column]
    public ?string $title = null;

    #[Column]
    public ?string $path = null;

    #[Column('mime_type')]
    public ?string $mimeType = null;

    #[Column]
    public ?string $active = null;

    #[Column('image_lg')]
    public ?string $imageLg = null;

    #[Column]
    public ?string $thumbnail = null;

    #[Column]
    public int $sequence = 0;

    #[Override]
    public function getResourceId(): string
    {
        return 'asset';
    }
}
