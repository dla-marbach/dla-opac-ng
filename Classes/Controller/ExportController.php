<?php
namespace Dla\DlaOpacNg\Controller;

use \TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ExportController extends ActionController
{

    /**
     * @param string $ids
     */
    public function csvAction(string $ids)
    {
        $extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dla_opac_ng');
        include_once $extPath . 'Classes/Ajax/EidSettings.php';

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domainName = $_SERVER['HTTP_HOST'] . '/';
        $file = 'index.php?';
        $parameter = 'eID=getEntities&q=' . trim($ids, ',');

        $csvFilename = 'marbach.csv';
        $delimiter = ';';

        // header for file download
        header('Content-Encoding: UTF-8');
        header('Content-type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.$csvFilename.'";');
        echo "\xEF\xBB\xBF";

        // send getEntities request // get all information from each id
        $json = file_get_contents($protocol . $domainName . $file . $parameter);

        $array = json_decode($json);

        $f = fopen('php://output', 'w');

        // add info
        $objDateTime = new \DateTime('NOW');
        $line = ['Deutsches Literaturarchiv Marbach. Auszug Katalog. ' . $objDateTime->format('d.m.Y')];
        fputcsv($f, $line, $delimiter);

        // field names
        $line = ['ID', 'Link', 'Titelbeschreibung', 'Form und Inhalt', 'Medium', 'Zeit', 'Personen', 'Thema', 'Sprache', 'Ort', 'Datenbestand', 'Medium', 'Bibliografie', 'Sammlung', 'Digital'];
        fputcsv($f, $line, $delimiter);

        foreach ($array as $entry) {
            $line = [
                $entry->id,
                $protocol . trim($domainName, '/') . $PLUGIN_PATH . $entry->id,
                $entry->title,
                implode(";", $entry->facet_form_content),
                implode(";", $entry->facet_medium),
                implode(";", $entry->facet_time),
                implode(";", $entry->facet_names),
                implode(";", array_map('htmlspecialchars_decode', $entry->facet_subject)),
                implode(";", $entry->facet_language),
                implode(";", $entry->facet_location),
                $entry->facet_source,
                implode(";", $entry->listview_type),
                implode(";", $entry->filter_bibliography),
                implode(";", $entry->filter_collection),
                $entry->filter_digital
            ];

            fputcsv($f, $line, $delimiter);
        }
        exit;
    }
}