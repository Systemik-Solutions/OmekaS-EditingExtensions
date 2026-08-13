<?php declare(strict_types=1);

namespace EditingExtensions;

use Doctrine\DBAL\Connection;
use Omeka\Settings\SettingsInterface;

class UsedTermCache
{
    public const SETTING_KEY = 'EditingExtensions_used_term_ids';

    private const CACHE_VERSION = 1;

    private Connection $connection;
    private SettingsInterface $settings;

    public function __construct(
        Connection $connection,
        SettingsInterface $settings
    ) {
        $this->connection = $connection;
        $this->settings = $settings;
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
        $this->settings->delete(self::SETTING_KEY);
    }

    private function getCache(): array
    {
        $cache = $this->settings->get(self::SETTING_KEY);
        if (!$this->isValid($cache)) {
            $cache = $this->build();
            $this->settings->set(self::SETTING_KEY, $cache);
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
