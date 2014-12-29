<?php
/**
 * NodeTagsDAO
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
 * @version     $Id: NodeTagsDAO.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeTagsDAO
 *
 * @package     CrowdFusion
 */
class NodeTagsDAO extends AbstractNodeDAO
{
    protected $nodeDatabaseTagMergeLimit = 50;

    protected $tagFields = array(
                    'TagID',
//                    'SectionID as TagSectionID',
                    'ElementID as TagElementID',
                    'Slug as TagSlug',

                    'Role as TagRole',
                    //'RoleDisplay as TagRoleDisplay',
                    'Value as TagValue',
                    'ValueDisplay as TagValueDisplay',

                    'SortOrder as TagSortOrder'
                );

    public function setElementService($ElementService)
    {
        $this->ElementService = $ElementService;
    }

    public function setNodeDatabaseTagMergeLimit($nodeDatabaseTagMergeLimit)
    {
        $this->nodeDatabaseTagMergeLimit = $nodeDatabaseTagMergeLimit;
    }

    public function setTagsHelper($TagsHelper)
    {
        $this->TagsHelper = $TagsHelper;
    }

    public function setNodeMetaDAO($NodeMetaDAO)
    {
        $this->NodeMetaDAO = $NodeMetaDAO;
    }

    public function __construct()
    {

    }

    public function findOutTags(/* DatabaseInterface */ $db, NodeRef $originNodeRef, $ids, $outPartials = 'fields', $forceReadWrite = false, $checkJumpPermissions = false, $restrictedPartials = '', $resolveLinkedRecords = true, $existingRows = null)
    {
        return $this->findTags('out', $db, $originNodeRef, $ids, $outPartials, $forceReadWrite, $checkJumpPermissions, $restrictedPartials, $resolveLinkedRecords, $existingRows);
    }

    public function findInTags(/* DatabaseInterface */ $db, NodeRef $originNodeRef, $ids, $outPartials = 'fields', $forceReadWrite = false, $checkJumpPermissions = false, $restrictedPartials = '', $resolveLinkedRecords = true, $existingRows = null)
    {
        return $this->findTags('in', $db, $originNodeRef, $ids, $outPartials, $forceReadWrite, $checkJumpPermissions, $restrictedPartials, $resolveLinkedRecords, $existingRows);
    }

    protected function findTags($direction, /* DatabaseInterface */ $db, NodeRef $originNodeRef, $ids, $partials = 'fields', $forceReadWrite = false, $checkJumpPermissions = false, $restrictedPartials, $resolveLinkedRecords = true, $existingRows = null) {
        if(!is_null($db) && !($db instanceof DatabaseInterface))
            throw new NodeException('Argument 2 passed to NodeTagsDAO::findTags() must implement interface DatabaseInterface');

        $results = array();

        if(empty($partials))
            return $results;

        $returnOne = false;

        if(!is_array($ids)){
            $returnOne = $ids;
            $ids = array( $originNodeRef->getRefURL() => $ids );
        }

        $fixedId = false;

        if(!is_null($existingRows)){
            $fixedId = 1;
            $returnOne = 1;
        }

        $tagFields = $this->tagFields;

        $this->Logger->debug('findTags ['.$direction.'] with partials ['.$partials.'] restrict ['.$restrictedPartials.']');

        if($direction == 'out') {
            $table = $this->NodeDBMeta->getOutTagsTable($originNodeRef);
            $partials = PartialUtils::unserializeOutPartials($partials);
            $restrictedPartials = PartialUtils::unserializeOutPartials($restrictedPartials);
        }else{
            $table = $this->NodeDBMeta->getInTagsTable($originNodeRef);
            $partials = PartialUtils::unserializeInPartials($partials);
            $restrictedPartials = PartialUtils::unserializeInPartials($restrictedPartials);
//            $tagFields[] = 'TagOutID';
        }

//        $this->Logger->debug($partials);
        $tableid = $this->NodeDBMeta->getPrimaryKey($originNodeRef);

        $now = $this->DateFactory->newLocalDate();

        $rows = array();
        $fieldlikePartials = array();
        $childexprPartials = array();
        $addlParams = array();
        $jumpParams = array();

        $fieldlikeRoles = array();
        $nonfieldlikeRoles = array();
        $outRoles = array();

        $all = false;
        $allfieldlike = false;

        $tagDefs = array();

        if($partials == 'all' || ($x = array_search('all', $partials)) !== false)
        {
            $all = true;
            $partials = array();
        } else if(($x = array_search('fields', $partials)) !== false)
        {
            $allfieldlike = true;
            unset($partials[$x]);
        }

        if($restrictedPartials == 'all' || ($x = array_search('all', $restrictedPartials)) !== false)
        {
            return array();
        }


        $tagDefs = $originNodeRef->getElement()->getSchema()->getTagDefs();
        foreach($originNodeRef->getElement()->getSchema()->getTagDefs() as $role => $tagDef)
        {
            if($tagDef->isFieldlike() && strtolower($tagDef->Direction) == $direction) {
                $fieldlikeRoles[] = $role;
            }
            if($tagDef->Direction == 'out')
                $outRoles[] = $role;
        }

        foreach($partials as $x => $outPartial)
        {
            if($outPartial['TagRole'] == false)
                throw new TagException('Tag partials must specify a role when retrieving tags');

            if(in_array($outPartial['TagRole'], $fieldlikeRoles))
            {
                $fieldlikePartials[] = $outPartial;
                unset($partials[$x]);
            }

            if($resolveLinkedRecords && $outPartial->hasChildPartials())
                $childexprPartials[$outPartial['TagRole']][] = $outPartial;

        }



        // tags passed to us, no need to query database
        if(is_null($existingRows))
        {

            if($allfieldlike == true || !empty($fieldlikePartials)) {

                $cachedIds = array();
                // read from cache
                list($cachedIds, $rows) = $this->NodeCache->getTags($direction, $ids, $forceReadWrite);

                $remainingIds = array_diff($ids, $cachedIds);

                if(!empty($remainingIds))
                {
                    $dbToCacheRows = array();

                    if(!empty($fieldlikeRoles)) {
                        $q = new Query();

                        $q->SELECT($tableid.' as ID');
                        $q->SELECT($tagFields);
            // Commented out on 2/1/2010 by Craig .. Mysql seems to be using the right indexes without this.
            // $q->FROM($db->quoteIdentifier($table).' FORCE INDEX(RoleOnly)');
                        $q->FROM($db->quoteIdentifier($table));
                        $q->WHERE("$tableid IN ({$db->joinQuote((array)$remainingIds)})");

                        $q->WHERE("Role IN ({$db->joinQuote((array)$fieldlikeRoles)})");

                        $dbRows = $this->getConnectionForWrite($originNodeRef)->readAll($q);

                        foreach($dbRows as $row)
                        {
                            $dbToCacheRows[$row['ID']][] = $row;
                            $rows[] = $row;
                        }

                    }

                    $this->NodeCache->putTags($direction, $remainingIds, $dbToCacheRows, $forceReadWrite);
                }

            }

            if($all) {

                $q = new Query();
//                if($forceReadWrite)
//                    $q->forUpdate();

                $q->SELECT($tableid.' as ID');
                $q->SELECT($tagFields);
                $q->FROM($db->quoteIdentifier($table));
                $q->WHERE("$tableid IN ({$db->joinQuote((array)$ids)})");
//                $q->WHERE("SectionID = 0");

                $rows = $db->readAll($q);

            } else {

                $runPartials = array();

                // perform a SQL statement per partial
                foreach($partials as $outPartial)
                {
                    if(in_array($outPartial->toString(), $runPartials))
                        continue;

                    if($outPartial['TagRole'] == false)
                        throw new TagException('Tag partials must specify a role when retrieving tags');

                    if($direction == 'out' && !in_array($outPartial['TagRole'], $outRoles))
                        continue;

                    $unions = array();
                    foreach((array)$ids as $id)
                    {
                        $q = new Query();

                        $q->SELECT($tableid.' as ID');
                        $q->SELECT($tagFields);
                        $q->FROM($db->quoteIdentifier($table));
//                        $q->WHERE("$tableid IN ({$db->joinQuote((array)$ids)})");
                        $q->WHERE("$tableid = {$db->quote($id)}");
    //                    $q->WHERE("SectionID = 0");

                        if($direction == 'out') {
                            $q->WHERE($this->getOutTagPartialClause($outPartial, $db, $db->quoteIdentifier($table)));
                        } else {
                            $q->WHERE($this->getInTagPartialClause($outPartial, $originNodeRef->getElement(), $db, $db->quoteIdentifier($table)));
                        }

                        if(!$forceReadWrite && $this->nodeDatabaseTagMergeLimit > 0)
                            $q->LIMIT($this->nodeDatabaseTagMergeLimit);

                        $unions[] = '('.(string)$q.')';
                    }

                    $sql = implode('
                         UNION ALL
                        ', $unions);

                    $rows = array_merge($rows, $db->readAll($sql));
                    $nonfieldlikeRoles[] = $outPartial['TagRole'];

                    $runPartials[] = $outPartial->toString();
                }
            }


        } else {
            $rows = $existingRows;
        }

        // if no rows, return empty array
        if(empty($rows))
            return $rows;

        $sortKeys = array();
        foreach($rows as $k => $row)
            $sortKeys[$k] = $row['TagSortOrder'];

        array_multisort($sortKeys, SORT_ASC, $rows);

        // gather linked NodeRefs
        $tagNodeRefs = array();
//        $strTagNodeRefs = array();

//        $this->Logger->debug('FIELD LIKE PARTIALS');
//        $this->Logger->debug($fieldlikePartials);

//        $this->Logger->debug('NON FIELD LIKE PARTIALS');
//        $this->Logger->debug($partials);

        $existingTagsPerNode = array();

        $newrows = array();
        $deletedTags = array();
        // filter out bogus rows
        foreach($rows as $k => $row)
        {
//            $rowElement = $this->ElementService->getByID($row['TagElementID']);

            if(!empty($row['TagElement']))
                $rowElement = $this->ElementService->getBySlug($row['TagElement']);
            else
                $rowElement = $this->ElementService->getByID($row['TagElementID']);

            // element doesn't exist anymore
            if(empty($rowElement))
                continue;

//            error_log($nodeRef);

            if(!array_key_exists($row['TagRole'], $tagDefs))
            {
                if($direction == 'in' && $rowElement->getSchema()->hasTagDef($row['TagRole']))
                    $row['TagDef'] = $rowElement->getSchema()->getTagDef($row['TagRole']);
                else
                    continue; // tag definition does not exist

            } else {
                $row['TagDef'] = $tagDefs[$row['TagRole']];
            }

            $row['TagElement'] = $rowElement->getSlug();
            $row['TagRoleDisplay'] = $row['TagDef']['Title'];
            $row['TagDirection'] = $direction;


            //$row['TagLinkRefURL'] = $nodeRef->getRefURL();
//            $row['TagSite'] = $nodeRef->getSite()->getSlug();

            if(!empty($restrictedPartials))
            {
                foreach($restrictedPartials as $partial)
                {
                    if($this->TagsHelper->matchPartial($partial,$row))
                    {
                        continue 2;
                    }
                }
            }

            if($all == false && $allfieldlike == false && !empty($fieldlikePartials) && in_array($row['TagRole'], $fieldlikeRoles))
            {
//                error_log('matching fieldlike');
//                error_log(print_r($fieldlikePartials, true));
                $found = false;
                foreach($fieldlikePartials as $partial)
                {
//                    error_log('test '.$partial->toString());
                    if($this->TagsHelper->matchPartial($partial,$row))
                    {
//                        error_log('match');
                        $found = true;
                        break;
                    }
                }

                if(!$found)
                    continue;
            }

            if($all == false && !empty($partials) && in_array($row['TagRole'], $nonfieldlikeRoles))
            {
//                error_log('matching fieldlike');
                $found = false;
                foreach($partials as $partial)
                {
//                    error_log('test '.$partial->toString());
                    if($this->TagsHelper->matchPartial($partial,$row))
                    {
//                        error_log('match');
                        $found = true;
                        break;
                    }
                }

                if(!$found)
                    continue;
            }

            $nodeRef = new NodeRef($rowElement, $row['TagSlug']);

            $row['TagLinkNodeRef'] = $nodeRef;
            $row['NoValidation'] = true;

            $tag = new Tag($row);

	        if(isset($existingTagsPerNode[$row['ID']]) && array_key_exists($tag->toString(), $existingTagsPerNode[$row['ID']]))
            {
                $tagIDToDelete = $existingTagsPerNode[$row['ID']][$tag->toString()];
                if($tagIDToDelete != $tag->TagID)
                {

                    if($tag->TagID > $tagIDToDelete) {
                        $tagIDToDelete = $tag->TagID;

                        // If the current tag is being deleted, mark it as deleted so that we can skip the rest of
                        // the processing once it's deleted from the db.
                        $tag->Deleted = true;
                    }
                    else {
                        // We're deleting a tag that was already added to the results array. Therefore just keep track of
                        // it so that we can remove it from the results array later.
                        if($fixedId)
                            $deletedTags[$fixedId][] = $tagIDToDelete;
                        else
                            $deletedTags[$row['ID']][] = $tagIDToDelete;
                    }

                    // read repair
                    $this->Logger->debug("Delete {$tag->getTagDirection()} tag: {$tag->toString()}");

                    $affectedRows = $this->getConnectionForWrite($originNodeRef)->write("DELETE FROM {$db->quoteIdentifier($table)} WHERE TagID = {$db->quote($tagIDToDelete)}");

                    if($affectedRows > 0)
                        $this->NodeEvents->fireTagEvents($direction.'tags', 'remove', $originNodeRef, $nodeRef, $tag);

                    // If the current tag is the one that got deleted, we're done processing this tag and can move to
                    // the next. This tag will not get added to the results.
                    if($tag->Deleted)
                        continue;
                }
            }

            $existingTagsPerNode[$row['ID']][$tag->toString()] = $tag->TagID;

            if($resolveLinkedRecords)
            {

                $tagNodeRefs[] = $nodeRef;
//                $strTagNodeRefs[$k] = (string)$nodeRef;

                // if need to merge more tags in
                if(array_key_exists($row['TagRole'], $childexprPartials))
                {
                    // TODO: check partial matches

                    $rowRefKey = $rowElement->getSlug();

                    $outPartials = $childexprPartials[$row['TagRole']];

                    foreach($outPartials as $outPartial) {
                        $expressions = StringUtils::smartSplit($outPartial->getChildPartials(), ".", '"', '\\"', 2);

                        $firstExpression = array_shift($expressions);

                        if(strtolower($firstExpression) == 'meta') {

                            $addlParams[$rowRefKey]['Meta.select'][] = 'all';

                        } elseif(strtolower($firstExpression) == 'fields')
                        {

                            $addlParams[$rowRefKey]['Meta.select'][] = 'all';
                            $addlParams[$rowRefKey]['OutTags.select'][] = 'fields';
                            $addlParams[$rowRefKey]['InTags.select'][] = 'fields';

                        } else {

							unset($schemaDef);
                            $isTag = true;
                            try{
                                $jPartial = new TagPartial($firstExpression);
                                $role = $jPartial->getTagRole();
                                if($nodeRef->getElement()->getSchema()->hasTagDef($role))
                                {
                                    $schemaDef = $nodeRef->getElement()->getSchema()->getTagDef($role);
                                } else if ($nodeRef->getElement()->getSchema()->hasMetaDef($role)) {
                                    $jPartial = new MetaPartial($firstExpression);
                                    $schemaDef = $nodeRef->getElement()->getSchema()->getMetaDef($role);
                                    $isTag = false;
                                }

                            }catch(Exception $e) {

                                $jPartial = new MetaPartial($firstExpression);
                                $role = $jPartial->getMetaName();
                                if ($nodeRef->getElement()->getSchema()->hasMetaDef($role))
                                    $schemaDef = $nodeRef->getElement()->getSchema()->getMetaDef($role);
                                $isTag = false;

                            }

                            if(empty($schemaDef))
                                continue;
                                //throw new NodeException('Cannot retrieve additional nodes for role ['.$role.'], no schema def found on element ['.$rowElement->getSlug().']');

                            if($isTag) {
                                $newPartials = $schemaDef->getDirection()=='out'?'OutTags.select':'InTags.select';
                                $jPartial = $jPartial->getTagRole();
                            } else {
                                $newPartials = 'Meta.select';
                                $jPartial = $jPartial->getMetaName();
                            }

                            $new = $firstExpression;
                            if(!empty($expressions[0]))
                                $new .= '.'.$expressions[0];

                            $addlParams[$rowRefKey][$newPartials][] = $new;
                            $jumpParams[$row['TagRole']][$newPartials][] = $jPartial;
                        }

                    }

                }

                $newrows[] = $tag;
            } else {

                if($fixedId)
                    $results[$fixedId][] = $row;
                else
                    $results[$row['ID']][] = $tag;

            }

        }


        if($resolveLinkedRecords)
        {

//            error_log('PRE NODE RETRIEVE');

            // find the corresponding records
            $nodeRows = array();
            $connectionCouplets = $this->getResolvedConnectionCouplets($tagNodeRefs, $forceReadWrite);

            foreach($connectionCouplets as $connectionCouplet)
            {

                $db = $connectionCouplet->getConnection();
                $tableToSlugs = $connectionCouplet->getAttribute('tablesToSlugs');

                foreach($tableToSlugs as $table => $tableInfo)
                {
                    extract($tableInfo);

                    $rowRefKey = $element->getSlug();

                    $partialJump = false;
                    $newNodePartials = null;

                    if(array_key_exists($rowRefKey, $addlParams))
                    {
                        $partialJump = true;

                        $addl = $addlParams[$rowRefKey];

                        $newNodePartials = new NodePartials();

                        if(array_key_exists('OutTags.select', $addl)){
                            $newNodePartials->setOutPartials(implode(',', array_unique($addl['OutTags.select'])));
                        }

                        if(array_key_exists('InTags.select', $addl)) {
                            $newNodePartials->setInPartials(implode(',',  array_unique($addl['InTags.select'])));
                        }

                        if(array_key_exists('Meta.select', $addl)) {
                            $newNodePartials->setMetaPartials(implode(',',  array_unique($addl['Meta.select'])));
                        }
                    }

                    if($checkJumpPermissions && !$this->NodePermissions->check('get', $tableNodeRef, $newNodePartials, true))
                        continue;

                    $foundRows = $this->multiGetFromDB($db, $tableid, $table, $tableNodeRef, $slugs, false, $forceReadWrite, false);

                    if($partialJump && !empty($foundRows))
                    {
                        $this->Logger->debug('Partials jump on '.$tableNodeRef->getElement()->getName());

                        $aoutTags = array();
                        $ainTags = array();
                        $ameta = array();

                        $this->Benchmark->start('partial-jump');

                        $idField = 'ID';
                        $pids = ArrayUtils::arrayMultiColumn($foundRows, $idField);

                        if($newNodePartials->hasOutPartials())
                            $aoutTags = $this->findOutTags($db, $tableNodeRef, $pids, $newNodePartials->getOutPartials(), false, false, $checkJumpPermissions);
                        if($newNodePartials->hasInPartials())
                            $ainTags = $this->findInTags($db, $tableNodeRef, $pids, $newNodePartials->getInPartials(), false, false, $checkJumpPermissions);
                        if($newNodePartials->hasMetaPartials())
                            $ameta = $this->NodeMetaDAO->findMeta($db, $tableNodeRef, $pids, $newNodePartials->getMetaPartials(), false);

                        foreach($foundRows as $nodeRefString => &$nrow)
                        {
                            $nrow->setNodePartials($newNodePartials);
                            $nrow->setMetas( isset($ameta[$nrow[$idField]])?$ameta[$nrow[$idField]]:array() );
                            $nrow->setOutTags( isset($aoutTags[$nrow[$idField]])?$aoutTags[$nrow[$idField]]:array() );
                            $nrow->setInTags( isset($ainTags[$nrow[$idField]])?$ainTags[$nrow[$idField]]:array() );
                        }
                        $this->Benchmark->end('partial-jump');
                    }

                    $nodeRows = array_merge($nodeRows, $foundRows);

                }

            }

            foreach($newrows as $row)
            {
//                $id = $row['ID'];
//                unset($row['ID']);
//                error_log(print_r($row, true));

                if($resolveLinkedRecords)
                {
                    $nodeRef = $row['TagLinkNodeRef'];
                    if(!array_key_exists((string)$nodeRef, $nodeRows))
                        continue;

                    $tagNode = $nodeRows[(string)$nodeRef];

                    // if need to filter out jump partials
                    if(array_key_exists($row['TagRole'], $jumpParams))
                    {
                        $outPartials = $jumpParams[$row['TagRole']];

                        foreach($outPartials as $key => $kPartials) {
                            if($key == 'Meta.select')
                            {
                                foreach($tagNode->getMetas() as $meta)
                                    if(!in_array($meta->MetaName, $kPartials))
                                        $tagNode->removeMeta($meta->MetaName);

                            } else if($key == 'InTags.select') {

                                foreach($tagNode->getInTags() as $tag)
                                    if(!in_array($tag->TagRole, $kPartials))
                                        $tagNode->removeInTags($tag->TagRole);

                            } else if($key == 'OutTags.select') {

                                foreach($tagNode->getOutTags() as $tag)
                                    if(!in_array($tag->TagRole, $kPartials))
                                        $tagNode->removeOutTags($tag->TagRole);
                            }
                        }
                    }


                    // ignore deleted records
                    //if($tagNode['Status'] == 'deleted')
                    //    continue;

                    $row['TagLinkNode'] = $tagNode;
                    $row['TagLinkTitle'] = $tagNode['Title'];
                    $row['TagLinkID'] = $tagNode['ID'];
                    $row['TagLinkStatus'] = $tagNode['Status'];
                    $row['TagLinkActiveDate'] = $tagNode['ActiveDate'];
                    $row['TagLinkSortOrder'] = $tagNode['SortOrder'];

                    // only populate TagLinkURL if the record is active
                    if($tagNode['Status'] == 'published' && $tagNode['ActiveDateUnix'] < $now->toUnix()) {
                        $row['TagLinkIsActive'] = true;
                        $row['TagLinkURL'] = $nodeRef->getRecordLink();
                        $row['TagLinkURI'] = $nodeRef->getRecordLinkURI();
                    } else
                        $row['TagLinkURL'] = '';
                        $row['TagLinkURI'] = '';

                }


                if($fixedId)
                    $results[$fixedId][] = $row;
                else
                    $results[$row['ID']][] = $row;
            }
        }

        // If tags were deleted during the 'read repair' they need to be removed from the results array
        // Go through the nodes that have had tags deleted. If those nodes exist in the results array, go
        // through the tags being returned. If the tag exists in the 'deleted' array for the node, remove it from
        // the result set.
        if(!empty($deletedTags)) {
            foreach($deletedTags as $rowId => $rowDeletedTags) {
                if(isset($results[$rowId]) && !empty($results[$rowId])) {
                    foreach($results[$rowId] as $resultKey => $rowResults) {
                        if(in_array($rowResults->TagID, $rowDeletedTags)) {
                            unset($results[$rowId][$resultKey]);
                        }
                    }
                }
            }
        }

        if($returnOne)
            return isset($results[$returnOne])?$results[$returnOne]:array();

        return $results;
    }


    public function getOutTagPartialClause(TagPartial $tagPartial, DatabaseInterface $db, $table)
    {

        $clause = '';

        foreach(array('element', 'slug', 'role', 'value') as $key) {
            $partialKey = 'Tag'.ucfirst($key);
            if (empty($tagPartial->$partialKey)) continue;

            if (!empty($clause)) $clause .= ' AND';

            if ($key == 'element') {
                $element = $this->ElementService->getBySlug($tagPartial->TagElement);

                $clause .= " {$table}.ElementID = {$db->quote($element->ElementID)}";

            } else {

                $val = $tagPartial->$partialKey;
                $clause .= ' '.$table.'.'.$key." = ".$db->quote($val)." ";

            }

        }
        return $clause;

    }

    public function getInTagPartialClause(TagPartial $tagPartial, Element $myElement, DatabaseInterface $db, $table)
    {

        $clause = '';

        foreach(array('element', 'slug', 'role', 'value') as $key) {
            $partialKey = 'Tag'.ucfirst($key);
            if (empty($tagPartial->$partialKey)) continue;

            if (!empty($clause)) $clause .= ' AND';

            if ($key == 'element') {
                $element = $this->ElementService->getBySlug($tagPartial->TagElement);

                $clause .= " {$table}.ElementID = {$db->quote($element->ElementID)}";

            } else {

                $val = $tagPartial->$partialKey;
                $clause .= ' '.$table.'.'.$key." = ".$db->quote($val)." ";

            }

        }
        return $clause;

    }


    public function saveOutTags(DatabaseInterface $db, NodeRef $originNodeRef, $recordid, $outPartials = 'fields', array $outTags, $restrictedPartials = '')
    {

        if(empty($recordid)) {
            throw new Exception('Cannot save out tags without recordid');
        }

        TagUtils::validateTags($outTags);

//        $originNodeRef = $node->getNodeRef();


        $originalRestrictedPartials = $restrictedPartials;

        $restrictedPartials = PartialUtils::unserializeOutPartials($restrictedPartials);
        if($restrictedPartials == 'all' || ($x = array_search('all', $restrictedPartials)) !== false) {
            return false;
        }

        $outtable = $this->NodeDBMeta->getOutTagsTable($originNodeRef);
        $tableid = $this->NodeDBMeta->getPrimaryKey($originNodeRef);

//        if($sectionType != null)
//        {
//            $schema = $originNodeRef->getElement()->getSchema()->getSectionDef($sectionType);
//        } else
//        {
            $schema = $originNodeRef->getElement()->getSchema();
//        }


//        $this->Logger->debug('current tags');
//        $this->Logger->debug($currentOutTags);

//        $this->Logger->debug('out tags');
//        $this->Logger->debug($outTags);

//        if($sectionid != 0)
//            foreach($outTags as &$outTag)
//                $outTag->setTagSectionID($sectionid);

        $tagsToDelete = array();
        $tagsToUpdate = array();

        // remove duplicates from tags
        foreach($outTags as $o => $tag) {
            $tagDef = $schema->getTagDef($tag->getTagRole());

            if(!$tagDef->isSortable()) {
                $tag->TagSortOrder = 0;
            } else {
                $tag->ShouldMatchSort = true;
            }

            foreach($outTags as $i => $dtag) {
                if($o != $i && $tag->matchExact($dtag))
                {
//                  error_log("REMOVING DUPE ".$dtag->toString());
                    unset($outTags[$o]);
                }
            }

            foreach($restrictedPartials as $partial)
            {
                if($this->TagsHelper->matchPartial($partial,$tag))
                {
                    unset($outTags[$o]);
                }
            }
        }


        $currentOutTags = $this->findTags('out', $db, $originNodeRef, $recordid, $outPartials, true, false, $originalRestrictedPartials, $resolveLinkedRecords = false);

        TagUtils::validateTags($currentOutTags);


        foreach($currentOutTags as $k => $tag) {
            if($tag->getTagID() == false) throw new Exception('Cannot save tags without TagIDs for current tags');

            $foundThisTag = false;
            foreach($outTags as $k2 => $dtag) {
                // Get a list of fields that differ between the current tag and the tag to be saved
                $tagDiff = $tag->diff($dtag, $dtag->ShouldMatchSort);
                if(empty($tagDiff) /* && $tag->getTagSectionID() == $dtag->getTagSectionID()*/ )
                {
//                    $this->Logger->debug('Matched: '.$dtag);
                    // remove it from out tags, since it exists already
                    unset($outTags[$k2]);
                    $foundThisTag = true;
                    break;
                }
                elseif(count($tagDiff) == 1 && $tagDiff[0] == 'TagSortOrder') {
                    // If the only difference between the tags is sort order, we don't
                    // want to remove the tag and then add it back. Just do an update.
                    // Save the tag to run through updates later.

                    $tag->setTagSortOrder($dtag->getTagSortOrder());
                    $tagsToUpdate[] = $tag;

                    // Remove it from outtags because we don't want it added again
                    unset($outTags[$k2]);
                    $foundThisTag = true;

                    break;
                }
            }

            if(!$foundThisTag) {
//                $this->Logger->debug('Unmatched: '.$tag);
                $tagsToDelete[] = $tag;
            }
        }

        if(empty($tagsToDelete) && empty($outTags) && empty($tagsToUpdate)) {
            return false;
        }

        $tagIDsToDelete = array();
        foreach($tagsToDelete as $tag) {

            $this->Logger->debug("Delete {$tag->getTagDirection()} tag: {$tag->toString()}");

            // log tag deletion
//            $this->transactionsService->logTagDelete($element, $recordid, $tag);

//          error_log("DELETE TAG: ".$tag->toString());

            // delete corresponding in tag
            $inNodeRef = $tag->getTagLinkNodeRef();

            $intable = $this->NodeDBMeta->getInTagsTable($inNodeRef);
            $intableid = $this->NodeDBMeta->getPrimaryKey($inNodeRef);

            try {
                $inrecordid = $this->getRecordIDFromNodeRef($inNodeRef);
            }catch(NodeException $ne) {
                continue;
            }

            $affectedRows = $this->getConnectionForWrite($inNodeRef)->write("
                DELETE FROM {$db->quoteIdentifier($intable)} WHERE
                    {$intableid} = {$db->quote($inrecordid)} AND
                    ElementID = {$db->quote($originNodeRef->getElement()->getElementID())} AND
                    Slug = {$db->quote($originNodeRef->getSlug())} AND
                    Role = {$db->quote($tag->getTagRole())} AND
                    Value = {$db->quote($tag->getTagValue())}", DatabaseInterface::AFFECTED_ROWS);

            if($affectedRows > 0) {
                $this->NodeEvents->fireTagEvents('intags', 'remove', $inNodeRef, $originNodeRef, $tag);
            }

            $affectedRows = $db->deleteRecord($db->quoteIdentifier($outtable), "TagID = {$db->quote($tag['TagID'])}");

            if($affectedRows > 0) {
                $this->NodeEvents->fireTagEvents('outtags', 'remove', $originNodeRef, $inNodeRef, $tag);
            }

        }

        // Tags that only have their sort order changed, do an update on both sides of the tag and fire an sortOrder.edit
        // event for the side of the tag that is ordered.
        foreach($tagsToUpdate as $tag) {
            $this->Logger->debug("Update {$tag->getTagDirection()} tag: {$tag->toString()}");

            // update corresponding in tag
            $inNodeRef = $tag->getTagLinkNodeRef();

            $intable = $this->NodeDBMeta->getInTagsTable($inNodeRef);
            $intableid = $this->NodeDBMeta->getPrimaryKey($inNodeRef);

            try {
                $inrecordid = $this->getRecordIDFromNodeRef($inNodeRef);
            }catch(NodeException $ne) {
                continue;
            }

            $updateArray = array(
                'SortOrder' => $tag->getTagSortOrder()
            );

            $affectedRows = $db->updateRecord($db->quoteIdentifier($intable), $updateArray,
                "{$intableid} = {$db->quote($inrecordid)} AND
                ElementID = {$db->quote($originNodeRef->getElement()->getElementID())} AND
                Slug = {$db->quote($originNodeRef->getSlug())} AND
                Role = {$db->quote($tag->getTagRole())} AND
                Value = {$db->quote($tag->getTagValue())}");

            $affectedRows = $db->updateRecord($db->quoteIdentifier($outtable), $updateArray, "TagID = {$db->quote($tag['TagID'])}");

            if($affectedRows > 0) {
                $this->NodeEvents->fireTagEvents('outtags', 'sortOrder.edit', $originNodeRef, $inNodeRef, $tag);
            }
        }

//        sleep(15);
//        error_log('Sleeping...');

        foreach($outTags as $tag) {

            $this->Logger->debug("Add {$tag->getTagDirection()} tag: {$tag->toString()}");

            $outElement = $this->ElementService->getBySlug($tag->getTagElement());

            $inNodeRef = new NodeRef($outElement, $tag->getTagSlug());

            try {
                $inRecordID = $this->getRecordIDFromNodeRef($inNodeRef);
            }catch(NodeException $ne) {
                continue;
            }

            $outTagArray = array(
                $tableid => $recordid,
                'ElementID' => $outElement->getElementID(),
                'Slug' => $tag->getTagSlug(),
                'Role' => $tag->getTagRole(),
                'Value' => $tag->getTagValue(),
                'ValueDisplay' => $tag->getTagValueDisplay(),
                'SortOrder' => $tag->getTagSortOrder()
            );

            $tagid = $db->insertRecord($db->quoteIdentifier($outtable), $outTagArray);
            $this->NodeEvents->fireTagEvents('outtags', 'add', $originNodeRef, $inNodeRef, $tag);


            $intable = $this->NodeDBMeta->getInTagsTable($inNodeRef);
            $inPrimaryKey = $this->NodeDBMeta->getPrimaryKey($inNodeRef);

            $inTagArray = array(
                $inPrimaryKey => $inRecordID,
                'ElementID' => $originNodeRef->getElement()->getElementID(),
                'Slug' => $originNodeRef->getSlug(),
                'Role' => $tag->getTagRole(),
                'Value' => $tag->getTagValue(),
                'ValueDisplay' => $tag->getTagValueDisplay(),
                'SortOrder' => $tag->getTagSortOrder()
            );

            $tagid = $this->getConnectionForWrite($inNodeRef)->insertRecord($db->quoteIdentifier($intable), $inTagArray);
            $this->NodeEvents->fireTagEvents('intags', 'add', $inNodeRef, $originNodeRef, $tag);

        }

        return true;
    }

    public function saveInTags(DatabaseInterface $db, NodeRef $originNodeRef, $recordid, $inPartials = 'fields', array $inTags, $restrictedPartials = '')
    {

        if(empty($recordid)) {
            throw new Exception('Cannot save in tags without recordid');
        }

        TagUtils::validateTags($inTags);

        $originalRestrictedPartials = $restrictedPartials;

        $restrictedPartials = PartialUtils::unserializeInPartials($restrictedPartials);
        if($restrictedPartials == 'all' || ($x = array_search('all', $restrictedPartials)) !== false) {
            return false;
        }


//        $originNodeRef = $node->getNodeRef();

//        $outtable = $this->NodeDBMeta->getOutTagsTable($originNodeRef);
        $intable = $this->NodeDBMeta->getInTagsTable($originNodeRef);
        $tableid = $this->NodeDBMeta->getPrimaryKey($originNodeRef);

//        $now = $this->DateFactory->newStorageDate();

//        $schema = $originNodeRef->getElement()->getSchema();


        $tagsToDelete = array();
        $tagsToUpdate = array();

        // remove duplicates from tags
        foreach($inTags as $o => $tag) {

            if($originNodeRef->getElement()->hasTagDef($tag->getTagRole())) {
                $tagDef = $originNodeRef->getElement()->getSchema()->getTagDef($tag->getTagRole());
            }
            else {
                $externalElement = $this->ElementService->getBySlug($tag->getTagElement());
                $tagDef = $externalElement->getSchema()->getTagDef($tag->getTagRole());
            }

            $tag->MatchPartial = TagUtils::determineMatchPartial($tagDef, $originNodeRef->getSlug());

            $this->Logger->debug('MATCH PARTIAL for ['.$tag->toString().'] is ['.$tag->MatchPartial->toString().']');

            if(!$tagDef->isSortable()) {
                $tag->TagSortOrder = 0;
            } else {
                $tag->ShouldMatchSort = true;
            }

            foreach($inTags as $i => $dtag) {
                if($o != $i && $tag->matchExact($dtag))
                {
//                  error_log("REMOVING DUPE ".$dtag->toString());
                    unset($inTags[$o]);
                }
            }

            foreach($restrictedPartials as $partial)
            {
                if($this->TagsHelper->matchPartial($partial,$tag))
                {
                    unset($inTags[$o]);
                }
            }
        }

        $currentInTags = $this->findTags('in', $db, $originNodeRef, $recordid, $inPartials, true, false, $originalRestrictedPartials, $resolveLinkedRecords = false);

        TagUtils::validateTags($currentInTags, 'in');

        foreach($currentInTags as $k => $tag) {
            //if($tag->getTagOutID() == false) throw new Exception('Cannot save in tags without TagOutIDs for current tags');

            $foundThisTag = false;
            foreach($inTags as $k2 => $dtag) {
                // Get a list of fields that differ between the current tag and the tag to be saved
                $tagDiff = $tag->diff($dtag, $dtag->ShouldMatchSort);
                if(empty($tagDiff))
                {
                    // remove it from inTags, since it exists already
                    unset($inTags[$k2]);
                    $foundThisTag = true;
                    break;
                }
                elseif(count($tagDiff) == 1 && $tagDiff[0] == 'TagSortOrder') {
                    // If the only difference between the tags is sort order, we don't
                    // want to remove the tag and then add it back. Just do an update.
                    // Save the tag to run through updates later.

                    $tag->setTagSortOrder($dtag->getTagSortOrder());
                    $tagsToUpdate[] = $tag;

                    // Remove it from intags because we don't want it added again
                    unset($inTags[$k2]);
                    $foundThisTag = true;
                    break;
                }
            }

            if(!$foundThisTag) {
                $tagsToDelete[] = $tag;
            }
        }

        if(empty($tagsToDelete) && empty($inTags) && empty($tagsToUpdate)) {
            return false;
        }

        foreach($tagsToDelete as $tag) {

            $this->Logger->debug("Delete {$tag->getTagDirection()} tag: {$tag->toString()}");

            // delete corresponding out tag
            $outElement = $this->ElementService->getBySlug($tag->getTagElement());
            $outNodeRef = new NodeRef($outElement, $tag->getTagSlug());

            $outtable = $this->NodeDBMeta->getOutTagsTable($outNodeRef);
            $outtableid = $this->NodeDBMeta->getPrimaryKey($outNodeRef);

            try {
                $outrecordid = $this->getRecordIDFromNodeRef($outNodeRef);
            }catch(NodeException $ne) {
                continue;
            }

            $affectedRows = $this->getConnectionForWrite($outNodeRef)->write("
                DELETE FROM {$db->quoteIdentifier($outtable)} WHERE
                    {$outtableid} = {$db->quote($outrecordid)} AND
                    ElementID = {$db->quote($originNodeRef->getElement()->getElementID())} AND
                    Slug = {$db->quote($originNodeRef->getSlug())} AND
                    Role = {$db->quote($tag->getTagRole())} AND
                    Value = {$db->quote($tag->getTagValue())}", DatabaseInterface::AFFECTED_ROWS);

            if($affectedRows > 0) {
                $this->NodeEvents->fireTagEvents('outtags', 'remove', $outNodeRef, $originNodeRef, $tag);
            }

            $affectedRows = $db->deleteRecord($db->quoteIdentifier($intable), "TagID = {$db->quote($tag['TagID'])}");

            if($affectedRows > 0) {
                $this->NodeEvents->fireTagEvents('intags', 'remove', $originNodeRef, $outNodeRef, $tag);
            }

        }

        // Tags that only have their sort order changed, do an update on both sides of the tag and fire an sortOrder.edit
        // event for the side of the tag that is ordered.
        foreach($tagsToUpdate as $tag) {
            $this->Logger->debug("Update {$tag->getTagDirection()} tag: {$tag->toString()}");

            // update corresponding out tag
            $outNodeRef = $tag->TagLinkNodeRef;

            $outtable = $this->NodeDBMeta->getOutTagsTable($outNodeRef);
            $outtableid = $this->NodeDBMeta->getPrimaryKey($outNodeRef);

            try {
                $outrecordid = $this->getRecordIDFromNodeRef($outNodeRef);
            }catch(NodeException $ne) {
                continue;
            }

            $updateArray = array(
                'SortOrder' => $tag->getTagSortOrder()
            );

            $affectedRows = $db->updateRecord($db->quoteIdentifier($outtable), $updateArray,
                "{$outtableid} = {$db->quote($outrecordid)} AND
                ElementID = {$db->quote($originNodeRef->getElement()->getElementID())} AND
                Slug = {$db->quote($originNodeRef->getSlug())} AND
                Role = {$db->quote($tag->getTagRole())} AND
                Value = {$db->quote($tag->getTagValue())}");

            $affectedRows = $db->updateRecord($db->quoteIdentifier($intable), $updateArray, "TagID = {$db->quote($tag['TagID'])}");

            if($affectedRows > 0) {
                $this->NodeEvents->fireTagEvents('intags', 'sortOrder.edit', $originNodeRef, $outNodeRef, $tag);
            }
        }

        if(!empty($inTags)) {

            foreach($inTags as $inTag) {

//                if($inTag->getTagSectionID() != 0)
//                    throw new NodeException('Cannot save in tags coming from a section');


                $externalElement = $this->ElementService->getBySlug($inTag->getTagElement());

                $externalNodeRef = new NodeRef($externalElement, $inTag->getTagSlug());

                try {
                    $externalRecordID = $this->getRecordIDFromNodeRef($externalNodeRef);
                }catch(NodeException $ne) {
                    continue;
                }

                if(''.$externalNodeRef == ''.$originNodeRef) {
                    continue;
                }

                $this->Logger->debug("Add {$inTag->getTagDirection()} tag: {$inTag->toString()}");

                //$inTagPartial = new TagPartial($inTag);

                $db = $this->getConnectionForWrite($externalNodeRef);

                $newTag = new Tag(
                    $originNodeRef->getElement()->getSlug(),
                    $originNodeRef->getSlug(),
                    $inTag->getTagRole(),
                    $inTag->getTagValue(),
                    $inTag->getTagValueDisplay());

                $newTag->setTagSortOrder($inTag->getTagSortOrder());


                // match partial is used to guarantee that only 1 outbound link exists for that element or element/value combo
                $this->saveOutTags($db, $externalNodeRef, $externalRecordID, $inTag->getMatchPartial()->toString(), array($newTag));

            }
        }

        return true;

    }

    public function fireAddOutTagEvents(NodeRef $nodeRef)
    {
        return $this->fireAllTagEvents($nodeRef, 'out', 'add');
    }

    public function fireAddInTagEvents(NodeRef $nodeRef)
    {
        return $this->fireAllTagEvents($nodeRef, 'in', 'add');
    }

    public function fireRemoveOutTagEvents(NodeRef $nodeRef)
    {
        return $this->fireAllTagEvents($nodeRef, 'out', 'remove');
    }

    public function fireRemoveInTagEvents(NodeRef $nodeRef)
    {
        return $this->fireAllTagEvents($nodeRef, 'in', 'remove');
    }

    protected function fireAllTagEvents(NodeRef $nodeRef, $direction = 'out', $action = 'add')
    {
        $oppositeTagDirection = $direction == 'out' ? 'in' : 'out';

        $db = $this->getConnectionForWrite($nodeRef);
        $recordid = $this->getRecordIDFromNodeRef($nodeRef);
        $tags = $this->findTags($direction, $db, $nodeRef, $recordid, 'all', true, false, '', $resolveLinkedRecords = true);

        foreach($tags as $tag)
        {
            $this->NodeEvents->fireTagEvents($direction.'tags', $action, $nodeRef, $tag->getTagLinkNodeRef(), $tag);
            $this->NodeEvents->fireTagEvents($oppositeTagDirection.'tags', $action, $tag->getTagLinkNodeRef(), $nodeRef, $tag);
        }
    }


}
