<?php

namespace Dla\DlaOpacNg\ViewHelpers;

use Dla\DlaOpacNg\Service\OrderabilityResolver;
use Dla\DlaOpacNg\Service\SolrConnection;
use Solarium\Exception\HttpException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Liefert, ob der Bestellbutton für einen Bibliotheksdatensatz ohne Exemplare angezeigt wird.
 * Die Elternsätze (parent_id_mv) werden bei Bedarf aus Solr geladen.
 */
class OrderableViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('fields', 'array', 'Solr-Dokumentfelder des Datensatzes', true);
    }

    public function render(): bool
    {
        $fields = $this->arguments['fields'];
        $resolver = new OrderabilityResolver();

        // Ist der Datensatz selbst nicht bestellbar oder hat keine Eltern, spart das die Solr-Abfrage.
        $orderable = $resolver->isOrderable($fields, []);
        $parentIds = $this->parentIds($fields);
        if (!$orderable || $parentIds === []) {
            return $orderable;
        }

        return $resolver->isOrderable($fields, $this->loadParents($parentIds));
    }

    /**
     * @return list<string>
     */
    private function parentIds(array $fields): array
    {
        $ids = [];
        foreach ((array)($fields['parent_id_mv'] ?? []) as $id) {
            if (preg_match('/^[A-Z]{2}\d+$/', (string)$id)) {
                $ids[] = (string)$id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param list<string> $ids
     * @return list<array<string,mixed>>
     */
    private function loadParents(array $ids): array
    {
        $client = GeneralUtility::makeInstance(SolrConnection::class)->getClient();
        $query = $client->createSelect();
        $query->setQuery('id:(' . implode(' OR ', $ids) . ')');
        $query->setRows(count($ids));
        $query->clearFields();
        $query->addFields([
            'id', 'filterMedium_mv', 'filterSource', 'filterType_mv', 'digitalObject_hyperlink_mv',
            'item_id_mv', 'item_usageRestriction_mv', 'item_location_mv', 'item_digitalObject_hyperlink_mv',
        ]);

        $parents = [];
        try {
            $documents = $client->select($query)->getDocuments();
        } catch (HttpException) {
            // Wie dla:countFromSolr: Solr-Fehler dürfen die Detailseite nicht abbrechen.
            return [];
        }

        foreach ($documents as $document) {
            $parents[] = $document->getFields();
        }

        return $parents;
    }
}
