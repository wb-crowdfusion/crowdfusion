<?php
/**
 * NodeImportExportService
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
 * @version     $Id: NodeImportExportService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeImportExportService
 *
 * @package     CrowdFusion
 */
class NodeImportExportService
{
    protected $NodeMapper;
    protected $NodeBinder;



    public function setElementService(ElementService $ElementService)
    {
        $this->ElementService = $ElementService;
    }

    public function setNodeService(NodeService $NodeService)
    {
        $this->NodeService = $NodeService;
    }

    public function setNodeMapper(NodeMapper $NodeMapper)
    {
        $this->NodeMapper = $NodeMapper;
    }

    public function setNodeEvents(NodeEvents $NodeEvents)
    {
        $this->NodeEvents = $NodeEvents;
    }

    public function setNodeBinder(NodeBinder $NodeBinder)
    {
        $this->NodeBinder = $NodeBinder;
    }

    public function setNodeTagsDAO(NodeTagsDAO $NodeTagsDAO)
    {
        $this->NodeTagsDAO = $NodeTagsDAO;
    }

    public function setTransactionManager(TransactionManager $TransactionManager){
        $this->TransactionManager = $TransactionManager;
    }

    public function export($file, $elements = 'all', $fireEvents = false, $ignoreInTags = false)
    {

        if(file_exists($file))
            throw new Exception('Cannot write export file ['.$file.'], file exists');

        if(!$fireEvents)
            $this->NodeEvents->disableEvents();

        $this->NodeTagsDAO->setNodeDatabaseTagMergeLimit(-1);
        $log = '';

        if($elements == 'all')
        {

            $elements = $this->ElementService->findAll()->getResults();

        } else {

            $elementSlugs = StringUtils::smartExplode($elements);
            $elements = array();
            foreach($elementSlugs as $eSlug)
                $elements[] = $this->ElementService->getBySlug($eSlug);
        }

        foreach($elements as $element)
        {
            $offset = 0;
            $nq = new NodeQuery();
            $nq->setParameter('Elements.in', $element->getSlug());
            $nq->setParameter('Meta.select', 'all');
            $nq->setParameter('OutTags.select', 'all');
            if(!$ignoreInTags)
                $nq->setParameter('InTags.select', 'all');
            $nq->setLimit(1000);
            $nq->setOffset($offset);
            $nq->asObjects();
            $nq->isRetrieveTotalRecords(true);

            $nq = $this->NodeService->findAll($nq, true);
            $nodes = $nq->getResults();
            $tCount = $nq->getTotalRecords();
            $count = 0;

            while(count($nodes) > 0)
            {

                foreach($nodes as $node)
                {

                    FileSystemUtils::safeFilePutContents($file, json_encode($this->NodeMapper->nodeToInputArray($node))."\n",FILE_APPEND);

                    ++$count;
                }

                $offset += 1000;

                $nq = new NodeQuery();
                $nq->setParameter('Elements.in', $element->getSlug());
                $nq->setParameter('Meta.select', 'all');
                $nq->setParameter('OutTags.select', 'all');
                if(!$ignoreInTags)
                    $nq->setParameter('InTags.select', 'all');
                $nq->setLimit(1000);
                $nq->setOffset($offset);
                $nq->asObjects();

                $nodes = $this->NodeService->findAll($nq)->getResults();
            }

            $log .= 'Exported '.$count.' of '.$tCount.' '.$element->getSlug()." nodes.\n";

        }

        if(!$fireEvents)
            $this->NodeEvents->enableEvents();

        return $log;

    }

    public function import($file, $fireEvents = false, $ignoreInTags = false)
    {

        if(!file_exists($file))
            throw new Exception('Import file not found ['.$file.']');

        if(!$fireEvents)
            $this->NodeEvents->disableEvents();

        $this->NodeTagsDAO->setNodeDatabaseTagMergeLimit(-1);
        $log = '';

        $errors = new Errors();

        $acount = array();
        $ecount = array();

        $addedNodeRefs = array();

        $handle = @fopen($file, "r");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle);

                $nodeArray = json_decode($buffer, true);
                if(empty($nodeArray))
                    continue;

                $node = $this->bindArray($nodeArray, $errors);

                if(!$this->NodeService->refExists($node->getNodeRef()))
                {
                    $this->NodeService->add($node);
                    $addedNodeRefs[] = ''.$node->getNodeRef();
                    @++$acount[$node->getNodeRef()->getElement()->getSlug()];
                } else {
                    $addedNodeRefs[] = ''.$node->getNodeRef();
                    @++$ecount[$node->getNodeRef()->getElement()->getSlug()];
                }
            }
            fclose($handle);
        } else {
            throw new Exception('Unable to read import file ['.$file.']');
        }

        foreach($acount as $name => $num)
            $log .= "Stubbed ".$num." ".$name." nodes.\n";
        foreach($ecount as $name => $num)
            $log .= "Updating ".$num." ".$name." nodes.\n";


        $count = array();

        $handle = @fopen($file, "r");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle);

                $nodeArray = json_decode($buffer, true);
                if(empty($nodeArray))
                    continue;

                $node = $this->bindArray($nodeArray, $errors, true, $ignoreInTags);

                if(in_array(''.$node->getNodeRef(), $addedNodeRefs))
                {
                    $this->NodeService->edit($node);
                    @++$count[$node->getNodeRef()->getElement()->getSlug()];
                }
            }
            fclose($handle);
        }

        foreach($count as $name => $num)
            $log .= "Imported ".$num." ".$name." nodes.\n";

        if(!$fireEvents)
            $this->NodeEvents->enableEvents();

        return $log;
    }

    protected function bindArray($nodeArray, $errors, $bindAll = false, $ignoreInTags = false)
    {
        $nodeRef = new NodeRef(
            $this->ElementService->getBySlug($nodeArray['ElementSlug']),
            $nodeArray['Slug']);

        $node = $nodeRef->generateNode();
        $this->NodeMapper->defaultsOnNode($node);

        $this->NodeBinder->bindPersistentFields($node, $errors, $nodeArray, $nodeArray);

        if($bindAll) {
            if($ignoreInTags)
            {
                $this->NodeBinder->bindAllMetaForNode($node, $errors, $nodeArray, $nodeArray);

                $schema = $node->getNodeRef()->getElement()->getSchema();

                foreach($schema->getTagDefs() as $tagDef) {
                    if($tagDef->Direction == 'out')
                        $this->NodeBinder->bindOutTags($node, $errors, $nodeArray, $nodeArray, $tagDef->Id, true);
                }

            } else {
                $this->NodeBinder->bindAllTagsForNode($node, $errors, $nodeArray, $nodeArray, $increasePartials = true);
            }
        } else
            $this->NodeBinder->bindAllMetaForNode($node, $errors, $nodeArray, $nodeArray);

        $errors->throwOnError();
        return $node;
    }


    public function populateDefaults($elements = 'all')
    {

        $log = '';

        if($elements == 'all')
        {

            $elements = $this->ElementService->findAll()->getResults();

        } else {

            $elementSlugs = StringUtils::smartExplode($elements);
            $elements = array();
            foreach($elementSlugs as $eSlug)
                $elements[] = $this->ElementService->getBySlug($eSlug);
        }

        foreach($elements as $element)
        {
            $offset = 0;
            $nq = new NodeQuery();
            $nq->setParameter('Elements.in', $element->getSlug());
            $nq->setParameter('Meta.select', 'all');
            $nq->setLimit(1000);
            $nq->setOffset($offset);
            $nq->asObjects();
            $nq->isRetrieveTotalRecords(true);

            $nq = $this->NodeService->findAll($nq, true);
            $nodes = $nq->getResults();
            $tCount = $nq->getTotalRecords();
            $count = 0;

            while(count($nodes) > 0)
            {

                foreach($nodes as $node)
                {
                    $this->NodeMapper->defaultsOnNode($node);

                    $this->NodeService->edit($node);

                    ++$count;
                }

                $offset += 1000;

                $nq = new NodeQuery();
                $nq->setParameter('Elements.in', $element->getSlug());
                $nq->setParameter('Meta.select', 'all');
                $nq->setLimit(1000);
                $nq->setOffset($offset);
                $nq->asObjects();

                $nodes = $this->NodeService->findAll($nq)->getResults();
            }

            $log .= 'Updated defaults on '.$count.' of '.$tCount.' '.$element->getSlug()." nodes.\n";

        }

        return $log;
    }
    public function migrateInTags(NodeRef $sourceRef, $mergeSlug,$limit=null){
        $targetRef          = new NodeRef($sourceRef->getElement(),$mergeSlug);
        $sourceNode         = $this->NodeService->getByNodeRef($sourceRef, new NodePartials('','','all'));

        $batchSize = 1000;

        $inTags = $sourceNode->getInTags();

        //Get the schema to ensure tags are removed from the element in which they were defined.
        $schema         = $sourceRef->getElement()->getSchema();
        $tagsMigrated   = 0;
        while($inTags && ($limit === null || $tagsMigrated < $limit)){  //while array is not empty
            $inTag = array_pop($inTags);
            if($schema->hasTagDef($inTag->TagRole)){
                // If the InTag exists in the source element schema
                // remove it from the source node and tag add it to the target node
                $this->NodeService->removeInTag($sourceRef,$inTag);
                $this->NodeService->addInTag($targetRef,$inTag);
            }
            else{
                // If the InTag does not exist in the source element schema
                // its converse OutTag must exist in the tagged node element schema
                // Remove OutTag from the tagged node and create a new OutTag to the targetNode
                $taggedNodeRef  = $inTag->TagLinkNode->getNodeRef();

                $outTag         = new Tag($sourceRef, $sourceRef->getSlug() ,$inTag->TagRole);
                $this->NodeService->removeOutTag($taggedNodeRef,$outTag);

                $mergeOutTag    = new Tag($targetRef, $targetRef->getSlug() ,$inTag->TagRole);
                $this->NodeService->addOutTag($taggedNodeRef,$mergeOutTag);
            }

            $tagsMigrated++;
            if ($tagsMigrated % $batchSize == 0 ){
                 $this->TransactionManager->commit()->begin();
            }
        }
        $this->TransactionManager->commit()->begin();

        return $tagsMigrated;
    }
}