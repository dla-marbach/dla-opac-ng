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

        // field names
        $line = ['ID', 'Titel', 'Form und Inhalt', 'Medium', 'Zeit', 'Personen', 'Thema', 'Sprache', 'Ort', 'Datenbestand'];
        fputcsv($f, $line, $delimiter);

        foreach ($array as $entry) {
            $line = [
                $entry->id,
                $entry->title,
                implode(",", $entry->facet_form_content),
                implode(",", $entry->facet_medium),
                implode(",", $entry->facet_time),
                implode(",", $entry->facet_names),
                implode(",", $entry->facet_subject),
                implode(",", $entry->facet_language),
                implode(",", $entry->facet_location),
                $entry->facet_source
            ];

            fputcsv($f, $line, $delimiter);
        }
        exit;
    }
}