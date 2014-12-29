<?php
/**
 * NodeFindAllDAO
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
 * @version     $Id: NodeFindAllDAO.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * A simple findAll query object that queries 1 datasource and 1 element.
 *
 * @package     CrowdFusion
 */
class NodeFindAllDAO extends AbstractNodeDAO
{

    protected $nodeDatabaseBatchLimit = 100;

    protected $NodeRefService;
    protected $NodeMultiGetDAO;
    protected $NodeMetaDAO;
    protected $NodeTagsDAO;
    protected $TagsHelper;
    protected $Events;

    public function setNodeRefService($NodeRefService)
    {
        $this->NodeRefService = $NodeRefService;
    }

    public function setNodeMultiGetDAO($NodeMultiGetDAO)
    {
        $this->NodeMultiGetDAO = $NodeMultiGetDAO;
    }

    public function setNodeMetaDAO($NodeMetaDAO)
    {
        $this->NodeMetaDAO = $NodeMetaDAO;
    }

    public function setNodeTagsDAO($NodeTagsDAO)
    {
        $this->NodeTagsDAO = $NodeTagsDAO;
    }

//    public function setNodeSectionsDAO($NodeSectionsDAO)
//    {
//        $this->NodeSectionsDAO = $NodeSectionsDAO;
//    }

    public function setNodeDatabaseBatchLimit($nodeDatabaseBatchLimit)
    {
        $this->nodeDatabaseBatchLimit = $nodeDatabaseBatchLimit;
    }

    public function setTagsHelper(TagsHelper $TagsHelper)
    {
        $this->TagsHelper = $TagsHelper;
    }

    public function setEvents(Events $Events)
    {
        $this->Events = $Events;
    }

    protected $NodesHelper;

    public function setNodesHelper(NodesHelper $NodesHelper)
    {
        $this->NodesHelper = $NodesHelper;
    }

    public function __construct()
    {

    }

    /**
     * @throws NodeException
     * @param NodeQuery $nodeQuery
     * @param bool $forceReadWrite
     * @return NodeQuery
     */
    public function findAll(NodeQuery $nodeQuery, $forceReadWrite = false)
    {

        $this->Benchmark->start('findall');

//        $this->Logger->debug($nodeQuery);

        $this->Events->trigger('Node.findAll', $nodeQuery);
        if($nodeQuery->getResults() !== null)
            return $nodeQuery;

        // NODEREFS
        //list($nodeRefs, $nodePartials, $allFullyQualified) = $this->NodeRefService->parseFromNodeQuery($nodeQuery);
        $this->NodeRefService->normalizeNodeQuery($nodeQuery);

        $nodeRefs = $nodeQuery->getParameter('NodeRefs.normalized');
        $nodePartials = $nodeQuery->getParameter('NodePartials.eq');
        $allFullyQualified = $nodeQuery->getParameter('NodeRefs.fullyQualified');

        if(empty($nodeRefs))
            return $nodeQuery;

//        foreach((array)$nodeRefs as $k => $nodeRef)
//            $this->NodeEvents->fireNodeEvents('find', '', $nodeRef, $nodePartials, $nodeQuery);

        $orderObjects = $this->NodesHelper->getOrderObjects($nodeQuery);

//        $this->currentOrderingObjects = $orderObjects;


        $co = false;
        if($nodeQuery->hasParameter('Count.only') && ($co = $nodeQuery->getParameter('Count.only')) != true)
            throw new NodeException('Count.only parameter must be equal to true');

        $checkPermissions = ($nodeQuery->hasParameter('Permissions.check') && $nodeQuery->getParameter('Permissions.check') == true);

        $doCounts = $nodeQuery->isRetrieveTotalRecords() || $co;

        $offset = $nodeQuery->getOffset()!=null?$nodeQuery->getOffset():0;
        $limit = $nodeQuery->getLimit();

        $allowDeleted = ($nodeQuery->getParameter('Status.all') != null || $nodeQuery->getParameter('Status.eq') == 'deleted');

        if($allFullyQualified)
        {
            $this->Logger->debug('RETRIEVING, SORTING, AND FILTERING IN PHP');

            // TODO: limit this to 50 nodes, PHP can't handle more

            $existsNodePartials = $this->NodesHelper->createNodePartialsFromExistsClauses($nodeQuery);

            // RETRIEVAL PHASE
            $resultingRows = $this->NodeMultiGetDAO->multiGet($nodeRefs, $existsNodePartials, $forceReadWrite, $checkPermissions, $allowDeleted);


            // FILTER PHASE
            $resultingRows = $this->NodesHelper->filterNodes($resultingRows, $nodeQuery);

            if($doCounts)
                $totalCount = count($resultingRows);

            if(!empty($resultingRows))
            {

                // SORTING PHASE
                $resultingRows = $this->NodesHelper->sortNodes($resultingRows, $orderObjects, true);

                // LIMIT/OFFSET PHASE
                $resultingRows = $this->NodesHelper->sliceNodes($resultingRows, $limit, $offset);

            }



        } else {

            $this->Logger->debug('RETRIEVING, SORTING, AND FILTERING IN MySQL');

            $moreThan1Table = false;

            $connectionCouplets = $this->getResolvedConnectionCouplets($nodeRefs, $forceReadWrite);

            $resultingRows = array();

            $queries = array();
            $elements = array();

            $c = 0;
            $totalCount = 0;

            //$query = null;
            //$firstTable = null;
            //$firstTableID = null;

            foreach($connectionCouplets as $connectionCouplet)
            {
                $db = $connectionCouplet->getConnection();
                $tableToSlugs = $connectionCouplet->getAttribute('tablesToSlugs');

                //if(is_null($limit) && count($tableToSlugs) > 1)
                //    throw new Exception('Cannot have limitless query across multiple tables');

                foreach($tableToSlugs as $table => $tableInfo)
                {
                    extract($tableInfo);

                    //if(!$query)
                        $query = $this->buildMySQLQuery($db, $tableNodeRef, $table, $tableid, $nodeQuery, $orderObjects, $slugs);

                    //if(!$firstTable)
                    //    $firstTable = $table;

                    //if(!$firstTableID)
                    //    $firstTableID = $tableid;

                    $elementid = $element->getElementID();

                    $elements[$elementid] = $element;

                    $queries[] = array(
                        'query' => $query,
                        'elementid' => $elementid,
                        'tableid' => $tableid,
                        'table' => $table,
                        'db' => $db);

                    $tableInfo=null;
                }

                $tableToSlugs=null;
                $connectionCouplet=null;
            }
            $connectionCouplets=null;

            $moreThan1Table = count($queries) > 1;
            $qcount = 1;

            foreach($queries as $qArgs)
            {
                extract($qArgs);

                $q = clone $query;

                if($doCounts)
                {
                    $cq = clone $q;
                    $cq->clearSelect();
                    $cq->ORDERBY(null);
                    //$cq->select("COUNT({$db->quoteIdentifier($firstTable)}.$firstTableID)");
                    $cq->select("COUNT(DISTINCT {$db->quoteIdentifier($table)}.$tableid)");

                    $s = (string)$cq;
                    //$s = str_replace($firstTable, $table, $s);
                    //$s = str_replace($firstTableID, $tableid, $s);

                    $totalCount += (int)$db->readField($s);

                    if($co) // Counts.only
                        continue;
                }


                $batchOffset = $moreThan1Table?0:$offset;
                $batchLimit = $moreThan1Table?$this->nodeDatabaseBatchLimit:$limit;

                while($batchOffset > -1) {
                    $rows=null;

                    $reorderOnce = false;

                    $q->LIMIT(($moreThan1Table && $batchLimit>1)?$batchLimit+1:$batchLimit);
                    $q->OFFSET($batchOffset);

                    $s = (string)$q;
                    //$s = str_replace($firstTable, $table, $s);
                    //$s = str_replace($firstTableID, $tableid, $s);

                    $rows = $db->readAll($s);

                    if(empty($rows)){
                        $batchOffset = -1;
                        break;
                    }

//                    $this->Benchmark->start('pushrows');

                    foreach($rows as $k => $row)
                    {
                        if($batchLimit > 1 && $k > $batchLimit-1)
                            break;

                        $row['ElementID'] = $elementid;

                        // if there is no limit, push all rows
                        // if there is only 1 table to aggregate, push all rows
                        // if the index of the current result set is less than the total needed rows, push
                        if(is_null($limit) || !$moreThan1Table || $c < ($limit+$offset)) {
                            $row['NodeRef'] = new NodeRef($elements[$row['ElementID']], $row['Slug']);
                            $resultingRows[] = $row;
                            ++$c;
                        } else {

                            if($k >= ($limit+$offset)) {
                                // done with this table (element)
                                $batchOffset = -1;
                                break 2;
                            }

                            if(!$reorderOnce) {
                                $reorderOnce = true;
                                $resultingRows = $this->NodesHelper->sortNodes($resultingRows, $orderObjects);
                            }


                            $lastRow = $resultingRows[$c-1];

                            // if this row is before the last row in the order, add it in
                            if(!$this->NodesHelper->compareNodes($lastRow, $row, $orderObjects))
                            {
                                $row['NodeRef'] = new NodeRef($elements[$row['ElementID']], $row['Slug']);
                                $resultingRows[] = $row;
                                //++$c;
                                $lastRow=null;

                            // else break, we're done with this table (element)
                            } else {
                                //        error_log('stopped at last: '.$lastRow['Slug'].' '.$lastRow['ActiveDate']);
                                //        error_log('stopped at: '.$row['Slug'].' '.$row['ActiveDate']);

                                $batchOffset = -1;
                                $lastRow=null;
                                break 2;
                            }

                        }
                    }

//                    $this->Benchmark->end('pushrows');

                    if((!$moreThan1Table && $offset == 0) || count($rows) < $batchLimit+1)
                        $batchOffset = -1;
                    else
                        $batchOffset = $batchOffset+$batchLimit;
                }

                $rows=null;

                if($qcount > 1 && $offset > 0 && $moreThan1Table && !empty($resultingRows)) {
//                    error_log('resorting');
//                    error_log('needed total: '.($limit+$offset));

                    $resultingRows = $this->NodesHelper->sortNodes($resultingRows, $orderObjects);
                    $resultingRows = $this->NodesHelper->sliceNodes($resultingRows, ($limit+$offset), 0);

//                    $resultingRows = array_slice($resultingRows, 0, ($limit+$offset));
                    $c = count($resultingRows);

                }

                ++$qcount;
                $qArgs=null;
            }
            $queries=null;



            if(!empty($resultingRows))
            {
                if( $offset == 0 && $moreThan1Table )
                    $resultingRows = $this->NodesHelper->sortNodes($resultingRows, $orderObjects);

                $resultingRows = $this->NodesHelper->sliceNodes($resultingRows, $limit, $offset, ($moreThan1Table && $offset > 0));
            }

        }

        if(!empty($resultingRows))
        {
            $resultingNodeRefs = ArrayUtils::arrayMultiColumn($resultingRows, 'NodeRef');

            if($nodeQuery->hasParameter('NodeRefs.only') && StringUtils::strToBool($nodeQuery->getParameter('NodeRefs.only')) == true)
            {

                $nodeQuery->setResults($resultingNodeRefs);

            } else {

                $results = $this->NodeMultiGetDAO->multiGet($resultingNodeRefs, $nodePartials, $forceReadWrite, $checkPermissions, true);
                $keys = array_map('strval', $resultingNodeRefs);

                $results = ArrayUtils::arraySortUsingKeys($results, $keys);
                $keys=null;
                $resultingNodeRefs=null;

                $nodeQuery->setResults($results);
            }

        }

        if($doCounts)
        {
            $nodeQuery->setTotalRecords($totalCount);
        }

        $this->Benchmark->end('findall');

        return $nodeQuery;

    }




    public function buildMySQLQuery(DatabaseInterface $db, $tableNodeRef, $table, $tableid, NodeQuery $nodeQuery, $orderObjects, $slugs, $ids = null)
    {
        $table = $db->quoteIdentifier($table);
        $element = $tableNodeRef->getElement();
        $schema = $element->getSchema();

        $q = new Query();

        $q->SELECT('DISTINCT '.$table.'.'.$tableid.' as ID', true);
        $q->SELECT($table.'.Slug');
        $mTableC = 1;

        if($nodeQuery->hasParameter('OrderByInTag'))
        {
            $inParts = explode(' ',$nodeQuery->getParameter('OrderByInTag'),2);
            $partials = PartialUtils::unserializeInPartials($inParts[0]);
            foreach($partials as $partial)
            {
                if($partial->getTagElement() && $partial->getTagSlug() && $partial->getTagRole())
                {
                    $tTable = $db->quoteIdentifier($this->NodeDBMeta->getInTagsTable($tableNodeRef));
                    $tClause = $this->NodeTagsDAO->getOutTagPartialClause($partial, $db, $tTable);

                    $q->SELECT("OCondTable".$mTableC.".SortOrder as OSortOrder{$mTableC}");
                    $q->JOIN("INNER JOIN {$tTable} as OCondTable{$mTableC} ".
                            str_replace($tTable,'OCondTable'.$mTableC," ON {$table}.{$tableid} = {$tTable}.{$tableid} AND {$tClause}"));
                    $direction = array_key_exists(1,$inParts) ? $inParts[1] : 'ASC';
                    $q->ORDERBY('OCondTable'.$mTableC.'.SortOrder '.$direction);
                    $mTableC++;
                } else {
                    throw new NodeException('Invalid OrderByInTag parameter, must be fully qualified TagPartial with element:slug#role');
                }
            }
        }

        if($nodeQuery->hasParameter('OrderByOutTag'))
        {
            $outParts = explode(' ',$nodeQuery->getParameter('OrderByOutTag'),2);
            $partials  = PartialUtils::unserializeOutPartials($outParts[0]);
            foreach($partials as $partial)
            {
                if($partial->getTagElement() && $partial->getTagSlug() && $partial->getTagRole())
                {
                    $tTable = $db->quoteIdentifier($this->NodeDBMeta->getOutTagsTable($tableNodeRef));
                    $tClause = $this->NodeTagsDAO->getOutTagPartialClause($partial, $db, $tTable);

                    $q->SELECT("OCondTable".$mTableC.".SortOrder as OSortOrder{$mTableC}");
                    $q->JOIN("INNER JOIN {$tTable} as OCondTable{$mTableC} ".
                            str_replace($tTable,'OCondTable'.$mTableC," ON {$table}.{$tableid} = {$tTable}.{$tableid} AND {$tClause}"));
                    $direction = array_key_exists(1,$outParts) ? $outParts[1] : 'ASC';
                    $q->ORDERBY('OCondTable'.$mTableC.'.SortOrder '.$direction);
                    $mTableC++;
                }
                else {
                    throw new NodeException('Invalid OrderByOutTag parameter, must be fully qualified TagPartial with element:slug#role');
                }
            }
        }

        $mTableC = 1;
        foreach($orderObjects as $orderObject)
        {
            $column = $orderObject->getColumn();
            $direction = $orderObject->getDirection();
            //if(!in_array($column, array('ID', 'Slug')))
            //{
                if($orderObject->isMeta())
                {
                    $partial = $orderObject->getOrderByMetaPartial();
                    $datatype = $orderObject->getOrderByMetaDataType();

                    $mTable = $this->NodeDBMeta->getMetaTable($tableNodeRef, $datatype);
                    $mTableAlias = $db->quoteIdentifier($mTable.$mTableC++);
                    $mTable = $db->quoteIdentifier($mTable);

                    $mClause = $this->NodeMetaDAO->getMetaPartialClause(new MetaPartial($partial), $datatype, $db, $mTableAlias);

                    $q->SELECT($mTableAlias.'.'.$datatype.'Value as '.$column);
                    $q->JOIN('LEFT JOIN '.$mTable.' as '.$mTableAlias. ' ON '.$table.'.'.$tableid.' = '.$mTableAlias.'.'.$tableid.' AND '.$mClause);
                    $q->ORDERBY("$column $direction");
                } else if($orderObject->isDirectional()) {
                    if(!in_array($orderObject->getColumn(), array('Title', 'Slug', 'SortOrder', 'ActiveDate', 'CreationDate', 'ModifiedDate', 'TreeID')))
                        throw new NodeException('Invalid ordering column ['.$orderObject->getColumn().'], must be Title, Slug, SortOrder, ActiveDate, CreationDate, ModifiedDate, TreeID, or #meta-id');

                    if($orderObject->getColumn() != 'Slug')
                        $q->SELECT($table.'.'.$orderObject->getColumn());
                    $q->ORDERBY("{$table}.{$column} $direction");
                }

            //}

        }
        // Add if clause 2/1/2010 by Craig .. Mysql seems to be doing it's job without this secondary sort.
        if(empty($orderObjects))
	        $q->ORDERBY('ID DESC');
        $q->FROM($table);


        // ID
        if(!empty($ids))
        {
            if (count($ids) > 1)
                $q->WHERE("{$table}.{$tableid} IN (".$db->joinQuote($ids).")");
            else {
                if(isset($ids[0]))
                    $q->WHERE("{$table}.{$tableid} = {$db->quote($ids[0])}");
            }
        }

        // Slug
        if(!empty($slugs))
        {
            if (count($slugs) > 1)
                $q->WHERE("{$table}.Slug IN (".$db->joinQuote($slugs).")");
            else {
                if(isset($slugs[0]))
                    $q->WHERE("{$table}.Slug = {$db->quote($slugs[0])}");
            }
        }

        // AlphaIndex
        if (($alpha = $nodeQuery->getParameter('Title.firstChar')) != null) {
            if(strtolower($alpha) == '#')
            {
                $q->WHERE("(ASCII(LOWER(LEFT({$table}.Title, 1))) < 65) OR (ASCII(LOWER(LEFT({$table}.Title, 1))) BETWEEN 90 AND 97) OR (ASCII(LOWER(LEFT({$table}.Title, 1))) > 122)");
            } else {
                $q->WHERE("LOWER(LEFT({$table}.Title, 1)) = {$db->quote(strtolower($alpha))}");
            }
        }

        // Title
        $this->DTOHelper->buildEqualsFilter($db, $q, $nodeQuery, 'Title.ieq', "{$table}.Title");
        $this->DTOHelper->buildReplaceFilter($db, $q, $nodeQuery, 'Title.eq', "BINARY {$table}.Title = ?");

        // TitleSearch
        $this->DTOHelper->buildReplaceFilter($db, $q, $nodeQuery, 'Title.like', "{$table}.Title LIKE ?", '%#s%');

        // ParentTreeID
        $this->DTOHelper->buildReplaceFilter($db, $q, $nodeQuery, 'TreeID.childOf', "{$table}.TreeID LIKE ?", "#s%");

        // TreeID
        $this->DTOHelper->buildEqualsFilter($db, $q, $nodeQuery, 'TreeID.eq', "{$table}.TreeID");

        // select node by Tree depth (or limit existing selection)
        if(($treeDepth = $nodeQuery->getParameter('TreeID.depth')) !== null) {
            $actualDepth = $treeDepth * 4;
            $q->orWhere("LENGTH({$table}.TreeID) = {$db->quote($actualDepth)}");
        }

        if(($treeid = $nodeQuery->getParameter('TreeID.parentOf')) != null)
        {
            $depth = strlen($treeid) / 4;
            if($depth == 1)
                $q->WHERE('1 = 0');
            else
                for($i = 1; $i < $depth; ++$i) {
                    $ptreeid = substr($treeid, 0, $i*4);
                    $q->ORWHERE("{$table}.TreeID = {$db->quote($ptreeid)}");
                }
        }


        // ActiveAfter
        // ActiveBefore
        $this->DTOHelper->buildDateReplaceFilter($db, $q, $nodeQuery, 'ActiveDate.after', "{$table}.ActiveDate > ?");
        $this->DTOHelper->buildDateReplaceFilter($db, $q, $nodeQuery, 'ActiveDate.before', "{$table}.ActiveDate <= ?");
        $this->DTOHelper->buildDateReplaceFilter($db, $q, $nodeQuery, 'ActiveDate.start', "{$table}.ActiveDate >= ?", 0,0,0);
        $this->DTOHelper->buildDateReplaceFilter($db, $q, $nodeQuery, 'ActiveDate.end', "{$table}.ActiveDate <= ?", 23,59,59);

        // CreatedAfter
        // CreatedBefore
        $this->DTOHelper->buildDateReplaceFilter($db, $q, $nodeQuery, 'CreationDate.after', "{$table}.CreationDate > ?");
        $this->DTOHelper->buildDateReplaceFilter($db, $q, $nodeQuery, 'CreationDate.before', "{$table}.CreationDate <= ?");
        $this->DTOHelper->buildDateReplaceFilter($db, $q, $nodeQuery, 'CreationDate.start', "{$table}.CreationDate >= ?", 0,0,0);
        $this->DTOHelper->buildDateReplaceFilter($db, $q, $nodeQuery, 'CreationDate.end', "{$table}.CreationDate <= ?", 23,59,59);

        // Status
        if (($status = $nodeQuery->getParameter('Status.eq')) != null) {
            switch($status) {
                case 'published':
                    $q->WHERE("{$table}.Status = 'published'");
                    break;
                case 'draft':
                    $q->WHERE("{$table}.Status = 'draft'");
                    break;
                case 'deleted':
                    $q->WHERE("{$table}.Status = 'deleted'");
                    break;
                default:
                    $q->WHERE("{$table}.Status != 'deleted'");
                    break;
            }
        }else if($nodeQuery->getParameter('Status.isActive') !== null && StringUtils::strToBool($nodeQuery->getParameter('Status.isActive')) == true) {
            $now = $this->DateFactory->newStorageDate();
            $q->WHERE("{$table}.Status = 'published' AND {$table}.ActiveDate < {$db->quote($now)}");
        }else if($nodeQuery->getParameter('Status.all') == null || StringUtils::strToBool($nodeQuery->getParameter('Status.all')) == false) {
            $q->WHERE("{$table}.Status != 'deleted'");
        }


        $metaParams = $this->NodesHelper->getMetaFilters($nodeQuery);
        $tablect = 0;
        foreach($metaParams as $mArgs)
        {
            list($full, $name, $operator, $value) = $mArgs;
            $def = $schema->getMetaDef($name);
            $datatype = $def->Datatype;

            $mTable = $db->quoteIdentifier($this->NodeDBMeta->getMetaTable($tableNodeRef, $datatype));
            $clause = ' '.$db->quoteIdentifier($mTable).'.Name = '.$db->quote($name)." AND ";

            if( $datatype == 'flag')
                throw new NodeException('Unable to run meta clause on flag datatype');

            if(in_array($datatype, array('text', 'blob', 'mediumtext', 'mediumblob')))
                throw new NodeException('Query arguments with #'.$name.' are not supported');

            switch($operator)
            {
                case 'eq':
                    if($datatype == 'varchar')
                        $clause .= " BINARY {$db->quoteIdentifier($mTable)}.{$datatype}Value = {$db->quote($value)}";
                    else
                        $clause .= " {$db->quoteIdentifier($mTable)}.{$datatype}Value = {$db->quote($value)}";
                    break;

                case 'ieq':
                    $clause .= " {$db->quoteIdentifier($mTable)}.{$datatype}Value = {$db->quote($value)}";
                    break;

                case 'like':
                    $clause .= " {$db->quoteIdentifier($mTable)}.{$datatype}Value LIKE ". $db->quote('%' . $value . '%');
                    break;

                case 'before':
                    $d = $this->DateFactory->newLocalDate($value);
                    $clause .= " {$db->quoteIdentifier($mTable)}.{$datatype}Value <= {$db->quote($d)}";
                    break;

                case 'after':
                    $d = $this->DateFactory->newLocalDate($value);
                    $clause .= " {$db->quoteIdentifier($mTable)}.{$datatype}Value > {$db->quote($d)}";
                    break;

                case 'start':
                    $d = $this->DateFactory->newLocalDate($value);
                    $d->setTime(0, 0, 0);
                    $clause .= " {$db->quoteIdentifier($mTable)}.{$datatype}Value >= {$db->quote($d)}";
                    break;

                case 'end':
                    $d = $this->DateFactory->newLocalDate($value);
                    $d->setTime(23, 59, 59);
                    $clause .= " {$db->quoteIdentifier($mTable)}.{$datatype}Value <= {$db->quote($d)}";
                    break;

                case 'notEq':
                    if($datatype == 'varchar')
                        $clause .= " BINARY {$db->quoteIdentifier($mTable)}.{$datatype}Value != {$db->quote($value)}";
                    else
                        $clause .= " {$db->quoteIdentifier($mTable)}.{$datatype}Value != {$db->quote($value)}";
                    break;

                case 'lessThan':
                    $clause .= " {$db->quoteIdentifier($mTable)}.{$datatype}Value < {$db->quote($value)}";
                    break;

                case 'lessThanEq':
                    $clause .= " {$db->quoteIdentifier($mTable)}.{$datatype}Value <= {$db->quote($value)}";
                    break;

                case 'greaterThan':
                    $clause .= " {$db->quoteIdentifier($mTable)}.{$datatype}Value > {$db->quote($value)}";
                    break;

                case 'greaterThanEq':
                    $clause .= " {$db->quoteIdentifier($mTable)}.{$datatype}Value >= {$db->quote($value)}";
                    break;

                /*
                 * case insensitive comparison for #meta.in filtering.
                 */
                case 'in':
                    $inValues = explode(',', $value);
                    if (count($inValues) > 1) {
                        $clause .= " {$db->quoteIdentifier($mTable)}.{$datatype}Value IN ({$db->joinQuote($inValues)})";
                    } else {
                        if ($datatype == 'varchar') {
                            $clause .= " BINARY {$db->quoteIdentifier($mTable)}.{$datatype}Value = {$db->quote($value)}";
                        } else {
                            $clause .= " {$db->quoteIdentifier($mTable)}.{$datatype}Value = {$db->quote($value)}";
                        }
                    }
                    break;
            }

            $tablect++;
            $q->JOIN("INNER JOIN {$mTable} as CondTable{$tablect} ".
                    str_replace($mTable,'CondTable'.$tablect," ON {$table}.{$tableid} = {$mTable}.{$tableid} AND {$clause}"));

        }



        // IncludesMeta
//        if(($im = $nodeQuery->getParameter('Meta.exist')) != NULL ) {
//            $metas       = PartialUtils::unserializeMetaPartials($im);
//            $conditions = array();
//            foreach($metas as $partial) {
//                $s = $schema->getMetaDef($partial->getMetaName());
//
//                $datatype = $s->Datatype;
//
//                $mTable = $db->quoteIdentifier($this->NodeDBMeta->getMetaTable($tableNodeRef, $datatype));
//                $mClause = $this->NodeMetaDAO->getMetaPartialClause($partial, $datatype, $db, $mTable);
//
//                $conditions[] = "{$table}.{$tableid} IN
//                    (SELECT {$mTable}.{$tableid}
//                    FROM {$mTable}
//                    WHERE ".$mClause.")";
////                  $conditions[] = "EXISTS (SELECT 1 FROM {$this->model->getMetaTable()}
////                      WHERE {$this->model->getMetaTable()}.{$this->model->getTableID()} = {$table}.{$this->model->getTableID()}
////                      AND ".$partial->getTagClause($this->model->getMetaTable()).")";
//            }
//            if (!empty($conditions)) {
//                $q->WHERE(join(' OR ', $conditions));
//            }
//
//        }

        // IncludesAllMeta
        if(($iam = $nodeQuery->getParameter('Meta.exist')) != NULL ) {
            $metas       = PartialUtils::unserializeMetaPartials($iam);
            foreach($metas as $partial) {
                $s = $schema->getMetaDef($partial->getMetaName());

                $datatype = $s->Datatype;

                $mTable = $db->quoteIdentifier($this->NodeDBMeta->getMetaTable($tableNodeRef, $datatype));
                $mClause = $this->NodeMetaDAO->getMetaPartialClause($partial, $datatype, $db, $mTable);

                $tablect++;
                $q->JOIN("INNER JOIN {$mTable} as CondTable{$tablect} ".
                        str_replace($mTable,'CondTable'.$tablect," ON {$table}.{$tableid} = {$mTable}.{$tableid} AND {$mClause}"));

//                $q->WHERE("{$table}.{$tableid} IN
//                    (SELECT {$mTable}.{$tableid}
//                    FROM {$mTable}
//                    WHERE ".$mClause.")");
//                  $this->db->WHERE("EXISTS (SELECT 1 FROM {$this->model->getMetaTable()}
//                      WHERE {$this->model->getMetaTable()}.{$this->model->getTableID()} = {$table}.{$this->model->getTableID()}
//                      AND ".$partial->getTagClause($this->model->getMetaTable()).")");
            }
        }

        // IncludesOutTags
//        if(($iot = $nodeQuery->getParameter('IncludesOutTags')) != NULL ) {
//            $tags       = PartialUtils::unserializeOutPartials($iot);
//            $conditions = array();
//            foreach($tags as $partial) {
//                $tTable = $db->quoteIdentifier($this->NodeDBMeta->getOutTagsTable($tableNodeRef));
//
//                // TODO: need to support aspects
//                // TODO: convert OR clauses into multiple unions
//
//                $tClause = $this->NodeTagsDAO->getOutTagPartialClause($partial, $tableNodeRef->getSite(), $db, $tTable);
//
//                $conditions[] = "{$table}.{$tableid} IN
//                    (SELECT {$tTable}.{$tableid}
//                    FROM {$tTable}
//                    WHERE {$tClause})";
////                  $conditions[] = "EXISTS (SELECT 1 FROM {$this->model->getTagsTable()} t2
////                      INNER JOIN tags2 ON t2.tagid = tags2.tagid
////                      WHERE {$table}.{$this->model->getTableID()} = t2.{$this->model->getTableID()} AND
////                      ".$partial->getTagClause("tags2").")";
//            }
//            if (!empty($conditions)) {
//                $q->WHERE(join(' OR ', $conditions));
//            }
//
//        }

        // IncludesAllOutTags
        if( ($iaot = $nodeQuery->getParameter('OutTags.exist')) != NULL ) {
            $tags       = PartialUtils::unserializeOutPartials($iaot);
            foreach($tags as $partial) {
                $tTable = $db->quoteIdentifier($this->NodeDBMeta->getOutTagsTable($tableNodeRef));
                $tClause = $this->NodeTagsDAO->getOutTagPartialClause($partial, $db, $tTable);

                $tablect++;
                $q->JOIN("INNER JOIN {$tTable} as CondTable{$tablect} ".
                        str_replace($tTable,'CondTable'.$tablect," ON {$table}.{$tableid} = {$tTable}.{$tableid} AND {$tClause}"));


//                $q->WHERE( "{$table}.{$tableid} IN
//                    (SELECT {$tTable}.{$tableid}
//                    FROM {$tTable}
//                    WHERE {$tClause})" );

//                  $this->db->WHERE("EXISTS (SELECT 1 FROM {$this->model->getTagsTable()} t2
//                      INNER JOIN tags2 ON t2.tagid = tags2.tagid
//                      WHERE {$table}.{$this->model->getTableID()} = t2.{$this->model->getTableID()} AND
//                      ".$partial->getTagClause("tags2").")");
            }

        }

        // IncludesInTags
//        if(($iit = $nodeQuery->getParameter('IncludesInTags')) != NULL ) {
//            $tags       = PartialUtils::unserializeInPartials($iit);
//            $conditions = array();
//            foreach($tags as $partial) {
//                $tTable = $db->quoteIdentifier($this->NodeDBMeta->getInTagsTable($tableNodeRef));
//                $tClause = $this->NodeTagsDAO->getInTagPartialClause($partial, $tableNodeRef->getElement(), $tableNodeRef->getSite(), $db, $tTable);
//
//                $conditions[] = "{$table}.{$tableid} IN
//                    (SELECT {$tTable}.{$tableid}
//                    FROM {$tTable}
//                    WHERE {$tClause})";
////                  $conditions[] = "EXISTS (SELECT 1 FROM {$this->model->getTagsTable()} t2
////                      INNER JOIN tags2 ON t2.tagid = tags2.tagid
////                      WHERE {$table}.{$this->model->getTableID()} = t2.{$this->model->getTableID()} AND
////                      ".$partial->getTagClause("tags2").")";
//            }
//            if (!empty($conditions)) {
//                $q->WHERE(join(' OR ', $conditions));
//            }
//
//        }

        // IncludesAllInTags
        if( ($iait = $nodeQuery->getParameter('InTags.exist')) != NULL ) {
            $tags       = PartialUtils::unserializeInPartials($iait);
            foreach($tags as $partial) {
                $tTable = $db->quoteIdentifier($this->NodeDBMeta->getInTagsTable($tableNodeRef));
                $tClause = $this->NodeTagsDAO->getInTagPartialClause($partial, $tableNodeRef->getElement(), $db, $tTable);

                $tablect++;
                $q->JOIN("INNER JOIN {$tTable} as CondTable{$tablect} ".
                        str_replace($tTable,'CondTable'.$tablect," ON {$table}.{$tableid} = {$tTable}.{$tableid} AND {$tClause}"));

//
//                $q->WHERE( "{$table}.{$tableid} IN
//                    (SELECT {$tTable}.{$tableid}
//                    FROM {$tTable}
//                    WHERE {$tClause})" );

//                  $this->db->WHERE("EXISTS (SELECT 1 FROM {$this->model->getTagsTable()} t2
//                      INNER JOIN tags2 ON t2.tagid = tags2.tagid
//                      WHERE {$table}.{$this->model->getTableID()} = t2.{$this->model->getTableID()} AND
//                      ".$partial->getTagClause("tags2").")");
            }

        }


        return $q;
    }



}
