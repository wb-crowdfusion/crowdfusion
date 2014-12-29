<?php
/**
 * AbstractMySQLSearchIndex
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
 * @version     $Id: AbstractMySQLSearchIndex.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AbstractMySQLSearchIndex
 *
 * @package     CrowdFusion
 */
abstract class AbstractMySQLSearchIndex extends AbstractSearchIndex
{

    protected $SearchDataSource;
    protected $NodeService;

    public function setDTOHelper(DTOHelper $DTOHelper)
    {
        $this->DTOHelper = $DTOHelper;
    }


    public function setSearchDataSource(DataSourceInterface $SearchDataSource){
        $this->dsn = $SearchDataSource;
    }

    public function getWriteConnection()
    {
        return $this->dsn->getConnectionsForReadWrite()->offsetGet(0)->getConnection();
    }

    public function getReadConnection()
    {
        return $this->dsn->getConnectionsForRead()->offsetGet(0)->getConnection();
    }

    public function setElementService(ElementService $ElementService)
    {
        $this->ElementService = $ElementService;
    }

    protected abstract function columnScores();
    protected abstract function executeIndexQuery(DatabaseInterface $db, DTO $dto, $s, $searchSafe, $maxresults = 500);
    protected abstract function buildIndexArray(Node $node);

    protected function readReindexedNodes($nodeRefs)
    {
        return $this->NodeService->multiGet($nodeRefs, new NodePartials('all', 'fields', 'fields'), $forceReadWrite = true, $allowDeleted = true);
    }

    public function reindexAll()
    {
        $nodes = $this->readReindexedNodes($this->markedForReindex);

        $db = $this->getWriteConnection();

        foreach((array)$nodes as $node)
        {
            $nodeRef = $node->getNodeRef();
            $this->Logger->debug('Reindexing ['.$nodeRef.']');

            $array = $this->buildIndexArray($node);

            $indexID = $db->readField("SELECT
                    {$this->indexTableID}
                FROM {$db->quoteIdentifier($this->indexTable)}
                WHERE ElementID = {$db->quote($nodeRef->getElement()->getElementID())}
                AND Slug = {$db->quote($nodeRef->getSlug())}
            ");

            if(!empty($indexID)){

                $db->updateRecord($db->quoteIdentifier($this->indexTable), $array, "{$this->indexTableID} = {$db->quote($indexID)}");

            } else {
                $array['CreationDate'] = $this->DateFactory->newStorageDate();

                $db->insertRecord($db->quoteIdentifier($this->indexTable), $array);

            }
        }

        foreach((array)$this->markedForDeletion as $nodeRef)
        {
            $this->Logger->debug('Deleting from index ['.$nodeRef.']');

            $db->deleteRecord($db->quoteIdentifier($this->indexTable),
                "ElementID = {$db->quote($nodeRef->getElement()->getElementID())}
                AND Slug = {$db->quote($nodeRef->getSlug())}");

        }
    }


    public function search(NodeQuery $dto)
    {


        $searchSafe = $dto->getParameter('SearchKeywords');
        $searchRaw = $dto->getParameter('SearchKeywordsRaw')==null?preg_replace("/[\(\)\/\*\[\]\?]+/",'',$searchSafe):$dto->getParameter('SearchKeywordsRaw');
        $searchThreshold = $dto->getParameter('SearchThreshold');

        $maxresults = $dto->getParameter('SearchMaxResults')!=null?$dto->getParameter('SearchMaxResults'):500;
        $sortCol = $dto->getParameter('SearchSort')!=null?$dto->getParameter('SearchSort'):'relevance';
        $sortDir = $dto->getParameter('SearchSortDirection')!=null?$dto->getParameter('SearchSortDirection'):'desc';


//        $stopwords = array ('/\bof\b/','/\ba\b/','/\band\b/','/\bthe\b/');
//        $s = str_replace('+','\+',$searchRaw);
//        $s = preg_replace($stopwords,' ',$s);
        //$s = preg_replace("/\s+/",' ',$s);
        $s = preg_replace("/\s+/",' ',$searchRaw);

        // execute query
        $rows = $this->executeIndexQuery($this->getReadConnection(), $dto, $s, $searchSafe, $maxresults);

        if (sizeof($rows) == 0)
            return $dto;

        $datascore = $this->columnScores();
        $words = StringUtils::wordsTokenized($s);

        $scores = array();
        $sorts = array();
        $resultingNodeRefs = array();

        $preg_s = preg_quote(str_replace(array('*', '+', '-'), '', $s), '/');

        $ct = 0;
        foreach ($rows as $row) {


            $negative_match = FALSE;
            $found = array();
            if($ct++ > $maxresults) { break; }
            $score = $row['Score'];

            if(preg_match("/^$preg_s/i",$row['Title'])) {
                $score += 10;
            } elseif (sizeof($words) == 1 && preg_match("/\b".$preg_s."/i",$row['Title'])) {
                $score += 5;
            }

            foreach ($datascore as $param => $val) {

                if (!empty($s) && !empty($row[$param]) && preg_match("/\b".$preg_s."\b/si"," ".$row[$param]." ")) {
                    $score += $val;
                }

                $m[$param] = 0;
                foreach ($words as $word) {
                    if (preg_match("/^-(.{2,})/",$word,$m) && preg_match("/\b$m[1]\b/is",$row[$param])) {
                        $negative_match = TRUE;
                    }
                    $preg_word = preg_quote($word, '/');
                    if (!empty($word) && preg_match("/\b$preg_word\b/si"," ".$row[$param]." ")) {
                        if(!isset($m[$param])) $m[$param] = 0;
                        $m[$param]++;
                        $found[$word] = 1;
                        $score += ($val/2);
                    }
                }

                if (isset($m[$param]) && $m[$param] == sizeof($words))  {
                    $score += ($val *3);
                }

            }

            if(sizeof($found) > 0)
                $score = $score * (sizeof($found) / sizeof($words));

            $rowElement = $this->ElementService->getByID($row['ElementID']);
            $rowSlug = $row['Slug'];

            $rowNodeRef = new NodeRef($rowElement, $rowSlug);

            $rawscores[''.$rowNodeRef] = $score;
            if (!$negative_match) {
                if(empty($searchThreshold) || $score > $searchThreshold) {
                    $resultingNodeRefs[''.$rowNodeRef] = $rowNodeRef;

                    $scores[''.$rowNodeRef] = $score;
                    $sorts[''.$rowNodeRef] = $sortCol != 'relevance'?$row[$sortCol]:$score;
                }
            }
        }

        reset($scores);

        if (sizeof($sorts) == 0)
            return $dto;

        if(strtolower($sortDir) == 'asc')
            asort($sorts);
        else
            arsort($sorts);

//        $this->Logger->debug($resultingNodeRefs);
//        $this->Logger->debug($sorts);

        $results = ArrayUtils::arraySortUsingKeys($resultingNodeRefs, array_keys($sorts));

        $dto->setParameter('NodeRefs.in', $results);
        $dto->setParameter('NodeRefs.fullyQualified', true);
        $dto->setOrderBy('NodeRefs');

        $results = $this->NodeService->findAll($dto)->getResults();

        foreach($results as $key => &$node){
            $nodeRef = $node['NodeRef'];
            $node['SearchScore'] = $scores[''.$nodeRef];
//            error_log($node['Title'].' ('.$node['SearchScore'].')');
        }
//        $keys = array_map('strval', $sorts);
        $dto->setResults($results);

        return $dto;
    }

}