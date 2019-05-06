<?php

// Configuration options
$solr_select_url = 'http://scratch.dla-marbach.de:8983/solr/opac-ng-dev/select';

// Array of entity facts
$entity = [];

// Get query string
$query = $_GET['q'];

$prefix = $_GET['p'];

$activeFacets = $_GET['activeFacets'];

// Get relations
$responseRelations = file_get_contents(
    $solr_select_url.'?facet.field=facet_names_relations&facet.mincount=1&facet=on&facet.prefix='.urlencode($prefix).'&q='.urlencode($query . ' AND ' . $activeFacets).'&rows=0',
    FALSE,
    stream_context_create([
        'method' => 'GET',
        'follow_location' => 0,
        'timeout' => 1.0
    ])
);

$responseRoles = file_get_contents(
    $solr_select_url.'?facet.field=facet_names_roles&facet=on&facet.mincount=1&facet.prefix='.urlencode($prefix).'&q='.urlencode($query . ' AND ' . $activeFacets).'&rows=0',
    FALSE,
    stream_context_create([
        'method' => 'GET',
        'follow_location' => 0,
        'timeout' => 1.0
    ])
);

// Parse JSON response
if ($responseRelations !== FALSE && $responseRoles !== FALSE) {
    $jsonRelations = json_decode($responseRelations, TRUE);
    $jsonRoles = json_decode($responseRoles, TRUE);

    $relations = array($jsonRelations['facet_counts']['facet_fields']['facet_names_relations']);
    $roles = array($jsonRoles['facet_counts']['facet_fields']['facet_names_roles']);

//    $roles = $jsonRoles['facet_counts']['facet_fields']['facet_names_roles'];

    $output = array_merge($relations, $roles);

}

// Return result
header('Content-Type: application/json');
echo json_encode($output);
//echo json_encode($jsonRoles);