<?php
include_once 'EidSettings.php';

// Configuration options
$solr_select_url = $host . $core . '/select';

// Array of entity facts
$entity = [];

// Get query string
$query = $_GET['q'];


// Get Solr record
$response = file_get_contents(
    $solr_select_url.'?q='.urlencode('id:('.str_replace(',',' OR ',$query).')').'&rows=200',
    FALSE,
    stream_context_create([
        'http' => [
            'method' => 'GET',
            'follow_location' => 0,
            'timeout' => 1.0
        ]
    ])
);

// Parse JSON response
if ($response !== FALSE) {
    $json = json_decode($response, TRUE);
    foreach ($json['response']['docs'] as $key => $doc) {

        $title = '';
        if (!empty($doc['listview_additional2'])) {
            foreach ($doc['listview_additional2'] as $listview_additional2) {
                $title = htmlspecialchars($doc['listview_additional2']) . $title;
            }
        }
        if (!empty($doc['listview_additional1'])) {
            if (!empty($title)) {
                $title = '. - ' . $title;
            }
            foreach ($doc['listview_additional1'] as $listview_additional1) {
                $title = htmlspecialchars($listview_additional1) . $title;
            }
        }
        if (!empty($doc['listview_associate'])) {
            if (!empty($title)) {
                $title = '. - ' . $title;
            }
            foreach ($doc['listview_associate'] as $listview_associate) {
                $title = htmlspecialchars($listview_associate) . $title;
            }
        }
        if (!empty($doc['listview_title'])) {
            if (!empty($title)) {
                $title = ' / ' . $title;
            }
            $title = $doc['listview_title'] . $title;
        }

        $entities[] = [
            'id' => htmlspecialchars($doc['id']),
            'listview_title' => !empty($doc['listview_title']) ? htmlspecialchars($doc['listview_title']) : '',
            'listview_associate' => !empty($doc['listview_associate']) ? array_map('htmlspecialchars', $doc['listview_associate']) : '',
            'listview_additional1' => !empty($doc['listview_additional1']) ? array_map('htmlspecialchars', $doc['listview_additional1']) : '',
            'facet_form_content' => !empty($doc['facet_form_content']) ? array_map('htmlspecialchars', $doc['facet_form_content']) : '',
            'facet_medium' => !empty($doc['facet_medium']) ? array_map('htmlspecialchars', $doc['facet_medium']) : '',
            'facet_time' => !empty($doc['facet_time']) ? array_map('htmlspecialchars', $doc['facet_time']) : '',
            'facet_names' => !empty($doc['facet_names']) ? array_map('htmlspecialchars', $doc['facet_names']) : '',
            'facet_subject' => !empty($doc['facet_subject']) ? array_map('htmlspecialchars', $doc['facet_subject']) : '',
            'facet_language' => !empty($doc['facet_language']) ? array_map('htmlspecialchars', $doc['facet_language']) : '',
            'facet_location' => !empty($doc['facet_location']) ? array_map('htmlspecialchars', $doc['facet_location']) : '',
            'facet_source' => !empty($doc['facet_source']) ? htmlspecialchars($doc['facet_source']) : '',
            'picture_midi' => !empty($doc['picture_midi']) ? $doc['picture_midi'] : '',
            'title' => $title,
        ];
    }

}

// Return result
header('Content-Type: application/json');
echo json_encode($entities);
