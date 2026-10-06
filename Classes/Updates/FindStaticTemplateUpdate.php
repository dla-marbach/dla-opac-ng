<?php

namespace Dla\DlaOpacNg\Updates;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Entfernt das statische TypoScript der ehemaligen Extension dla/find (EXT:find) aus den
 * TypoScript-Datensätzen. Die Konfiguration ist jetzt Bestandteil des statischen TypoScripts
 * von dla_opac_ng, das an derselben Stelle eingefügt wird, falls es noch fehlt.
 */
#[UpgradeWizard('dlaOpacNg_findStaticTemplate')]
final class FindStaticTemplateUpdate implements UpgradeWizardInterface
{
    private const TABLE = 'sys_template';
    private const FIND_STATIC = 'EXT:find/Configuration/TypoScript';
    private const OPAC_STATIC = 'EXT:dla_opac_ng/Configuration/TypoScript';

    public function getTitle(): string
    {
        return 'DLA OPAC: Statisches TypoScript von EXT:find entfernen';
    }

    public function getDescription(): string
    {
        return 'Die Extension dla/find ist in dla_opac_ng aufgegangen. Dieser Wizard entfernt "'
            . self::FIND_STATIC . '" aus den TypoScript-Datensätzen und ergänzt bei Bedarf "'
            . self::OPAC_STATIC . '".';
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE);
        foreach ($this->getAffectedRecords() as $record) {
            $connection->update(
                self::TABLE,
                ['include_static_file' => self::migrateIncludeStaticFile((string)$record['include_static_file'])],
                ['uid' => (int)$record['uid']]
            );
        }
        return true;
    }

    public function updateNecessary(): bool
    {
        return $this->getAffectedRecords() !== [];
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * Ersetzt EXT:find durch EXT:dla_opac_ng (oder entfernt es, falls dla_opac_ng bereits eingebunden ist).
     */
    public static function migrateIncludeStaticFile(string $includeStaticFile): string
    {
        $entries = GeneralUtility::trimExplode(',', $includeStaticFile, true);
        $hasOpac = in_array(self::OPAC_STATIC, array_map(self::normalize(...), $entries), true);
        $result = [];
        foreach ($entries as $entry) {
            if (self::normalize($entry) === self::FIND_STATIC) {
                if (!$hasOpac) {
                    $result[] = self::OPAC_STATIC;
                    $hasOpac = true;
                }
                continue;
            }
            $result[] = $entry;
        }
        return implode(',', $result);
    }

    private static function normalize(string $entry): string
    {
        return rtrim($entry, '/');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAffectedRecords(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $records = $queryBuilder
            ->select('uid', 'include_static_file')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->like(
                    'include_static_file',
                    $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards(self::FIND_STATIC) . '%', Connection::PARAM_STR)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
        return array_values(array_filter(
            $records,
            static fn(array $record): bool => self::migrateIncludeStaticFile((string)$record['include_static_file']) !== (string)$record['include_static_file']
        ));
    }
}
