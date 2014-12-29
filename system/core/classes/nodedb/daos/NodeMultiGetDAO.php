<?php
/**
 * NodeMultiGetDAO
 *
 * PHP version 5
 *
 * Crowd Fusion
 * Copyright (C) 2009-2010 Crowd Fusion, Inc.
 * http://www.crowdfusion.com/
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted under the terms of the BSD License.
 *
 * @package     CrowdFusion
 * @copyright   2009-2010 Crowd Fusion Inc.
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version     $Id: NodeMultiGetDAO.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeMultiGetDAO
 *
 * @package     CrowdFusion
 */
class NodeMultiGetDAO extends AbstractNodeDAO
{


    public function setNodeTagsDAO(NodeTagsDAO $NodeTagsDAO)
    {
        $this->NodeTagsDAO = $NodeTagsDAO;
    }

    public function setNodeMetaDAO(NodeMetaDAO $NodeMetaDAO)
    {
        $this->NodeMetaDAO = $NodeMetaDAO;
    }

    public function __construct()
    {

    }

//    public function setNodeSectionsDAO(NodeSectionsDAO $NodeSectionsDAO)
//    {
//        $this->NodeSectionsDAO = $NodeSectionsDAO;
//    }

    public function multiGet($noderefs, NodePartials $nodePartials, $forceReadWrite = false, $checkJumpPermissions = false, $allowDeleted = false)
    {
//        foreach((array)$noderefs as $nodeRef)
//            $this->NodeEvents->fireNodeEvents('get', '', &$nodeRef);
        if(empty($noderefs))
            return array();

        $results = array();
        $idField = 'ID';
        $connectionCouplets = $this->getResolvedConnectionCouplets(is_array($noderefs)?$noderefs:array($noderefs), $forceReadWrite);

        foreach($connectionCouplets as $connectionCouplet)
        {
            $db = $connectionCouplet->getConnection();
            $tableToSlugs = $connectionCouplet->getAttribute('tablesToSlugs');

            foreach($tableToSlugs as $table => $tableInfo)
            {
                extract($tableInfo);

                if($checkJumpPermissions && !$this->NodePermissions->check('get', $tableNodeRef, $nodePartials, true))
                    continue;

                foreach(array_chunk($slugs, 1000) as $slugs) {

                    $rows = $this->multiGetFromDB($db, $tableid, $table, $tableNodeRef, $slugs, false, $forceReadWrite, $allowDeleted);

                    if(!empty($rows))
                    {


                        $ids = ArrayUtils::arrayMultiColumn($rows, $idField);

                        $outTags = $this->NodeTagsDAO->findOutTags($db, $tableNodeRef, $ids, $nodePartials->getOutPartials(), $forceReadWrite, $checkJumpPermissions, $nodePartials->getRestrictedOutPartials(), $nodePartials->isResolveLinks());
                        $inTags = $this->NodeTagsDAO->findInTags($db, $tableNodeRef, $ids, $nodePartials->getInPartials(), $forceReadWrite, $checkJumpPermissions, $nodePartials->getRestrictedInPartials(), $nodePartials->isResolveLinks());
                        $meta = $this->NodeMetaDAO->findMeta($db, $tableNodeRef, $ids, $nodePartials->getMetaPartials(), $forceReadWrite, $nodePartials->getRestrictedMetaPartials());

                        foreach($rows as $nodeRefString => $row)
                        {
                            $row->setNodePartials($nodePartials);
                            if(isset($meta[$row[$idField]]))
                                $row->setMetas($meta[$row[$idField]]);
                            if(isset($outTags[$row[$idField]]))
                                $row->setOutTags($outTags[$row[$idField]]);
                            if(isset($inTags[$row[$idField]]))
                                $row->setInTags($inTags[$row[$idField]]);

                            //$this->NodeMapper->populateNodeCheaters($row);
                            $results[$nodeRefString] = $row;
                        }

                        unset($ids);
                        unset($outTags);
                        unset($inTags);
                        unset($meta);
                    }
                    unset($rows);
                }
                unset($slugs);

            }

            unset($tableToSlugs);

        }

        unset($connectionCouplets);

        if(!empty($results) && !is_array($noderefs))
            return current($results);

        return $results;
    }

}
