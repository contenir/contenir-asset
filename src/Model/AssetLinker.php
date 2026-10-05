<?php

declare(strict_types=1);

namespace Contenir\Asset\Model;

use Contenir\Asset\Exception\InvalidArgumentException;
use Contenir\Asset\Model\Entity\BaseAssetEntity;
use Contenir\Db\Model\Exception\MappingException;
use Contenir\Db\Model\Metadata\MetadataFactoryInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use ReflectionException;
use ReflectionProperty;
use Stringable;

use function implode;
use function is_scalar;

/**
 * Connects assets to the records they belong to, using column names from
 * the mapping: an owner's primary-key columns (entry_id, user_id, ...) are
 * copied onto asset properties mapped to the same columns.
 *
 * @internal
 */
final readonly class AssetLinker
{
    public function __construct(
        private MetadataFactoryInterface $metadata,
    ) {}

    /**
     * Reflection on the declaring class, so private and inherited
     * properties are both reachable.
     *
     * @throws ReflectionException
     */
    private static function property(object $entity, string $name): ReflectionProperty
    {
        $declaring = (new ReflectionProperty($entity, $name))->getDeclaringClass()->getName();

        return new ReflectionProperty($declaring, $name);
    }

    /**
     * Assign property values to an asset, by property name.
     *
     * @param array<string, mixed> $values
     *
     * @throws InvalidArgumentException When a name is not a mapped column property.
     * @throws MappingException
     * @throws ReflectionException
     *
     * @mago-expect analysis:mixed-assignment Values are caller input, type-checked by PHP on assignment.
     */
    public function assign(BaseAssetEntity $asset, array $values): void
    {
        $metadata = $this->metadata->getMetadataFor($asset::class);
        foreach ($values as $property => $value) {
            if (! $metadata->hasField($property)) {
                throw InvalidArgumentException::unknownProperty($asset::class, $property);
            }

            self::property($asset, $property)->setValue($asset, $value);
        }
    }

    /**
     * Storage folder name for a target record: its ACL resource id and
     * primary-key values, e.g. "entry-42".
     *
     * @throws MappingException
     * @throws ReflectionException
     *
     * @mago-expect analysis:mixed-assignment Key values are read reflectively from the owner.
     */
    public function folderFor(ResourceInterface $target): string
    {
        $parts = [$target->getResourceId()];
        foreach ($this->keys($target) as $value) {
            $parts[] = is_scalar($value) || $value instanceof Stringable ? (string) $value : '';
        }

        return implode('-', $parts);
    }

    /**
     * Copy $owner's primary-key values onto the asset properties mapped to
     * the same column names. Key columns the asset does not map are
     * skipped.
     *
     * @throws MappingException
     * @throws ReflectionException
     *
     * @mago-expect analysis:mixed-assignment Key values are read reflectively from the owner.
     */
    public function link(BaseAssetEntity $asset, object $owner): void
    {
        $assetMetadata = $this->metadata->getMetadataFor($asset::class);
        foreach ($this->keys($owner) as $column => $value) {
            if (! $assetMetadata->hasColumn($column)) {
                continue;
            }

            self::property($asset, $assetMetadata->getFieldForColumn($column)->propertyName)->setValue($asset, $value);
        }
    }

    /**
     * @return array<string, mixed> primary-key values keyed by column
     *
     * @throws MappingException
     * @throws ReflectionException
     */
    private function keys(object $owner): array
    {
        $keys = [];
        foreach ($this->metadata->getMetadataFor($owner::class)->identifier as $field) {
            $property                 = self::property($owner, $field->propertyName);
            $keys[$field->columnName] = $property->isInitialized($owner) ? $property->getValue($owner) : null;
        }

        return $keys;
    }
}
