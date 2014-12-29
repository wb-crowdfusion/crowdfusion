<?php
/**
 * NodeMetaDAO
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
 * @version     $Id: NodeMetaDAO.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeMetaDAO
 *
 * @package     CrowdFusion
 */
class NodeMetaDAO extends AbstractNodeDAO
{

    public function __construct()
    {

    }

    public function setTypeConverter(TypeConverter $TypeConverter)
    {
        $this->TypeConverter = $TypeConverter;
    }

    public function findMeta(DatabaseInterface $db, NodeRef $originNodeRef, $ids, $metaPartials = 'fields', $forceReadWrite = false, $restrictedPartials = '') {

        $results = array();

        if(empty($metaPartials))
            return $results;

        $this->Logger->debug('findMeta with partials ['.$metaPartials.'] restrict ['.$restrictedPartials.']');

        $returnOne = false;

        if(!is_array($ids)){
            $returnOne = $ids;
            $ids = array( $originNodeRef->getRefURL() => $ids );
        }

        $partials = PartialUtils::unserializeMetaPartials($metaPartials);
        $restrictedPartials = PartialUtils::unserializeMetaPartials($restrictedPartials);

        if($restrictedPartials == 'all' || ($x = array_search('all', $restrictedPartials)) !== false)
        {
            return array();
        }

        $tableid = $this->NodeDBMeta->getPrimaryKey($originNodeRef);

        $now = $this->DateFactory->newLocalDate();

        $metaPartials = array();
        $all = false;

        $metaDefs = $originNodeRef->getElement()->getSchema()->getMetaDefs();
        $metaSchemas = $originNodeRef->getElement()->getSchema()->getMetaStorageDatatypes();

        if($partials == 'all' ||
            ($x = array_search('all', $partials)) !== false) {

            if($partials == 'all' || (isset($x) && $x !== false)) {
                $all = true;
                if(is_array($partials))
                    unset($partials[$x]);
                else
                    $partials = array();
            }

        }

        foreach($partials as $z => $metaPartial)
        {
            if(!array_key_exists($metaPartial->getMetaName(), $metaDefs)) {
                unset($partials[$z]);
                continue;
            }

            $metaPartials[] = $metaPartial;
        }

        $rows = array();
        $cachedIds = array();
        // retrieve from cache
        list($cachedIds, $rows) = $this->NodeCache->getMeta('', $ids, $forceReadWrite);
        $remainingIds = array_diff($ids, $cachedIds);

        if(!empty($remainingIds))
        {
            $dbToCacheRows = array();

            // perform a SQL statement per partial
            foreach($metaSchemas as $datatype)
            {
                $table = $this->NodeDBMeta->getMetaTable($originNodeRef, $datatype);

                $q = new Query();

                $q->SELECT($tableid.' as ID');
                $q->SELECT(array(
//                    'MetaID',
                    'Name as MetaName',
                ));

                if(($datatypeCol = $this->NodeDBMeta->getMetaDatatypeColumn($datatype)) != null){
                    $q->SELECT("{$datatypeCol}Value as MetaValue");
                }

                $q->FROM($db->quoteIdentifier($table));
                $q->WHERE("$tableid IN ({$db->joinQuote((array)$remainingIds)})");

                $dbRows = $this->getConnectionForWrite($originNodeRef)->readAll($q);

                foreach($dbRows as $row)
                {
                    $dbToCacheRows[$row['ID']][] = $row;
                    $rows[] = $row;
                }
                unset($dbRows);
                unset($datatype);
                unset($q);
                unset($table);

            }

            $this->NodeCache->putMeta('', $remainingIds, $dbToCacheRows, $forceReadWrite);
            unset($dbToCacheRows);
            unset($remainingIds);
            unset($metaSchemas);


        }


        foreach($rows as $row)
        {
            $id = $row['ID'];
            unset($row['ID']);

            if(!array_key_exists($row['MetaName'], $metaDefs))
                continue;

            foreach($restrictedPartials as $dPartial)
            {
                if(strcmp($dPartial->getMetaName(), $row['MetaName']) === 0){
                    continue 2;
                }
            }

            if(!$all) {

                $found = false;
                foreach($metaPartials as $dPartial)
                {
                    if(strcmp($dPartial->getMetaName(), $row['MetaName']) === 0){
                        $found = true;
                        break;
                    }
                }

                if(!$found)
                    continue;
            }

            $metaDef = $metaDefs[$row['MetaName']];
            $row['MetaTitle'] = $metaDef->Title;
            $row['MetaStorageDatatype'] = (string)$metaDef->Datatype;
            $row['MetaValidationDatatype'] = $metaDef->Validation->getDatatype();
            if($row['MetaValidationDatatype'] == 'date')
               $row['MetaValue'] = $this->TypeConverter->convertFromString($metaDef->Validation, $row['MetaValue'], null, true);
            else if($row['MetaStorageDatatype'] == 'flag')
                  $row['MetaValue'] = 1;

            $row['NoValidation'] = true;
            $results[$id][$row['MetaName']] = new Meta($row);
        }



        if($returnOne)
            return isset($results[$returnOne])?$results[$returnOne]:array();

        return $results;
    }


    public function getMetaPartialClause(MetaPartial $metaPartial, $datatype, DatabaseInterface $db, $table)
    {

        $clause = '';

        foreach(array('name', 'value') as $key) {
            $partialKey = 'Meta'.ucfirst($key);
            if (!isset($metaPartial->$partialKey)) continue;

            if (!empty($clause)) $clause .= ' AND';

            $val = $metaPartial->$partialKey;

            if ($key == 'value') {
                if(($datatypeCol = $this->NodeDBMeta->getMetaDatatypeColumn($datatype)) != null)
                    $clause .= " {$db->quoteIdentifier($table)}.{$datatypeCol}Value = {$db->quote($val)}";
            } else {
                $clause .= ' '.$db->quoteIdentifier($table).'.'.$key." = ".$db->quote($val)." ";
            }

        }
        return $clause;

    }

    public function saveMeta(DatabaseInterface $db, NodeRef $originNodeRef, $recordid, $metaPartials = 'fields', array $metaTags, $restrictedPartials = '') {

        if(empty($recordid))
            throw new Exception('Cannot save meta without recordid');

        $originalRestrictedPartials = $restrictedPartials;

        $restrictedPartials = PartialUtils::unserializeMetaPartials($restrictedPartials);

        if($restrictedPartials == 'all' || ($x = array_search('all', $restrictedPartials)) !== false)
        {
            return;
        }

        MetaUtils::validateMeta($metaTags);

//        $originNodeRef = $node->getNodeRef();


        $tableid = $this->NodeDBMeta->getPrimaryKey($originNodeRef);

        $now = $this->DateFactory->newStorageDate();

        $tagsToDelete = array();
        $updatedMeta = array();
        $originalMeta = array();

//        if($sectionType != null)
//        {
//            $schema = $originNodeRef->getElement()->getSchema()->getSectionDef($sectionType);
//        } else
//        {
            $schema = $originNodeRef->getElement()->getSchema();
//        }

        // remove duplicates from meta
        foreach($metaTags as $o => &$mtag) {
//            if($sectionid != 0)
//                $mtag->setMetaSectionID($sectionid);

            $metaDef = $schema->getMetaDef($mtag->getMetaName());
            $mtag->setMetaStorageDatatype($metaDef->Datatype);
            $mtag->setMetaValidationDatatype($metaDef->Validation->getDatatype());

            foreach($metaTags as $i => $ddtag) {
                //if($o != $i && $tag->getMetaName() === $dtag->getMetaName() && $tag->getMetaSectionID() == $dtag->getMetaSectionID())
                if($o != $i && $mtag->getMetaName() === $ddtag->getMetaName())
                {
//                  error_log("REMOVING DUPE ".$dtag->toString());
                    unset($metaTags[$o]);
                }
            }
            foreach($restrictedPartials as $dPartial)
            {
                if(strcmp($dPartial->getMetaName(), $mtag['MetaName']) === 0){
                    unset($metaTags[$o]);
                }
            }
        }

        $currentMetaTags = $this->findMeta($db, $originNodeRef, $recordid, $metaPartials, true, $originalRestrictedPartials);

//        $this->Logger->debug('current meta for ['.$recordid.'] on section ['.$sectionid.']');
//        $this->Logger->debug($currentMetaTags);

        MetaUtils::validateMeta($currentMetaTags);


        foreach($currentMetaTags as $k => $tag) {
            //if($tag->getMetaID() == false) throw new Exception('Cannot save meta without MetaIDs for current meta');

            $foundThisTag = false;
            foreach($metaTags as $dtag) {
                //if($tag->getMetaName() === $dtag->getMetaName() && $tag->getMetaSectionID() == $dtag->getMetaSectionID())
                if($tag->getMetaName() === $dtag->getMetaName() /*&& $tag->getMetaSectionID() == $dtag->getMetaSectionID()*/)
                {
//                  error_log("MATCHED ".$dtag->toString() ." TO ".$tag->toString());
//                  error_log(print_r($dtag, true));
//                  error_log(print_r($tag, true));

                  // delete string meta that have no value
                    if(
                    (($dtag->getMetaStorageDatatype() == 'text' || $dtag->getMetaStorageDatatype() == 'varchar' || $dtag->getMetaStorageDatatype() == 'blob') &&
                       strlen(trim($dtag->getMetaValue())) == 0) ||
                       ($dtag->getMetaStorageDatatype() == 'flag' && $dtag->getMetaValue() == false) ||
                            (($dtag->getMetaValidationDatatype() == 'int' || $dtag->getMetaValidationDatatype() == 'float') && $dtag->getMetaValue() === null) ||
                        in_array($dtag, $updatedMeta)) {
//                            error_log('DELETE: '.$tag);
                        $tagsToDelete[] = $tag;
                    } else {
//                        error_log('UPDATE: '.$tag);
                        $updatedMeta[$tag->getMetaStorageDatatype()][] = $dtag;
                        $originalMeta[$tag->getMetaStorageDatatype()][] = $tag;
                    }

                    $foundThisTag = true;
                    break;
                }
            }


            if(!$foundThisTag)
                $tagsToDelete[] = $tag;
        }

        $changedOne = false;

        $tagIDsToDelete = array();
        foreach($tagsToDelete as $tag) {

            // log tag deletion
//            $this->transactionsService->logMetaDelete($element, $recordid, $tag);
            $table = $this->NodeDBMeta->getMetaTable($originNodeRef, $tag->getMetaStorageDatatype());

            $affectedRows = $db->deleteRecord($table, "{$tableid} = {$db->quote($recordid)} AND Name = {$db->quote($tag->getMetaName())}");

            if($affectedRows > 0) {
                $this->NodeEvents->fireMetaEvents('meta', 'remove', $originNodeRef, $tag);
                $changedOne = true;
            }

//            $tagIDsToDelete[][] = $tag->getMetaID();
        }

//        if(!empty($tagIDsToDelete)){
//            foreach($tagIDsToDelete as $datatype => $ids){
//
//                ;
//                $deletions = $db->write("DELETE FROM {$db->quoteIdentifier($table)} WHERE MetaID IN (".$db->joinQuote((array)$ids).")", DatabaseInterface::AFFECTED_ROWS);
//
//            }
//        }

        foreach($metaTags as $tag) {
            $datatype = $tag->getMetaStorageDatatype();
            $table = $db->quoteIdentifier($this->NodeDBMeta->getMetaTable($originNodeRef, $datatype));

            if(($datatype == 'text' || $datatype == 'varchar' || $datatype == 'blob') &&
                       strlen(trim($tag->getMetaValue())) == 0)
                continue;

            if($datatype == 'flag' && $tag->getMetaValue() == false)
                continue;

            if(($tag->getMetaValidationDatatype() == 'int' || $tag->getMetaValidationDatatype() == 'float') && $tag->getMetaValue() === null)
                continue;

            $datatypeCol = $this->NodeDBMeta->getMetaDatatypeColumn($datatype);

            if(isset($updatedMeta[$datatype]) && in_array($tag, $updatedMeta[$datatype])) {
                $tagid = array_search($tag, $updatedMeta[$datatype]);

                $originalTag = $originalMeta[$datatype][$tagid];

                // if this is an integer storage field and the originalvalue is 0 and the new value equates to 0, do no update
                if(in_array(strtolower($datatypeCol), array('tiny', 'int', 'long', 'float')) && floatVal($originalTag->getMetaValue()) == 0 && floatVal($tag->getMetaValue()) == 0)
                    continue;

                if($datatype != 'flag' && strcmp(''.$originalTag->getMetaValue(), ''.$tag->getMetaValue()) !== 0) {

                    // update the meta tag
                    $affectedRows = $db->updateRecord($table, array( "{$datatypeCol}Value" => $tag->getMetaValue() ), "{$tableid} = {$db->quote($recordid)} AND Name = {$db->quote($tag->getMetaName())}");

                    if($affectedRows > 0) {
                        $this->NodeEvents->fireMetaEvents('meta', 'update', $originNodeRef, $tag, $originalTag);
//                        if($tag->getMetaValidationDatatype() == 'boolean')
//                            if($tag->getMetaValue() == false)
//                                $this->NodeEvents->fireMetaEvents('meta', 'remove', $originNodeRef, $tag, $originalTag);
//                            else
//                                $this->NodeEvents->fireMetaEvents('meta', 'add', $originNodeRef, $tag, $originalTag);


                        $changedOne = true;
                    }
                }

            } else {

                // insert the meta tag
//                $newinsert = array(
//                    $tableid => $recordid,
//                    'Name' => $tag->getMetaName(),
//                    "{$datatype}Value" => $tag->getMetaValue(),
//                );

                try {

                    if($datatype == 'flag')
                        $affectedRows = $db->write("INSERT IGNORE INTO {$table} ({$tableid}, Name) Values ({$db->quote($recordid)}, {$db->quote($tag->getMetaName())})", DatabaseInterface::AFFECTED_ROWS);
                    else
                        $affectedRows = $db->write("INSERT INTO {$table} ({$tableid}, Name, {$datatypeCol}Value) Values ({$db->quote($recordid)}, {$db->quote($tag->getMetaName())}, {$db->quote($tag->getMetaValue())})", DatabaseInterface::AFFECTED_ROWS);

    //                $bulkTagInserts[$datatype][] = $newinsert;

    //                $db->insertRecord($table, $newinsert);

                    if($affectedRows > 0) {
                        $this->NodeEvents->fireMetaEvents('meta', 'add', $originNodeRef, $tag);
                        $changedOne = true;
                    }
                }catch(SQLDuplicateKeyException $dke)
                {

                    $where = "{$tableid} = {$db->quote($recordid)} AND Name = {$db->quote($tag->getMetaName())}";
                    $originalValue = $db->readField("SELECT {$datatypeCol}Value FROM {$table} WHERE {$where} LOCK IN SHARE MODE");

                    // update the meta tag
                    $affectedRows = $db->updateRecord($table, array( "{$datatypeCol}Value" => $tag->getMetaValue() ), $where);

                    if($affectedRows > 0) {
                        $this->NodeEvents->fireMetaEvents('meta', 'update', $originNodeRef, $tag, new Meta($tag->getMetaName(), $originalValue));
                        $changedOne = true;
                    }

                }

//                $this->NodeEvents->fireMetaEvents('meta', 'add', $originNodeRef, $tag);

            }

        }

//        if(!empty($bulkTagInserts))
//            foreach($bulkTagInserts as $datatype => $bulkInserts)
//                $db->bulkInsertRecords($db->quoteIdentifier($this->NodeDBMeta->getMetaTable($originNodeRef, $datatype)), $bulkInserts);

        return $changedOne;
    }

    public function migrateMetaStorage(DatabaseInterface $db, NodeRef $originNodeRef, $metaID, $oldDatatype, $newDatatype, $force = false)
    {


        $tableid = $this->NodeDBMeta->getPrimaryKey($originNodeRef);

        $oldTable = $db->quoteIdentifier($this->NodeDBMeta->getMetaTable($originNodeRef, $oldDatatype));
        $newTable = $db->quoteIdentifier($this->NodeDBMeta->getMetaTable($originNodeRef, $newDatatype));

        $oldDatatypeCol = $this->NodeDBMeta->getMetaDatatypeColumn($oldDatatype);
        $newDatatypeCol = $this->NodeDBMeta->getMetaDatatypeColumn($newDatatype);

        if($force) { // clobber existing data

            // move meta in database
            $affectedRows = $db->write("
                INSERT INTO {$newTable} ({$tableid}, Name, {$newDatatypeCol}Value)
                 SELECT {$tableid}, Name, {$oldDatatypeCol}Value FROM {$oldTable} WHERE Name = {$db->quote($metaID)}
                 ON DUPLICATE KEY UPDATE {$newDatatypeCol}Value = VALUES({$newDatatypeCol}Value)", DatabaseInterface::AFFECTED_ROWS);

        } else {
            // move meta in database
            $affectedRows = $db->write("
                INSERT IGNORE INTO {$newTable} ({$tableid}, Name, {$newDatatypeCol}Value)
                 SELECT {$tableid}, Name, {$oldDatatypeCol}Value FROM {$oldTable} WHERE Name = {$db->quote($metaID)}", DatabaseInterface::AFFECTED_ROWS);

        }

        return $affectedRows;
    }


    public function deprecateMeta(DatabaseInterface $db, NodeRef $originNodeRef, $metaID, $datatype)
    {

        $table = $db->quoteIdentifier($this->NodeDBMeta->getMetaTable($originNodeRef, $datatype));

        $affectedRows = $db->write("DELETE FROM {$table} WHERE Name = {$db->quote($metaID)}", DatabaseInterface::AFFECTED_ROWS);

        return $affectedRows;
    }

    public function fireAddMetaEvents(NodeRef $nodeRef)
    {
        return $this->fireAllMetaEvents($nodeRef, 'add');
    }

    public function fireRemoveMetaEvents(NodeRef $nodeRef)
    {
        return $this->fireAllMetaEvents($nodeRef, 'remove');
    }

    protected function fireAllMetaEvents(NodeRef $nodeRef, $action = 'add')
    {
        $db = $this->getConnectionForWrite($nodeRef);
        $recordid = $this->getRecordIDFromNodeRef($nodeRef);
        $metas =  $this->findMeta($db, $nodeRef, $recordid, 'all', true);
        foreach($metas as $meta)
        {
            $this->NodeEvents->fireMetaEvents('meta', $action, $nodeRef, $meta);
        }
    }

    /**
     * @param NodeRef $nodeRef
     * @param $metaID
     * @param int $value
     *
     * @throws NodeException
     * @throws SQLException
     * @throws Exception
     */
    public function incrementMeta(NodeRef $nodeRef, $metaID, $value = 1)
    {
        $metaID = ltrim($metaID, '#');

        $this->NodeEvents->fireNodeEvents(__FUNCTION__, '', $nodeRef, $metaID);

        $db = $this->getConnectionForWrite($nodeRef);
        $id = $this->getRecordIDFromNodeRef($nodeRef);

        // determine the meta storage
        $metaDef = $nodeRef->getElement()->getSchema()->getMetaDef($metaID);
        $datatype = $metaDef->getDatatype();
        $table = $db->quoteIdentifier($this->NodeDBMeta->getMetaTable($nodeRef, $datatype));
        $tableid = $this->NodeDBMeta->getPrimaryKey($nodeRef);
        $datatypeCol = $this->NodeDBMeta->getMetaDatatypeColumn($datatype);

        $value = intval($value);
        $newValue = false;
        $oldValue = false;
        $originalMeta = null;

        $maxValue = $this->getMaxIntForDatatype($datatype);
        $minValue = $this->getMinIntForDatatype($datatype);

        try {
            $affectedRows = $db->write("UPDATE {$table} SET {$datatypeCol}Value = LAST_INSERT_ID({$datatypeCol}Value+{$value}) WHERE {$tableid} = {$db->quote($id)} AND Name = {$db->quote($metaID)}", DatabaseInterface::AFFECTED_ROWS);
            if ($affectedRows == 1) {
                $newValue = intval($db->readField("SELECT LAST_INSERT_ID()"));
                $oldValue = $newValue - $value;
            }
        } catch (SQLException $e) {
            if ($e->getCode() != 22003 || strpos($e->getMessage(), 'Numeric value out of range') === false) {
                throw $e;
            }

            // must ask the DB for its current value
            $oldValue = intval($db->readField("SELECT {$datatypeCol}Value FROM {$table} WHERE {$tableid} = {$db->quote($id)} AND Name = {$db->quote($metaID)}"));
            $newValue = $oldValue + $value;

            // ensure the new values don't overflow (note that $value can be positive or negative and isn't necessarily 1)
            if ($newValue > $maxValue) {
                $newValue = $maxValue;
            } elseif ($newValue < $minValue) {
                $newValue = $minValue;
            }

            if ($newValue === $oldValue) {
                $affectedRows = 1;
            } else {
                $affectedRows = $db->write("UPDATE {$table} SET {$datatypeCol}Value = {$newValue} WHERE {$tableid} = {$db->quote($id)} AND Name = {$db->quote($metaID)}", DatabaseInterface::AFFECTED_ROWS);
            }
        } catch (Exception $e) {
            throw $e;
        }

        // new value didn't get set because the meta record hasn't been created yet.
        if ($newValue === false) {
            $newValue = $value;
            // ensure the new values don't overflow (note that $value can be positive or negative and isn't necessarily 1)
            if ($newValue > $maxValue) {
                $newValue = $maxValue;
            } elseif ($newValue < $minValue) {
                $newValue = $minValue;
            }
        }

        if ($affectedRows == 0) {
            $affectedRows = $db->write("INSERT INTO {$table} ({$tableid}, Name, {$datatypeCol}Value) Values ({$db->quote($id)}, {$db->quote($metaID)}, {$newValue}) ON DUPLICATE KEY UPDATE {$datatypeCol}Value = LAST_INSERT_ID({$datatypeCol}Value+{$value})", DatabaseInterface::AFFECTED_ROWS);

            // new record inserted
            if ($affectedRows == 1) {
                $metaEvent = 'add';
                $newMeta = new Meta($metaID, $newValue);
                $newMeta->setMetaStorageDatatype($metaDef->Datatype);
                $originalMeta = null;

            // duplicate key update encountered
            } else if ($affectedRows == 2) {
                $metaEvent = 'update';

                $newValue = intval($db->readField("SELECT LAST_INSERT_ID()"));
                $oldValue = $newValue - $value;

                $newMeta = new Meta($metaID, $newValue);
                $newMeta->setMetaStorageDatatype($metaDef->Datatype);

                $originalMeta = new Meta($metaID, $oldValue);
                $originalMeta->setMetaStorageDatatype($metaDef->Datatype);

            } else {
                throw new NodeException('Unable to increment meta ID ['.$metaID.'] on record ['.$nodeRef.']');
            }
        } else {
            $metaEvent = 'update';

            $newMeta = new Meta($metaID, $newValue);
            $newMeta->setMetaStorageDatatype($metaDef->Datatype);

            $originalMeta = new Meta($metaID, $oldValue);
            $originalMeta->setMetaStorageDatatype($metaDef->Datatype);
        }

        $this->NodeEvents->fireMetaEvents('meta', $metaEvent, $nodeRef, $newMeta, $originalMeta);
    }

    /**
     * @param NodeRef $nodeRef
     * @param $metaID
     * @param int $value
     *
     * @throws NodeException
     * @throws SQLException
     * @throws Exception
     */
    public function decrementMeta(NodeRef $nodeRef, $metaID, $value = 1)
    {
        $metaID = ltrim($metaID, '#');

        $this->NodeEvents->fireNodeEvents(__FUNCTION__, '', $nodeRef, $metaID);

        $db = $this->getConnectionForWrite($nodeRef);
        $id = $this->getRecordIDFromNodeRef($nodeRef);

        // determine the meta storage
        $metaDef = $nodeRef->getElement()->getSchema()->getMetaDef($metaID);
        $datatype = $metaDef->getDatatype();
        $table = $db->quoteIdentifier($this->NodeDBMeta->getMetaTable($nodeRef, $datatype));
        $tableid = $this->NodeDBMeta->getPrimaryKey($nodeRef);
        $datatypeCol = $this->NodeDBMeta->getMetaDatatypeColumn($datatype);

        $value = intval($value);
        $newValue = false;
        $oldValue = false;
        $originalMeta = null;

        $maxValue = $this->getMaxIntForDatatype($datatype);
        $minValue = $this->getMinIntForDatatype($datatype);

        try {
            $affectedRows = $db->write("UPDATE {$table} SET {$datatypeCol}Value = LAST_INSERT_ID({$datatypeCol}Value-{$value}) WHERE {$tableid} = {$db->quote($id)} AND Name = {$db->quote($metaID)}", DatabaseInterface::AFFECTED_ROWS);
            if ($affectedRows == 1) {
                $newValue = intval($db->readField("SELECT LAST_INSERT_ID()"));
                $oldValue = $newValue + $value;
            }
        } catch (SQLException $e) {
            if ($e->getCode() != 22003 || strpos($e->getMessage(), 'Numeric value out of range') === false) {
                throw $e;
            }

            // must ask the DB for its current value
            $oldValue = intval($db->readField("SELECT {$datatypeCol}Value FROM {$table} WHERE {$tableid} = {$db->quote($id)} AND Name = {$db->quote($metaID)}"));
            $newValue = $oldValue - $value;

            // ensure the new values don't overflow (note that $value can be positive or negative and isn't necessarily 1)
            if ($newValue > $maxValue) {
                $newValue = $maxValue;
            } elseif ($newValue < $minValue) {
                $newValue = $minValue;
            }

            if ($newValue === $oldValue) {
                $affectedRows = 1;
            } else {
                $affectedRows = $db->write("UPDATE {$table} SET {$datatypeCol}Value = {$newValue} WHERE {$tableid} = {$db->quote($id)} AND Name = {$db->quote($metaID)}", DatabaseInterface::AFFECTED_ROWS);
            }
        } catch (Exception $e) {
            throw $e;
        }

        // new value didn't get set because the meta record hasn't been created yet.
        if ($newValue === false) {
            $newValue = $value * -1;
            // ensure the new values don't overflow (note that $value can be positive or negative and isn't necessarily 1)
            if ($newValue > $maxValue) {
                $newValue = $maxValue;
            } elseif ($newValue < $minValue) {
                $newValue = $minValue;
            }
        }

        if ($affectedRows == 0) {
            $affectedRows = $db->write("INSERT INTO {$table} ({$tableid}, Name, {$datatypeCol}Value) Values ({$db->quote($id)}, {$db->quote($metaID)}, {$newValue}) ON DUPLICATE KEY UPDATE {$datatypeCol}Value = LAST_INSERT_ID({$datatypeCol}Value-{$value})", DatabaseInterface::AFFECTED_ROWS);

            // new record inserted
            if ($affectedRows == 1) {
                $metaEvent = 'add';
                $newMeta = new Meta($metaID, $newValue);
                $newMeta->setMetaStorageDatatype($metaDef->Datatype);
                $originalMeta = null;

            // duplicate key update encountered
            } else if ($affectedRows == 2) {
                $metaEvent = 'update';

                $newValue = intval($db->readField("SELECT LAST_INSERT_ID()"));
                $oldValue = $newValue + $value;

                $newMeta = new Meta($metaID, $newValue);
                $newMeta->setMetaStorageDatatype($metaDef->Datatype);

                $originalMeta = new Meta($metaID, $oldValue);
                $originalMeta->setMetaStorageDatatype($metaDef->Datatype);

            } else {
                throw new NodeException('Unable to decrement meta ID ['.$metaID.'] on record ['.$nodeRef.']');
            }
        } else {
            $metaEvent = 'update';

            $newMeta = new Meta($metaID, $newValue);
            $newMeta->setMetaStorageDatatype($metaDef->Datatype);

            $originalMeta = new Meta($metaID, $oldValue);
            $originalMeta->setMetaStorageDatatype($metaDef->Datatype);
        }

        $this->NodeEvents->fireMetaEvents('meta', $metaEvent, $nodeRef, $newMeta, $originalMeta);
    }

    /**
     * @param $datatype
     * @return int
     */
    protected function getMaxIntForDatatype($datatype)
    {
        $types = array('flag'        => 1,
                       'tiny'        => 255,
                       'int'         => 4294967295,
                       'long'        => 18446744073709551615,
                       'tiny-signed' => 127,
                       'int-signed'  => 2147483647,
                       'long-signed' => 9223372036854775807);

        if (array_key_exists($datatype,$types)) {
            return $types[$datatype];
        } else {
            return 1;
        }
    }

    /**
     * @param $datatype
     * @return int
     */
    protected function getMinIntForDatatype($datatype)
    {
        $types = array('flag'        => 0,
                       'tiny'        => 0,
                       'int'         => 0,
                       'long'        => 0,
                       'tiny-signed' => -128,
                       'int-signed'  => -2147483648,
                       'long-signed' => -9223372036854775808);

        if (array_key_exists($datatype,$types)) {
            return $types[$datatype];
        } else {
            return 0;
        }
    }
}