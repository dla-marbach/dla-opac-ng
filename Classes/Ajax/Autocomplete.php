<?php

// Configuration options
$solr_suggest_url = 'http://scratch.dla-marbach.de:8983/solr/opac-ng-dev/suggest';
$solr_suggest_dictionary = 'mySuggester';

// Array of suggestions
$suggests = [];

// Get query string
$query = $_GET['q'];

// Get Solr suggestions
$response = file_get_contents(
    $solr_suggest_url.'?suggest=true&suggest.dictionary='.$solr_suggest_dictionary.'&suggest=true&suggest.q='.urlencode($query),
    FALSE,
    stream_context_create([
        'method' => 'GET',
        'follow_location' => 0,
        'timeout' => 1.0
    ])
);

// Parse JSON response
if ($response !== FALSE) {
    $json = json_decode($response, TRUE);

    foreach($json['suggest'][$solr_suggest_dictionary][$query]['suggestions'] as $suggestion) {
        list ($id, $normalized) = explode('␝', $suggestion['payload']);
        $suggests[] = [
            'id' => 'entity_ids:('.$id.')',
            'term' => htmlspecialchars($suggestion['term']),
            'normalized' => htmlspecialchars($normalized)
        ];
    }
}

// Return results
if (!empty($suggests)) {
    header('Content-Type: application/json');
    echo json_encode($suggests);
}
