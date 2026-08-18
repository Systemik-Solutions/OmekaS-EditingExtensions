<?php declare(strict_types=1);

namespace EditingExtensions;

use Doctrine\DBAL\Connection;
use RuntimeException;

class UsedTermCache
{
    public const TABLE_NAME = Module::USED_TERM_CACHE_TABLE;

    private const CACHE_VERSION = 1;
    private const CACHE_ROW_ID = 1;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getPropertyIds(): array
    {
        return $this->getCache()['properties'];
    }

    public function getResourceClassIds(): array
    {
        return $this->getCache()['resource_classes'];
    }

    public function invalidate(): void
    {
        // Always hit the database. Omeka settings are cached per process, so
        // deleting a settings-backed cache can be skipped by a long-running
        // process whose snapshot predates the cache row.
        $this->connection->delete(
            self::TABLE_NAME,
            ['id' => self::CACHE_ROW_ID]
        );
    }

    private function getCache(): array
    {
        $table = $this->connection->getDatabasePlatform()
            ->quoteIdentifier(self::TABLE_NAME);
        $row = $this->connection->executeQuery(
            sprintf('SELECT cache_value FROM %s WHERE id = ?', $table),
            [self::CACHE_ROW_ID]
        )->fetchAssociative();
        $cache = $row
            ? json_decode((string) $row['cache_value'], true)
            : null;
        if (!$this->isValid($cache)) {
            $cache = $this->build();
            $this->store($cache);
        }

        return $cache;
    }

    private function isValid($cache): bool
    {
        return is_array($cache)
            && ($cache['version'] ?? null) === self::CACHE_VERSION
            && isset($cache['properties'], $cache['resource_classes'])
            && is_array($cache['properties'])
            && is_array($cache['resource_classes']);
    }

    private function store(array $cache): void
    {
        $json = json_encode($cache);
        if ($json === false) {
            throw new RuntimeException('Could not encode the used-term cache.');
        }

        $table = $this->connection->getDatabasePlatform()
            ->quoteIdentifier(self::TABLE_NAME);
        $this->connection->executeStatement(
            sprintf(
                'INSERT INTO %s (id, cache_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE cache_value = VALUES(cache_value)',
                $table
            ),
            [self::CACHE_ROW_ID, $json]
        );
    }

    private function build(): array
    {
        $platform = $this->connection->getDatabasePlatform();
        $valueTable = $platform->quoteIdentifier('value');
        $resourceTable = $platform->quoteIdentifier('resource');

        $propertyIds = $this->connection->executeQuery(
            sprintf(
                'SELECT DISTINCT property_id FROM %s ORDER BY property_id',
                $valueTable
            )
        )->fetchFirstColumn();
        $resourceClassIds = $this->connection->executeQuery(
            sprintf(
                'SELECT DISTINCT resource_class_id
                 FROM %s
                 WHERE resource_class_id IS NOT NULL
                 ORDER BY resource_class_id',
                $resourceTable
            )
        )->fetchFirstColumn();

        return [
            'version' => self::CACHE_VERSION,
            'properties' => array_map('intval', $propertyIds),
            'resource_classes' => array_map('intval', $resourceClassIds),
        ];
    }
}
