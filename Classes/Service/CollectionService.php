<?php

namespace Dla\DlaOpacNg\Service;

class CollectionService
{
    private $db;

    private $table;

    private $order;

    private $fields;

    /**
     * Initializes the database connection and sets the character set.
     * Also initializes the use of a collection.
     *
     * @return void
     */
    public function __construct()
    {
        $config = require '../config/system/settings.php';
        // Connect to DB
        $this->db = mysqli_connect(
            getenv('TYPO3__DB__host'),
            getenv('TYPO3__DB__username'),
            getenv('TYPO3__DB__password'),
            getenv('TYPO3__DB__database'),
            getenv('TYPO3__DB__port')
        );

        // Set charset
        mysqli_set_charset(
            $this->db,
            $config['DB']['Connections']['Default']['charset']
        );

        // init collection
        $this->useCollection();
    }

    /**
     * Sets the table to use for the collection.
     *
     * @return void
     */
    public function useCollection()
    {
        $this->table = 'tx_dlaopacng_collection';
        $this->order = 'displayTree';
        $this->fields = 'uid,parent_id,record_id,displayTree,facet_value,hasChild';
    }

    /**
     * Sets the table to use for the classification.
     *
     * @return void
     */
    public function useClassification()
    {
        $this->table = 'tx_dlaopacng_classification';
        $this->order = 'display';
        $this->fields = 'uid,parent_id,record_id,displayTree,facet_value,hasChild,count';
    }

    /**
     * Returns all parents of a node.
     *
     * @param String $nodeId
     * @return array
     */
    public function getAllParents(String $nodeId)
    {

        $stmt = $this->db->prepare('SELECT uid,parent_id,record_id,displayTree FROM ' . $this->table . ' WHERE record_id = ?');

        $stmt->bind_param('s', $nodeId);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = mysqli_fetch_fields($result);

        $jTree = [];
        $currentTree = [];

        while ($row = $result->fetch_assoc()) {

            $stmt = $this->db->prepare('SELECT uid,parent_id,record_id,displayTree FROM ' . $this->table . ' WHERE uid = ?');

            $stmt->bind_param('s', $row['parent_id']);
            $stmt->execute();
            $parentResult = $stmt->get_result();

            $parentData = mysqli_fetch_fields($parentResult);

            $currentTree['record_id'] = $row['record_id'];
            $currentTree['uid'] = $row['uid'];
            $currentTree['displayTree'] = $row['displayTree'];

            while ($rowParent = $parentResult->fetch_assoc()) {

                if ($rowParent['parent_id']) {

                    $stmt = $this->db->prepare('SELECT uid,parent_id,record_id,displayTree FROM ' . $this->table . ' WHERE uid = ?');

                    $stmt->bind_param('s', $rowParent['parent_id']);
                    $stmt->execute();
                    $parentResult = $stmt->get_result();

                    $parentData = mysqli_fetch_fields($parentResult);


                    if (empty($currentTree)) {
                        $currentTree['record_id'] = $rowParent['record_id'];
                        $currentTree['uid'] = $rowParent['uid'];
                        $currentTree['displayTree'] = $rowParent['displayTree'];
                    } else {
                        $newTree['record_id'] = $rowParent['record_id'];
                        $newTree['uid'] = $rowParent['uid'];
                        $newTree['displayTree'] = $rowParent['displayTree'];
                        $newTree['child'] = $currentTree;
                        $currentTree = $newTree;
                    }
                } else {
                    $newTree['record_id'] = $rowParent['parent_id'];
                    $newTree['uid'] = $rowParent['uid'];
                    $newTree['displayTree'] = $rowParent['displayTree'];
                    $newTree['child'] = $currentTree;
                    $currentTree = $newTree;

                    $jTree[] = $currentTree;
                    $currentTree = [];
                }
            }


        }
        return $jTree;
    }

    /**
     * Returns the structure of a node.
     *
     * @param String $nodeId
     * @return array
     */
    public function getStructure(String $nodeId)
    {
        $stmt = $this->db->prepare('SELECT ' . $this->fields . ' FROM 
                (SELECT ' . $this->fields . ' FROM ' . $this->table . ' ORDER BY parent_id, record_id) records,
                (SELECT @pv := ?) initialisation
                WHERE find_in_set(parent_id, @pv)
                AND length(@pv := concat(@pv, \', \', record_id))');

        $stmt->bind_param('i', $nodeId);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = mysqli_fetch_fields($result);

        while ($row = $result->fetch_assoc()) {
            $jTree[] = [
                'uid' => $row["uid"],
                'parent_id' => $row["parent_id"],
                'record_id' => $row["record_id"],
                'title' => $row["displayTree"],
                'facet_value' => $row["facet_value"],
                'hasChild' => $row["hasChild"],
                'count' => ($row['count'] ?: '')
            ];
        }
        return $jTree;
    }

    /**
     * Retrieves nodes based on specified filters and a given parent node ID.
     *
     * @param String $filter Comma-separated list of filters to apply to the query.
     * @param String $nodeId The parent node ID to retrieve child nodes from.
     * @return array An array of nodes matching the specified criteria.
     */
    public function getNodes(String $filter, String $nodeId)
    {
        if (!empty($filter)) {
            $placeholders = implode(',', array_fill(0, count(explode(",", $filter)), '?'));
            $stmt = $this->db->prepare('SELECT ' . $this->fields . ' FROM ' . $this->table . ' WHERE parent_id = ? AND uid IN (' . $placeholders . ') ORDER BY ' . $this->order . ';');

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
            $stmt = $this->db->prepare('SELECT ' . $this->fields . ' FROM ' . $this->table . ' WHERE parent_id = ? ORDER BY ' . $this->order . ';');
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
                'title' => $row["displayTree"],
                'facet_value' => $row["facet_value"],
                'hasChild' => $row["hasChild"],
                'count' => ($row['count'] ?? '')
            ];
        }

        return $jTree;
    }

    /**
     * Searches nodes based on a given search term and returns matched nodes with metadata.
     *
     * @param String $search The search term to be used for finding matching nodes.
     * @return array An array containing matched nodes, their metadata, and a list of found unique IDs.
     */
    public function searchNodes(String $search)
    {
        $addOperator = function ($x) {
            return '+' . $x;
        };

        $searchwords = implode(' ', array_map($addOperator, explode(' ', $search)));

        $stmt = $this->db->prepare('SELECT ' . $this->fields . ' FROM ' . $this->table . ' WHERE MATCH (displayTree,display,displayName,displayAddition1,displayAddition2) AGAINST (? IN BOOLEAN MODE);');

        $stmt->bind_param('s', $searchwords);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = mysqli_fetch_fields($result);

        while ($row = $result->fetch_assoc()) {
            $jTree[] = [
                'uid' => $row["uid"],
                'parent_id' => $row["parent_id"],
                'record_id' => $row["record_id"],
                'title' => $row["displayTree"],
                'facet_value' => $row["facet_value"],
                'hasChild' => $row["hasChild"],
                'count' => ($row['count'] ?? '')
            ];

            // build array of uids
            $jTree['foundUids'][$row["uid"]] = $row["uid"];
            $stmt = $this->db->prepare('SELECT uid,parent_id FROM ' . $this->table . ' WHERE uid = ?');
            $stmt->bind_param('i', $row["parent_id"]);
            $stmt->execute();

            $parentResult = $stmt->get_result();

            while ($parentsRow = $parentResult->fetch_assoc()) {

                $jTree['foundUids'][$parentsRow["uid"]] = $parentsRow["uid"];

                if ($parentsRow['parent_id']) {
                    $stmt = $this->db->prepare('SELECT uid,parent_id FROM ' . $this->table . ' WHERE uid = ?');

                    $stmt->bind_param('s', $parentsRow['parent_id']);
                    $stmt->execute();
                    $parentResult = $stmt->get_result();

                    $data = mysqli_fetch_fields($result);
                }
            }

        }

        if (!empty($jTree['foundUids'])) {
            $jTree['foundUids'] = implode(',', $jTree['foundUids']);
        } else {
            $jTree['foundUids'] = NULL;
        }

        return $jTree;
    }

}