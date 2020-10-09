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

$filter = $_POST['filterIds'];

$table = $tables[$type];

// Prepare JSON for jTree
$jTree = [];

if ($action == 'getNodes') {

    if (!empty($filter)) {
        $placeholders = implode(',', array_fill(0, count(explode(",", $filter)), '?'));
        $stmt = $db->prepare('SELECT uid,parent_id,record_id,treeview_title,facet_value,hasChild FROM ' . $table . ' WHERE parent_id = ? AND uid IN (' . $placeholders . ') ORDER BY treeview_title;');

        //  call bind_param with $filter as array  $stmt->bind_param('ss', $nodeId, $filter);
        $paramArray = array_merge([$nodeId], explode(",", $filter));

        // add types as first parameter for bind_param
        $typeArray = '';
        foreach ($paramArray as $parameter) {
            $typeArray .= 's';
        }
        // call "bind_param" with all parameters as array
        call_user_func_array(array($stmt, 'bind_param'), array_merge([$typeArray], $paramArray));
    } else {
        $stmt = $db->prepare('SELECT uid,parent_id,record_id,treeview_title,facet_value,hasChild FROM ' . $table . ' WHERE parent_id = ? ORDER BY treeview_title;');
        $stmt->bind_param('s', $nodeId);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $data = mysqli_fetch_fields($result);

    while ($row = $result->fetch_assoc()) {
        $jTree[] = [
            'uid' => $row["uid"],
            'parent_id' => $row["parent_id"],
            'record_id' => $row["record_id"],
            'title' => $row["treeview_title"],
            'facet_value' => $row["facet_value"],
            'hasChild' => $row["hasChild"],
        ];
    }

} else if ($action == 'searchNodes') {

    $addOperator = function($x) { return '+'.$x; };

    $searchwords = implode(' ', array_map($addOperator, explode(' ', $search)));

    $stmt = $db->prepare('SELECT uid,parent_id,record_id,treeview_title,facet_value,hasChild FROM ' . $table . ' WHERE MATCH (treeview_title,listview_title,listview_associate,listview_additional1,listview_additional2) AGAINST (? IN BOOLEAN MODE);');

    $stmt->bind_param('s', $searchwords);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = mysqli_fetch_fields($result);

    while ($row = $result->fetch_assoc()) {
        $jTree[] = [
            'uid' => $row["uid"],
            'parent_id' => $row["parent_id"],
            'record_id' => $row["record_id"],
            'title' => $row["treeview_title"],
            'facet_value' => $row["facet_value"],
            'hasChild' => $row["hasChild"],
        ];

        // build array of uids
        $jTree['foundUids'][$row["uid"]] = $row["uid"];
        $stmt = $db->prepare('SELECT uid,parent_id FROM ' . $table . ' WHERE uid = ?');
        $stmt->bind_param('i', $row["parent_id"]);
        $stmt->execute();

        $parentResult = $stmt->get_result();

        while ($parentsRow = $parentResult->fetch_assoc()) {

            $jTree['foundUids'][$parentsRow["uid"]] = $parentsRow["uid"];

            if ($parentsRow['parent_id']) {
                $stmt = $db->prepare('SELECT uid,parent_id FROM ' . $table . ' WHERE uid = ?');

                $stmt->bind_param('s', $parentsRow['parent_id']);
                $stmt->execute();
                $parentResult = $stmt->get_result();

                $data = mysqli_fetch_fields($result);
            }
        }

    }

    $jTree['foundUids'] = implode($jTree['foundUids'], ',');

} else if ($action == 'getAllParents') {

    $stmt = $db->prepare('SELECT uid,parent_id,record_id,treeview_title FROM ' . $table . ' WHERE record_id = ?');

    $stmt->bind_param('s', $nodeId);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = mysqli_fetch_fields($result);

    $jTree = [];
    $currentTree = [];

    while ($row = $result->fetch_assoc()) {
        if ($row['parent_id']) {
            $stmt = $db->prepare('SELECT uid,parent_id,record_id,treeview_title FROM ' . $table . ' WHERE uid = ?');

            $stmt->bind_param('s', $row['parent_id']);
            $stmt->execute();
            $result = $stmt->get_result();

            $data = mysqli_fetch_fields($result);

            if (empty($currentTree)) {
                $currentTree['record_id'] = $row['record_id'];
                $currentTree['uid'] = $row['uid'];
                $currentTree['treeview_title'] = $row['treeview_title'];
            } else {
                $newTree['record_id'] = $row['record_id'];
                $newTree['uid'] = $row['uid'];
                $newTree['treeview_title'] = $row['treeview_title'];
                $newTree['child'] = $currentTree;
                $currentTree = $newTree;
            }

        } else {
            $newTree['record_id'] = $row['parent_id'];
            $newTree['uid'] = $row['uid'];
            $newTree['treeview_title'] = $row['treeview_title'];
            $newTree['child'] = $currentTree;
            $currentTree = $newTree;

            $jTree = $currentTree;
        }

    }

} else if ($action == 'getStructure') {

    $stmt = $db->prepare('SELECT uid,parent_id,record_id,treeview_title,facet_value,hasChild FROM 
        (SELECT uid,parent_id,record_id,treeview_title,facet_value,hasChild FROM ' . $table . ' ORDER BY parent_id, record_id) records,
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
            'title' => $row["treeview_title"],
            'facet_value' => $row["facet_value"],
            'hasChild' => $row["hasChild"],
        ];
    }
}

// Return result
header('Content-Type: application/json');
echo json_encode($jTree);
