<?php

namespace Dla\DlaOpacNg\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\JsonResponse;

class Collection implements MiddlewareInterface
{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if (!isset($request->getQueryParams()['type'], $request->getQueryParams()['action'], $request->getQueryParams()['collection'])) {
            return $response;
        }

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
        $orderBy = [
            'classification' => 'listview_title',
            'collection' => 'treeview_title'
        ];

        $action = $request->getQueryParams()['action'];
        $nodeId = $request->getQueryParams()['nodeid'];
        $search = $request->getQueryParams()['search'];
        $type = $request->getQueryParams()['type'];

        $filter = $request->getParsedBody()['filterIds'];

        $table = $tables[$type];
        $order = $orderBy[$type];

        // Prepare JSON for jTree
        $jTree = [];

        if ($action == 'getNodes') {

            if (!empty($filter)) {
                $placeholders = implode(',', array_fill(0, count(explode(",", $filter)), '?'));
                $stmt = $db->prepare('SELECT uid,parent_id,record_id,treeview_title,facet_value,hasChild FROM ' . $table . ' WHERE parent_id = ? AND uid IN (' . $placeholders . ') ORDER BY ' . $order . ';');

                //  call bind_param with $filter as array  $stmt->bind_param('ss', $nodeId, $filter);
                $paramArray = array_merge([$nodeId], explode(",", $filter));

                // add types as first parameter for bind_param
                $typeArray = '';
                foreach ($paramArray as $parameter) {
                    $typeArray .= 's';
                }
                // call "bind_param" with all parameters as array using spread operator (...)
                $stmt->bind_param($typeArray, ...$paramArray);
            } else {
                $stmt = $db->prepare('SELECT uid,parent_id,record_id,treeview_title,facet_value,hasChild FROM ' . $table . ' WHERE parent_id = ? ORDER BY ' . $order . ';');
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

            $addOperator = function ($x) {
                return '+' . $x;
            };

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

            if (!empty($jTree['foundUids'])) {
                $jTree['foundUids'] = implode($jTree['foundUids'], ',');
            }

        } else if ($action == 'getAllParents') {

            $stmt = $db->prepare('SELECT uid,parent_id,record_id,treeview_title FROM ' . $table . ' WHERE record_id = ?');

            $stmt->bind_param('s', $nodeId);
            $stmt->execute();
            $result = $stmt->get_result();

            $data = mysqli_fetch_fields($result);

            $jTree = [];
            $currentTree = [];

            while ($row = $result->fetch_assoc()) {

                $stmt = $db->prepare('SELECT uid,parent_id,record_id,treeview_title FROM ' . $table . ' WHERE uid = ?');

                $stmt->bind_param('s', $row['parent_id']);
                $stmt->execute();
                $parentResult = $stmt->get_result();

                $parentData = mysqli_fetch_fields($parentResult);

                $currentTree['record_id'] = $row['record_id'];
                $currentTree['uid'] = $row['uid'];
                $currentTree['treeview_title'] = $row['treeview_title'];

                while ($rowParent = $parentResult->fetch_assoc()) {

                    if ($rowParent['parent_id']) {

                        $stmt = $db->prepare('SELECT uid,parent_id,record_id,treeview_title FROM ' . $table . ' WHERE uid = ?');

                        $stmt->bind_param('s', $rowParent['parent_id']);
                        $stmt->execute();
                        $parentResult = $stmt->get_result();

                        $parentData = mysqli_fetch_fields($parentResult);


                        if (empty($currentTree)) {
                            $currentTree['record_id'] = $rowParent['record_id'];
                            $currentTree['uid'] = $rowParent['uid'];
                            $currentTree['treeview_title'] = $rowParent['treeview_title'];
                        } else {
                            $newTree['record_id'] = $rowParent['record_id'];
                            $newTree['uid'] = $rowParent['uid'];
                            $newTree['treeview_title'] = $rowParent['treeview_title'];
                            $newTree['child'] = $currentTree;
                            $currentTree = $newTree;
                        }
                    } else {
                        $newTree['record_id'] = $rowParent['parent_id'];
                        $newTree['uid'] = $rowParent['uid'];
                        $newTree['treeview_title'] = $rowParent['treeview_title'];
                        $newTree['child'] = $currentTree;
                        $currentTree = $newTree;

                        $jTree[] = $currentTree;
                        $currentTree = [];
                    }
                }


            }

        } else if ($action == 'getStructure') {

            $stmt = $db->prepare('SELECT uid,parent_id,record_id,treeview_title,facet_value,hasChild FROM 
                (SELECT uid,parent_id,record_id,treeview_title,facet_value,hasChild FROM ' . $table . ' ORDER BY parent_id, record_id) records,
                (SELECT @pv := ?) initialisation
                WHERE find_in_set(parent_id, @pv)
                AND length(@pv := concat(@pv, ', ', record_id))');

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
        return new JsonResponse($jTree);
    }
}
