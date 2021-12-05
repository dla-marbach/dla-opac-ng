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
        $file = '?';
        $parameter = 'getEntities=1&q=' . trim($ids, ',');

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
        $line = ['ID', 'Link', 'Titelbeschreibung', 'Medientyp', 'Form und Inhalt', 'Medium', 'Zeit', 'Personen', 'Thema', 'Sprache', 'Ort', 'Datenbestand', 'Bibliografie', 'Sammlung', 'Digital'];
        fputcsv($f, $line, $delimiter);

        foreach ($array as $entry) {

            if(!empty($entry->facet_form_content)) {
                $facet_form_content = implode(";", $entry->facet_form_content);
            }
            if(!empty($entry->facet_medium)) {
                $facet_medium = implode(";", $entry->facet_medium);
            }
            if(!empty($entry->facet_time)) {
                $facet_time = implode(";", $entry->facet_time);
            }
            if(!empty($entry->facet_names)) {
                if (is_array($entry->facet_names)) {
                    $facet_names = implode(";", array_map('htmlspecialchars_decode', $entry->facet_names));
                } else {
                    $facet_names = implode(";", $entry->facet_names);
                }
            }
            if(!empty($entry->facet_subject)) {
                if (is_array($entry->facet_subject)) {
                    $facet_subject = implode(";", array_map('htmlspecialchars_decode', $entry->facet_subject));
                } else {
                    $facet_subject = implode(";", $entry->facet_subject);
                }
            }
            if(!empty($entry->facet_language)) {
                $facet_language = implode(";", $entry->facet_language);
            }
            if(!empty($entry->facet_location)) {
                if (is_array($entry->facet_location)) {
                    $facet_location = implode(";", array_map('htmlspecialchars_decode', $entry->facet_location));
                } else {
                    $facet_location = implode(";", $entry->facet_location);
                }
            }
            if(!empty($entry->listview_type)) {
                $listview_type = implode(";", $entry->listview_type);
            }
            if(!empty($entry->filter_bibliography)) {
                $filter_bibliography = implode(";", $entry->filter_bibliography);
            }
            if(!empty($entry->filter_collection)) {
                if (is_array($entry->filter_collection)) {
                    $filter_collection = implode(";", array_map('htmlspecialchars_decode', $entry->filter_collection));
                } else {
                    $filter_collection = implode(";", $entry->filter_collection);
                }
            }

            $line = [
                $entry->id,
                $protocol . trim($domainName, '/') . $PLUGIN_PATH . $entry->id,
                $entry->title,
                $listview_type,
                $facet_form_content,
                $facet_medium,
                $facet_time,
                $facet_names,
                $facet_subject,
                $facet_language,
                $facet_location,
                $entry->facet_source,
                $filter_bibliography,
                $filter_collection,
                $entry->filter_digital
            ];

            fputcsv($f, $line, $delimiter);
        }
        exit;
    }
}