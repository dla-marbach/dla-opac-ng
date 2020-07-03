<?php

$config = require 'typo3conf/LocalConfiguration.php';
// Connect to DB
$db = mysqli_connect(
    $config['DB']['Connections']['Default']['host'],
    $config['DB']['Connections']['Default']['user'],
    $config['DB']['Connections']['Default']['password'],
    $config['DB']['Connections']['Default']['dbname'],
    $config['DB']['Connections']['Default']['port']
);

// Set charset
mysqli_set_charset(
    $db,
    $config['DB']['Connections']['Default']['charset']
);

$tables = [
    'classification' => 'tx_dlaopacng_classification',
    'collection' => 'tx_dlaopacng_collection'
];

$action = $_GET['action'];
$nodeId = $_GET['id'];
$search = $_GET['search'];
$type = $_GET['type'];

$table = $tables[$type];
$view_title = 'treeview_title_' . $type;

// Prepare JSON for jTree
$jTree = [];

if ($action == 'getNodes') {

    $stmt = $db->prepare('SELECT uid,parent_id,record_id,' . $view_title . ' AS title,hasChild FROM ' . $table . ' WHERE parent_id = ? ORDER BY ' . $view_title . ';');
    $stmt->bind_param('s', $nodeId);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = mysqli_fetch_fields($result);

    while ($row = $result->fetch_assoc()) {
        $jTree[] = [
            'uid' => $row["uid"],
            'parent_id' => $row["parent_id"],
            'record_id' => $row["record_id"],
            'title' => $row["title"],
            'hasChild' => $row["hasChild"],
        ];
    }

} else if ($action == 'searchNodes') {

    $stmt = $db->prepare('SELECT uid,parent_id,record_id,' . $view_title . ' AS title,hasChild FROM ' . $table . ' WHERE MATCH (' . $view_title . ',listview_title,listview_type,listview_associate,listview_additional1,listview_additional2) AGAINST (? IN NATURAL LANGUAGE MODE);');

    $stmt->bind_param('s', $search);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = mysqli_fetch_fields($result);

    while ($row = $result->fetch_assoc()) {
        $jTree[] = [
            'uid' => $row["uid"],
            'parent_id' => $row["parent_id"],
            'record_id' => $row["record_id"],
            'title' => $row["title"],
            'hasChild' => $row["hasChild"],
        ];
    }

} else if ($action == 'getAllParents') {

    $stmt = $db->prepare('SELECT uid,parent_id,record_id FROM ' . $table . ' WHERE record_id = ?');

    $stmt->bind_param('s', $nodeId);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = mysqli_fetch_fields($result);

    $jTree = [];
    $currentTree = [];

    while ($row = $result->fetch_assoc()) {
        if ($row['parent_id']) {
            $stmt = $db->prepare('SELECT uid,parent_id,record_id FROM ' . $table . ' WHERE uid = ?');

            $stmt->bind_param('s', $row['parent_id']);
            $stmt->execute();
            $result = $stmt->get_result();

            $data = mysqli_fetch_fields($result);

            if (empty($currentTree)) {
                $currentTree['record_id'] = $row['record_id'];
                $currentTree['uid'] = $row['uid'];
            } else {
                $newTree['record_id'] = $row['record_id'];
                $newTree['uid'] = $row['uid'];
                $newTree['child'] = $currentTree;
                $currentTree = $newTree;
            }

        } else {
            $newTree['record_id'] = $row['parent_id'];
            $newTree['uid'] = $row['uid'];
            $newTree['child'] = $currentTree;
            $currentTree = $newTree;

            $jTree = $currentTree;
        }

    }

} else if ($action == 'getStructure') {

    $stmt = $db->prepare('SELECT uid,parent_id,record_id,' . $view_title . ' AS view_title,hasChild FROM 
        (SELECT uid,parent_id,record_id,' . $view_title . ' AS title,hasChild FROM ' . $table . ' ORDER BY parent_id, record_id) records,
        (SELECT @pv := ?) initialisation
        WHERE find_in_set(parent_id, @pv)
        AND length(@pv := concat(@pv, ',', record_id))');

    $stmt->bind_param('i', $nodeId);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = mysqli_fetch_fields($result);

    while ($row = $result->fetch_assoc()) {
        $jTree[] = [
            'uid' => $row["uid"],
            'parent_id' => $row["parent_id"],
            'record_id' => $row["record_id"],
            'title' => $row["title"],
            'hasChild' => $row["hasChild"],
        ];
    }
}

// Return result
header('Content-Type: application/json');
echo json_encode($jTree);