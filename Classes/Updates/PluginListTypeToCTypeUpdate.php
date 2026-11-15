<?php

namespace Dla\DlaOpacNg\Updates;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Stellt die Plugins dieser Extension von "Plugin"-Inhaltselementen (CType "list" mit list_type)
 * auf eigene Inhaltselemente (CType = Plugin-Signatur) um. TYPO3 v14 kennt den CType "list" und
 * das Feld list_type nicht mehr; ab TYPO3 v13.4 ist beides veraltet.
 *
 * Entspricht TYPO3\CMS\Extbase\Upgrades\AbstractListTypeToCTypeUpdate, das es erst ab TYPO3 v13.4 gibt.
 */
#[UpgradeWizard('dlaOpacNg_pluginListTypeToCType')]
final class PluginListTypeToCTypeUpdate implements UpgradeWizardInterface
{
    /**
     * Plugin-Signaturen, siehe ext_localconf.php.
     */
    public const PLUGIN_SIGNATURES = [
        'find_find',
        'dlaopacng_dlastart',
        'dlaopacng_dlacollection',
        'dlaopacng_dlaclassification',
    ];

    private const TABLE_CONTENT = 'tt_content';
    private const TABLE_BACKEND_USER_GROUPS = 'be_groups';

    public function getTitle(): string
    {
        return 'DLA OPAC: Plugins von list_type auf CType umstellen';
    }

    public function getDescription(): string
    {
        return 'Die Plugins (' . implode(', ', self::PLUGIN_SIGNATURES) . ') sind jetzt eigene Inhaltselemente. '
            . 'Dieser Wizard stellt vorhandene Inhaltselemente (CType "list") und die Berechtigungen der '
            . 'Backend-Benutzergruppen um. Voraussetzung für TYPO3 v14.';
    }

    public function executeUpdate(): bool
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        $connection = $connectionPool->getConnectionForTable(self::TABLE_CONTENT);
        foreach ($this->getContentElementsToUpdate() as $contentElement) {
            $connection->update(
                self::TABLE_CONTENT,
                ['CType' => (string)$contentElement['list_type'], 'list_type' => ''],
                ['uid' => (int)$contentElement['uid']]
            );
        }

        $connection = $connectionPool->getConnectionForTable(self::TABLE_BACKEND_USER_GROUPS);
        foreach ($this->getBackendUserGroupsToUpdate() as $backendUserGroup) {
            $connection->update(
                self::TABLE_BACKEND_USER_GROUPS,
                ['explicit_allowdeny' => self::migrateExplicitAllowDeny((string)$backendUserGroup['explicit_allowdeny'])],
                ['uid' => (int)$backendUserGroup['uid']]
            );
        }

        return true;
    }

    public function updateNecessary(): bool
    {
        return $this->getContentElementsToUpdate() !== [] || $this->getBackendUserGroupsToUpdate() !== [];
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * Ersetzt in den Berechtigungen einer Backend-Benutzergruppe "tt_content:list_type:<Plugin>"
     * durch "tt_content:CType:<Plugin>".
     */
    public static function migrateExplicitAllowDeny(string $explicitAllowDeny): string
    {
        $entries = GeneralUtility::trimExplode(',', $explicitAllowDeny, true);
        foreach ($entries as $key => $entry) {
            foreach (self::PLUGIN_SIGNATURES as $pluginSignature) {
                if ($entry === 'tt_content:list_type:' . $pluginSignature) {
                    $entries[$key] = 'tt_content:CType:' . $pluginSignature;
                }
            }
        }
        return implode(',', array_unique($entries));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getContentElementsToUpdate(): array
    {
        if (!$this->columnsExist(self::TABLE_CONTENT, ['CType', 'list_type'])) {
            return [];
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_CONTENT);
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->select('uid', 'list_type')
            ->from(self::TABLE_CONTENT)
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('list')),
                $queryBuilder->expr()->in('list_type', $queryBuilder->quoteArrayBasedValueListToStringList(self::PLUGIN_SIGNATURES))
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getBackendUserGroupsToUpdate(): array
    {
        if (!$this->columnsExist(self::TABLE_BACKEND_USER_GROUPS, ['explicit_allowdeny'])) {
            return [];
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_BACKEND_USER_GROUPS);
        $queryBuilder->getRestrictions()->removeAll();
        $records = $queryBuilder
            ->select('uid', 'explicit_allowdeny')
            ->from(self::TABLE_BACKEND_USER_GROUPS)
            ->where(
                $queryBuilder->expr()->like(
                    'explicit_allowdeny',
                    $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards('tt_content:list_type:') . '%')
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
        return array_values(array_filter(
            $records,
            static fn(array $record): bool => self::migrateExplicitAllowDeny((string)$record['explicit_allowdeny']) !== (string)$record['explicit_allowdeny']
        ));
    }

    /**
     * In TYPO3 v14 entfernt die Datenbankanalyse die Spalte list_type, sobald sie nicht mehr gebraucht wird.
     *
     * @param list<string> $columns
     */
    private function columnsExist(string $table, array $columns): bool
    {
        $schemaManager = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table)->createSchemaManager();
        if (!$schemaManager->tablesExist([$table])) {
            return false;
        }
        $existingColumns = [];
        foreach ($schemaManager->listTableColumns($table) as $column) {
            $existingColumns[] = strtolower($column->getName());
        }
        return array_diff(array_map('strtolower', $columns), $existingColumns) === [];
    }
}
