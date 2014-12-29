<?php
/**
 * AbstractNodeDAO
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
 * @version     $Id: AbstractNodeDAO.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AbstractNodeDAO
 *
 * @package     CrowdFusion
 */
abstract class AbstractNodeDAO extends AbstractDAO
{
    protected $Events;
    protected $Benchmark;
    protected $dsn;
    protected $NodeDBMeta;
    protected $NodeMapper;
    protected $NodePermissions;
    protected $NodeCache;
    protected $NodeEvents;

    public function setNodeDataSource(DataSourceInterface $NodeDataSource)
    {
        $this->dsn = $NodeDataSource;
    }

    public function setNodeDBMeta(NodeDBMeta $NodeDBMeta)
    {
        $this->NodeDBMeta = $NodeDBMeta;
    }
    public function setNodeMapper(NodeMapper $NodeMapper)
    {
        $this->NodeMapper = $NodeMapper;
    }

    public function setNodePermissions(NodePermissions $NodePermissions)
    {
        $this->NodePermissions = $NodePermissions;
    }

    public function setBenchmark(Benchmark $Benchmark)
    {
        $this->Benchmark = $Benchmark;
    }

    public function setNodeCache(NodeCacheInterface $NodeCache)
    {
        $this->NodeCache = $NodeCache;
    }

    public function setNodeEvents(NodeEvents $NodeEvents)
    {
        $this->NodeEvents = $NodeEvents;
    }

    public function __construct()
    {
        parent::__construct();
    }


    protected function multiGetFromDB($db, $tableid, $table, NodeRef $nodeRef, $slugs, $asObjects = false, $forceReadWrite = false, $allowDeleted = false)
    {
        $results = array();

        $cachedSlugs = array();
        $rows = array();

        list($cachedSlugs, $rows) = $this->NodeCache->getNodes($nodeRef, $slugs, $forceReadWrite);

        $remainingSlugs = array_diff((array)$slugs, $cachedSlugs);

        if(!empty($remainingSlugs))
        {
            $q = new Query();

            $q->SELECT($tableid);
            $q->SELECT($tableid.' as ID');
            $q->SELECT($this->NodeDBMeta->getSelectFields());
            $q->FROM($db->quoteIdentifier($table));
            $q->WHERE("Slug IN ({$db->joinQuote(array_unique((array)$slugs))})");

            $dbRows = $this->getConnectionForWrite($nodeRef)->readAll($q);
            $found = array();

            foreach($dbRows as $row)
            {
                if(in_array($row['Slug'], $found))
                {
                    $this->getConnectionForWrite($nodeRef)->deleteRecord($db->quoteIdentifier($table), "{$tableid} = {$db->quote($row['ID'])}");

                    $metaSchemas = $nodeRef->getElement()->getSchema()->getMetaStorageDatatypes();
                    foreach($metaSchemas as $datatype)
                    {
                        $mtable = $this->NodeDBMeta->getMetaTable($nodeRef, $datatype);
                        $this->getConnectionForWrite($nodeRef)->deleteRecord($db->quoteIdentifier($mtable), "{$tableid} = {$db->quote($row['ID'])}");
                    }

                    $otable = $this->NodeDBMeta->getOutTagsTable($nodeRef);
                    $this->getConnectionForWrite($nodeRef)->deleteRecord($db->quoteIdentifier($otable), "{$tableid} = {$db->quote($row['ID'])}");

                    $itable = $this->NodeDBMeta->getInTagsTable($nodeRef);
                    $this->getConnectionForWrite($nodeRef)->deleteRecord($db->quoteIdentifier($itable), "{$tableid} = {$db->quote($row['ID'])}");

                    continue;
                }

                $found[] = $row['Slug'];
                $this->NodeCache->putNode($nodeRef, $row, $forceReadWrite);
                $rows[] = $row;
            }
            unset($dbRows);

//            $unfounds = array_diff($remainingSlugs, $found);
//            foreach($unfounds as $unfoundSlug)
//            {
//                $this->NodeCache->putNode($nodeRef, array('Slug'=> $unfoundSlug, 'NotFound' => true), $forceReadWrite);
//            }

            unset($found);
//            unset($unfounds);
            unset($remainingSlugs);

        }

        foreach($rows as $row)
        {
            if(!empty($row['NotFound']))
                continue;
            if(!$allowDeleted && $row['Status'] == 'deleted')
                continue;

            $rowNodeRef = new NodeRef($nodeRef->getElement(), $row['Slug']);

            $row['NodeRef'] = $rowNodeRef;

            $results[(string)$rowNodeRef] = $this->NodeMapper->persistentArrayToNode($row);
            unset($rowNodeRef);
        }

        unset($rows);

        return $results;
    }

    protected function getRecordIDFromNodeRef(NodeRef $nodeRef)
    {
        if(!$nodeRef->isFullyQualified())
            throw new NodeException('Cannot retrieve record ID without fully-qualified NodeRef');

        $db = $this->getConnectionForWrite($nodeRef);
        $tableid = $this->NodeDBMeta->getPrimaryKey($nodeRef);

        $nodes = $this->multiGetFromDB($db, $tableid, $this->NodeDBMeta->getTableName($nodeRef), $nodeRef, (array)$nodeRef->getSlug(), false, true, true);

        if(empty($nodes))
            throw new NodeException('Node not found for NodeRef: '.$nodeRef);

        $node = current($nodes);

        return $node['ID'];
    }

    protected function getResolvedConnectionCouplets($noderefs, $forceReadWrite = false)
    {

        $connectionCouplets = $forceReadWrite?$this->dsn->getConnectionsForReadWrite($noderefs):$this->dsn->getConnectionsForRead($noderefs);

        foreach($connectionCouplets as &$connectionCouplet)
        {
            $myNodeRefs = $connectionCouplet->getHints();
            $db = $connectionCouplet->getConnection();

            $tableToSlugs = array();
            foreach($myNodeRefs as $myNodeRef)
            {
                // if(!$myNodeRef->isFullyQualified())
                    // throw new Exception('Fully qualified NodeRefs are required for multiGet');

                $table = $this->NodeDBMeta->getTableName($myNodeRef);
                if(empty($tableToSlugs[$table]['tableid']))
                {
                    $tableid = $this->NodeDBMeta->getPrimaryKey($myNodeRef);
                    $tableToSlugs[$table]['tableid'] = $tableid;
                    $tableToSlugs[$table]['element'] = $myNodeRef->getElement();
                    $tableToSlugs[$table]['tableNodeRef'] = $myNodeRef;
                    $tableToSlugs[$table]['slugs'] = array();
                }

                if($myNodeRef->isFullyQualified())
                    $tableToSlugs[$table]['slugs'][] = $myNodeRef->getSlug();

                $tableToSlugs[$table]['customNoderefs'][] = $myNodeRef;
            }

            $connectionCouplet->setAttribute('tablesToSlugs', $tableToSlugs);
        }

        return $connectionCouplets;
    }

    protected function getConnectionForWrite(NodeRef $nodeRef)
    {
        return $this->dsn->getConnectionsForReadWrite(array($nodeRef))->offsetGet(0)->getConnection();
    }

    protected function getConnectionForRead(NodeRef $nodeRef)
    {
        return $this->dsn->getConnectionsForRead(array($nodeRef))->offsetGet(0)->getConnection();
    }


}
