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
        $entities[] = [
            'id' => htmlspecialchars($doc['id']),
            'listview_title' => !empty($doc['listview_title']) ? htmlspecialchars($doc['listview_title']) : '',
            'listview_associate' => !empty($doc['listview_associate']) ? array_map('htmlspecialchars', $doc['listview_associate']) : '',
            'listview_additional1' => !empty($doc['listview_additional1']) ? array_map('htmlspecialchars', $doc['listview_additional1']) : '',
            'listview_additional2' => !empty($doc['listview_additional2']) ? array_map('htmlspecialchars', $doc['listview_additional2']) : '',
            'picture_midi' => !empty($doc['picture_midi']) ? $doc['picture_midi'] : '',
        ];
    }

}

// Return result
header('Content-Type: application/json');
echo json_encode($entities);
