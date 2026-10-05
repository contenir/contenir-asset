<?php

declare(strict_types=1);

namespace ContenirTest\Asset\Trait;

use Contenir\Db\Model\EntityManager;
use Contenir\Db\Model\Metadata\AttributeMetadataFactory;
use PDO;
use PhpDb\Adapter\Adapter;
use PhpDb\Sqlite\AdapterPlatform;
use PhpDb\Sqlite\Pdo\Connection;
use PhpDb\Sqlite\Pdo\Driver;
use PhpDb\Sqlite\Pdo\Feature\SqliteRowCounter;

/**
 * A fresh in-memory SQLite database with the asset fixture schema, and an
 * EntityManager over it. Call {@see self::setUpAssetDatabase()} from setUp().
 */
trait AssetDatabaseTrait
{
    protected PDO $pdo;

    protected EntityManager $em;

    protected AttributeMetadataFactory $metadata;

    /**
     * @return list<array<string, mixed>>
     */
    protected function rows(string $sql): array
    {
        $statement = $this->pdo->query($sql);

        /** @var list<array<string, mixed>> */
        return false === $statement ? [] : $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function setUpAssetDatabase(string ...$seed): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        foreach ([
            'CREATE TABLE asset (asset_id INTEGER PRIMARY KEY AUTOINCREMENT, type TEXT NOT NULL, user_id INTEGER, '
                . 'title TEXT, path TEXT, mime_type TEXT, active TEXT, image_lg TEXT, thumbnail TEXT, '
                . 'sequence INTEGER NOT NULL DEFAULT 0, entry_id INTEGER)',
            'CREATE TABLE entry (entry_id INTEGER PRIMARY KEY, title TEXT NOT NULL)',
            'CREATE TABLE member (user_id INTEGER PRIMARY KEY)',
            ...$seed,
        ] as $statement) {
            $this->pdo->exec($statement);
        }

        $driver         = new Driver(new Connection($this->pdo), features: [new SqliteRowCounter()]);
        $this->metadata = new AttributeMetadataFactory();
        $this->em       = new EntityManager(new Adapter($driver, new AdapterPlatform($driver)), $this->metadata);
    }
}
