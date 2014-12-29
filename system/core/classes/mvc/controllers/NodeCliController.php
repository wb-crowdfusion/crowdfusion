<?php
/**
 * Command Line controller for Node interactions contains import and export calls.
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
 * @version     $Id: NodeCliController.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Command Line controller for Node interactions contains import and export calls.
 *
 * @package     CrowdFusion
 */
class NodeCliController extends AbstractCliController
{
    protected $NodeService;

    public function setVersionService(VersionService $VersionService) {
        $this->VersionService = $VersionService;
    }

    public function setNodeImportExportService(NodeImportExportService $NodeImportExportService)
    {
        $this->NodeImportExportService = $NodeImportExportService;
    }

    public function setNodeService(NodeServiceInterface $NodeService)
    {
        $this->NodeService = $NodeService;
    }

    public function setElementService(ElementService $ElementService)
    {
        $this->ElementService = $ElementService;
    }

    public function setAspectService(AspectService $AspectService) {
        $this->AspectService = $AspectService;
    }

    public function setNodeRefService(NodeRefService $NodeRefService)
    {
        $this->NodeRefService = $NodeRefService;
    }

    public function setNodeDBMeta(NodeDBMeta $NodeDBMeta)
    {
        $this->NodeDBMeta = $NodeDBMeta;
    }

    protected function export()
    {

        $file = $this->Request->getRequiredParameter('file');
        $elements = $this->Request->getParameter('elements');
        if(empty($elements))
            $elements = 'all';

        $events = $this->Request->getParameter('fireEvents');

        $ignoreInTags = $this->Request->getParameter('ignoreInTags');

        echo $this->NodeImportExportService->export($file, $elements, StringUtils::strToBool($events), StringUtils::strToBool($ignoreInTags));

    }

    protected function import()
    {

        $file = $this->Request->getRequiredParameter('file');
        $events = $this->Request->getParameter('fireEvents');
        $ignoreInTags = $this->Request->getParameter('ignoreInTags');

        echo $this->NodeImportExportService->import($file, StringUtils::strToBool($events), StringUtils::strToBool($ignoreInTags));

    }

    protected function createTables()
    {

        $elements = $this->ElementService->findAll()->getResults();

        foreach($elements as $element)
        {
            echo "Creating tables for [".$element->Slug."]\n";
            $this->NodeService->createDBSchema($element);
        }

        echo "done\n";
    }

    protected function countsUpdate()
    {

        $interval = $this->Request->getParameter('interval');
        if(empty($interval)) $interval = 200;

        $offset = $this->Request->getParameter('offset');
        if(empty($offset)) $offset = 0;

        $countField = $this->Request->getRequiredParameter('originMetaId');
        $elementField = $this->Request->getRequiredParameter('originElement');
        $tagField = $this->Request->getRequiredParameter('countMatchPartial');

        $statusCheck = $this->Request->getParameter('status');

        $element = $this->ElementService->getBySlug($elementField);

        if(empty($element))
        {
            echo "Element not found for slug [{$elementField}]\n";
            return;
        }

        $taggedPartial = new TagPartial($tagField);
        $tagRole = $taggedPartial->TagRole;

        $tagDef = $element->getSchema()->getTagDef($tagRole);

        if (!empty($taggedPartial['TagElement']))
            $taggedElement = $taggedPartial['TagElement'];
        else if (!empty($taggedPartial['TagAspect']))
            $taggedElement = '@'.$taggedPartial['TagAspect'];
        else
            $taggedElement = '@'.$tagDef->Partial->TagAspect;

        $nq = new NodeQuery();
        $nq->setParameter('NodeRefs.only', true);
        $nq->setParameter('Elements.in', $elementField);

        $nq->setLimit($interval);
        $nq->setOffset($offset);

        $nq = $this->NodeService->findAll($nq, true);
        $nodes = $nq->getResults();
        //$tCount = $nq->getTotalRecords();
        $count = 0;

        while(count($nodes) > 0)
        {

        	foreach($nodes as $nodeRef)
            {
				$nq2 = new NodeQuery();
				$nq2->setParameter('Elements.in',$taggedElement);
				$nq2->setParameter('Count.only',true);

                if(!empty($statusCheck))
                    $nq2->setParameter('Status.eq', $statusCheck);

                if($tagDef->Direction == 'out')
                    $nq2->setParameter('InTags.exist',$nodeRef->getRefURL().'#'.$tagRole);
                else
                    $nq2->setParameter('OutTags.exist',$nodeRef->getRefURL().'#'.$tagRole);

				$count2 = $this->NodeService->findAll($nq2)->getTotalRecords();

				$this->NodeService->updateMeta($nodeRef, $countField, $count2);
				echo "$count [".$nodeRef."] $countField = $count2\n";

				$this->TransactionManager->commit()->begin();
                unset($nodeRef);

                ++$count;
            }

            $offset = ($offset + $interval);

            unset($nodes);

            $nq->setLimit($interval);
            $nq->setOffset($offset);
            $nq->clearResults();

            $nq = $this->NodeService->findAll($nq, true);
        	$nodes = $nq->getResults();

        }


        echo "done\n";


    }

    protected function copyNode()
    {
        $nodeRef = $this->Request->getRequiredParameter('noderef');
        $nodeRef = $this->NodeRefService->parseFromString($nodeRef);

        $newElement = $this->Request->getRequiredParameter('element');

        $delete = $this->Request->getParameter('delete');

        $newNodeElement = $this->ElementService->getBySlug($newElement);

        $this->NodeService->copy($nodeRef, $newNodeElement);

        echo "Copied {$nodeRef->getElement()->getName()} node [{$nodeRef->Slug}] to element [{$newNodeElement->getName()}]\n";

        if(!empty($delete) && StringUtils::strToBool($delete) == true) {
            $this->NodeService->delete($nodeRef);
            echo "Deleted {$nodeRef->getElement()->getName()} node [{$nodeRef->Slug}]\n";
        }

//        echo "done\n";

    }

    protected function renameNode()
    {
        $nodeRef = $this->Request->getRequiredParameter('noderef');
        $nodeRef = $this->NodeRefService->parseFromString($nodeRef);

        $newSlug = $this->Request->getRequiredParameter('newslug');

        $this->NodeService->rename($nodeRef, $newSlug);

        echo "Renamed {$nodeRef} node to [{$nodeRef->Slug}]\n";
    }


    protected function populateDefaults()
    {

        $elements = $this->Request->getParameter('elements');
        if(empty($elements))
            $elements = 'all';

        echo $this->NodeImportExportService->populateDefaults($elements);
    }


    protected function migrateMetaStorage()
    {

        $aspect = $this->Request->getRequiredParameter('aspect');
        $meta = $this->Request->getRequiredParameter('meta');
        $oldDatatype = $this->Request->getRequiredParameter('olddatatype');
        $newDatatype = $this->Request->getRequiredParameter('newdatatype');

        $force = StringUtils::strToBool($this->Request->getParameter('force'));

        $elements = $this->ElementService->findAllWithAspect($aspect);
        foreach($elements as $element)
        {

            $rowsAffected = $this->NodeService->migrateMetaStorage(new NodeRef($element),
                $meta, $oldDatatype, $newDatatype, $force);

            echo "Migrated {$rowsAffected} [$meta] on [{$element->Slug}] from [{$oldDatatype}] to [{$newDatatype}]\n";

        }


    }


    protected function deprecateMeta()
    {

        $aspect = $this->Request->getRequiredParameter('aspect');
        $meta = $this->Request->getRequiredParameter('meta');
        $datatype = $this->Request->getRequiredParameter('datatype');

        $elements = $this->ElementService->findAllWithAspect($aspect);
        foreach($elements as $element)
        {

            $rowsAffected = $this->NodeService->deprecateMeta(new NodeRef($element),
                $meta, $datatype);

            echo "Deprecated {$rowsAffected} [$meta] on [{$element->Slug}] rows from [{$datatype}]\n";
        }
    }


    /**
     * Provides functionality to migrate intags between nodes
     * @param (Required) noderef - Node Reference string of the element to delete (element-slug:node-slug)
     * @param (Required) mergeslug - Slug of the node to merge tags into, or null if no tag merge is desired
     * @param (optional) limit - number of tags to migrate. Will migrate all tags if omitted.
     *
     * @return void
     */
    protected function migrateInTags(){
        $nodeRef    = $this->Request->getRequiredParameter('noderef');
        $nodeRef    = $this->NodeRefService->parseFromString($nodeRef);

        if (!$this->NodeService->refExists($nodeRef)){
            echo 'Error - No Action Taken: Record not found for NodeRef: '.$nodeRef."\n";
            return;
         }

        $mergeSlug      = $this->Request->getRequiredParameter('mergeslug');
        $mergeNodeRef   = new NodeRef($nodeRef->getElement(),$mergeSlug);

        if (!empty($mergeSlug) && !$this->NodeService->refExists($mergeNodeRef)){
            echo 'Error - No Action Taken: Record not found for Merge Slug: '.$mergeSlug." \n";
            return;
         }
        // maximum number of intags to migrate
        $limit = $this->Request->getParameter('limit');
        if($limit !== null){
            if (!is_numeric($limit) || $limit != floor($limit)){
                echo 'Error - The Value of limit parameter must be an integer'.PHP_EOL;
                return;
            }
        }

       try{
           echo 'Migrating In Tags ..... ' . PHP_EOL;
           $tagsMigrated = $this->NodeImportExportService->migrateInTags($nodeRef, $mergeSlug,$limit);
           echo 'Done: Migrated '.$tagsMigrated . ' Tags' . PHP_EOL;
        }
        catch (Exception $e){
            echo "Exception caught: ".$e->getMessage()."\n";
            // if an error occurs, bail - we don't want to get stuck in a
            // loop if we can't delete stuff for some reason
            echo "Exiting, please correct problem and re-run\n";
            return;
        }
    }
    /**
     * Provides delete functionality for Nodes
     * @param (Required) noderef - Node Reference string of the element to delete (element-slug:node-slug)
     * @param (Required) mergeslug - Slug of the node to merge tags into, or null if no tag merge is desired
     * @param (Required) migrateintags - true to migrate tags before deleting node, false to use delete method's native tag merge
     * @return void
     */
    protected function delete()
    {
        // If migrate inTags is set to true make sure node has no intags before deleting
        // Defaults to true
        $migrateInTags =$this->Request->getRequiredParameter('migrateintags');
        if ($migrateInTags != 'true' && $migrateInTags != 'false') {
            echo 'Error - No Action Taken: value of migrateintags must be true or false: '."\n"; return;
            return;
        }
        $migrateInTags = StringUtils::strToBool($migrateInTags);

        $nodeRef = $this->NodeRefService->parseFromString($this->Request->getRequiredParameter('noderef'));

        if (!$this->NodeService->refExists($nodeRef)){
            echo 'Error - No Action Taken: Record not found for NodeRef: '.$nodeRef."\n";
            return;
         }
        $mergeSlug = $this->Request->getRequiredParameter('mergeslug');
        if ($mergeSlug == 'null')
            $mergeSlug = null;
        $mergeNodeRef = new NodeRef($nodeRef->getElement(),$mergeSlug);

        if ($mergeSlug != null && !$this->NodeService->refExists($mergeNodeRef)){
            echo 'Error - No Action Taken: Record not found for Merge Slug: '.$mergeSlug." \n";
            return;
         }

        if ($migrateInTags && $mergeSlug === null ){
            echo 'Error - No Action Taken: you must provide a Merge Slug in order to migrate in tags'. " \n";
            return;
        }

        try{
            if($migrateInTags){
                echo 'Begining Tag Migration '. PHP_EOL;
                $tagsMigrated = $this->NodeImportExportService->migrateInTags($nodeRef, $mergeSlug);
                echo $tagsMigrated. ' Tags Migrated to '.$mergeSlug. PHP_EOL. PHP_EOL;
            }

            echo 'Begining Node Delete '. PHP_EOL;
            $this->NodeService->delete($nodeRef, $mergeSlug);

            echo "Node ". $nodeRef .  "  Successfully Deleted. \n";
            if (!empty($mergeSlug))
                echo "And Tags transfered to ".$mergeSlug.". \n";
        }
        catch (Exception $e){
            echo "Exception caught: ".$e->getMessage()."\n";
            // if an error occurs, bail - we don't want to get stuck in a
            // loop if we can't delete stuff for some reason
            echo "Exiting, please correct problem and re-run\n";
            return;
        }
    }
    /**
     * Deletes all nodes for specified elements.
     *
     * @param elements A comma delimited list of the elements to delete nodes in
     * @param offset An integer value for the offset to begin deleting nodes from
     *
     * @return void
     */
    protected function deleteAll()
    {
        $elements = $this->Request->getRequiredParameter('elements');

        $interval = 1000;

        $elementSlugs = StringUtils::smartExplode($elements);
        $elements = array();
        foreach($elementSlugs as $eSlug)
            $elements[] = $this->ElementService->getBySlug($eSlug);

        foreach($elements as $element)
        {
            $offset = 0;

            if($this->Request->getParameter('offset') != null)
                $offset = $this->Request->getParameter('offset');

            echo 'Deleting nodes for element: '.$element."\n";

            while(true)
            {
                $count = 0;

                // commit any pending transactions and begin new one
                $this->TransactionManager->commit()->begin();

                $nq = new NodeQuery();
                $nq->setParameter('NodeRefs.only', true);
                $nq->setParameter('Elements.in', $element->getSlug());
                $nq->setLimit($interval);
                $nq->setOffset($offset);

                $nq = $this->NodeService->findAll($nq, true);
                $nodeRefs = $nq->getResults();
                $tCount = $nq->getTotalRecords();

                if(empty($nodeRefs))
                    break;

                foreach($nodeRefs as $nodeRef)
                {
                    try {
                        $this->NodeService->delete($nodeRef);
                        echo ($count+1).". ".$nodeRef->getSlug()." deleted\n";
                        if(($count+1) % 10 == 0) {
                            $this->TransactionManager->commit()->begin();
                            echo "commit\n";
                        }
                    } catch(Exception $e) {
                        echo "Exception caught: ".$e->getMessage()."\n";
                        // if an error occurs, bail - we don't want to get stuck in a
                        // loop if we can't delete stuff for some reason
                        echo "Exiting, please correct problem and re-run\n";
                        return;
                    }

                    ++$count;
                }

                echo 'Deleted '.$count.' of '.$tCount.' '.$element->getSlug()." nodes.\n";
            }
        }
    }

    protected function purgeAll()
    {
        $interval = 1000;

        // Allow the caller to specify a delay in ms between purge calls.
        // (to keep database load to a minimum if running this over a long period of time...)
        $delay = $this->Request->getParameter('delay');
        if (!empty($delay)) {
            $delay = intval($delay) * 1000;
        }

        $elements = array();
        $elementSlugs = $this->Request->getRequiredParameter('elements');
        if ($elementSlugs == 'all') {
            $elements = $this->ElementService->findAll()->getResults();
        }
        else {
            $elementSlugs = StringUtils::smartExplode($elementSlugs);
            foreach($elementSlugs as $slug) {
                $elements[] = $this->ElementService->getBySlug($slug);
            }
        }

        // Support passing a list of element slugs that will not be purged, even if the "elements" attribute is "all".
        $ignoreElementSlugs = $this->Request->getParameter('ignore_elements');
        if (!empty($ignoreElementSlugs)) {

            $ignoreElementSlugs = StringUtils::smartExplode($ignoreElementSlugs);
            foreach($ignoreElementSlugs as $slug) {
                // Do this step just to validate that the user has passed in real element slugs and not made any typos.
                $this->ElementService->getBySlug($slug);
            }
        }


        foreach($elements as $element)
        {
            // Skip over any elements we've been told to ignore.
            if (!empty($ignoreElementSlugs) && in_array($element->getSlug(), $ignoreElementSlugs))
                continue;

            $nq = new NodeQuery();
            $nq->setParameter('NodeRefs.only', true);
            $nq->setParameter('Elements.in', $element->getSlug());
            $nq->setParameter('Status.eq', 'deleted');
            $nq->setLimit($interval);

            $nq = $this->NodeService->findAll($nq, true);
            $nodes = $nq->getResults();
            $count = 0;

            while(count($nodes) > 0)
            {

                foreach($nodes as $nodeRef)
                {
                    ++$count;

                    try {
                        $this->NodeService->purge($nodeRef);
                        echo $count, ". ".$nodeRef, " purged\n";
                        if(($count) % 10 == 0) {
                            $this->TransactionManager->commit()->begin();
                            echo "commit\n";
                        }
                    } catch(Exception $e) {
                        echo "Exception caught: ".$e->getMessage()."\n";
                    }

                    unset($nodeRef);

                    if ($delay > 0) {
                        usleep($delay);
                    }
                }

                $this->TransactionManager->commit()->begin();

                $nq->clearResults();

                $nodes = $this->NodeService->findAll($nq, true)->getResults();
            }
        }
    }

    protected function interactive()
    {
        echo "\nWelcome to the Crowd Fusion Node Database Monitor.  Commands end with ; or newline.\n";
        echo "Server version: ".$this->VersionService->getCrowdFusionVersion()."\n";
        echo "\n";
        echo "Type 'help' for help. Type 'exit' to quit.\n";

        $codeBuffer = null;
        $history = array();

        do {

            echo "\ncf> ";

            /*
            $ans = strtolower( trim( `bash -c "read -n3 KEY; echo -n \\\$KEY | grep '\[A'"` ) );
            $ans = bin2hex($ans);
            if($ans == '1b5b61')
                echo "up arrow\n\n";
            //print_r(bin2hex($ans));
            exit;
            $ch = fgetc(STDIN);
            var_dump(ord($ch));
            exit;
            */

            $input = trim(fgets(STDIN));
            $input = rtrim($input,';');

            try {

                if($input != 'exit' && $input != 'quit') {

                    $parts = preg_split('/\s+/',$input);

                    $action = strtolower($parts[0]);

                    //*********************************************
                    //*** RUN *************************************
                    //*********************************************
                    if($action == 'run') {

                        if(empty($history)) {
                            $input = 'hist';
                        } else {
                            $input = $history[0];
                            if(isset($parts[1])) {
                                if(isset($history[intval($parts[1])-1])) {
                                    $input = $history[intval($parts[1])-1];
                                } else {
                                    $input = 'hist';
                                }
                            }
                        }

                        $parts = preg_split('/\s+/',$input);
                        $action = strtolower($parts[0]);
                    }

                    //*********************************************
                    //*** HISTORY *********************************
                    //*********************************************
                    if($action == 'hist' || $action == 'history') {

                        if(empty($history)) {
                            echo "Empty history\n";
                        } else {

                            $num = 10;
                            if(isset($parts[1]) && is_numeric($parts[1]))
                                $num = intval($parts[1]);

                            for($i = 0; $i < $num; $i++) {
                                if(isset($history[$i]))
                                    echo " ".($i+1).") ".$history[$i]."\n";
                            }
                        }

                    }
                    //*********************************************
                    //*** SHOW ************************************
                    //*********************************************
                    else if($action == 'show') {
                        $target = isset($parts[1]) ? strtolower($parts[1]) : null;

                        //************
                        //* ELEMENTS *
                        //************
                        if($target == 'elements') {

                            $start = microtime(true);
                            $elements = $this->ElementService->findAll()->getResults();

                            $data = array();
                            foreach($elements as $element) {
                                $data['Name'][] = $element->Name;
                                $data['Slug'][] = $element->Slug;
                                $data['Description'][] = $element->Description;
                            }
                            $end = microtime(true);

                            $this->printTable($data,$end-$start);
                        }

                        //***********
                        //* ASPECTS *
                        //***********
                        else if($target == 'aspects') {

                            //***************
                            //* FOR ELEMENT *
                            //***************
                            if(isset($parts[2])) {

                                if(strtolower($parts[2]) != 'for')
                                    throw new Exception('Syntax Error. Expected "for [element slug]"');

                                if(!isset($parts[3]))
                                    throw new Exception('Syntax Error. Expected "[element slug]"');

                                $target = strtolower($parts[3]);

                                $start = microtime(true);
                                $target = $this->ElementService->getBySlug($target);

                                $aspects = $target->getAspects();
                                $data = array();
                                foreach($aspects as $aspect) {
                                    $data['Name'][] = $aspect->Name;
                                    $data['Slug'][] = '@'.$aspect->Slug;
                                }
                                $end = microtime(true);

                                $this->printTable($data,$end-$start);

                            } else {

                                $start = microtime(true);
                                $aspects = $this->AspectService->findAll()->getResults();

                                $data = array();
                                foreach($aspects as $aspect) {
                                    $data['Name'][] = $aspect->Name;
                                    $data['Slug'][] = '@'.$aspect->Slug;
                                    $data['Description'][] = $aspect->Description;
                                }
                                $end = microtime(true);

                                $this->printTable($data,$end-$start);
                            }
                        }

                        //****************
                        //* CODE SNIPPET *
                        //****************
                        else if($target == 'code') {

                            if(empty($codeBuffer)) {
                                echo "Code snippet buffer is empty. Please execute a query then type 'show code'.\n";
                            } else {
                                echo "\n";

                                if(is_array($codeBuffer))
                                    echo implode("\n",$codeBuffer);
                                else
                                    echo $codeBuffer;

                                echo "\n";
                            }

                        }

                        else {
                            throw new Exception('Syntax Error. Expected "elements" or "aspects"');
                        }
                    }

                    //*********************************************
                    //*** HELP ************************************
                    //*********************************************
                    else if($action == 'help') {

                        echo "help is coming...\n";

                    }

                    //*********************************************
                    //*** DESCRIBE ********************************
                    //*********************************************
                    else if($action == 'describe' || $action == 'desc') {
                        $target = strtolower($parts[1]);

                        if(substr($target,0,1) == '@') {
                            $start = microtime(true);
                            $target = $this->AspectService->getBySlug(ltrim($target,'@'));

                            $schema = $target->Schema;

                            $data = array();

                            $metaDefs = $schema->getMetaDefs();
                            foreach($metaDefs as $metaDef) {
                                $validation = $metaDef->Validation->getValidationArray();
                                $data['Type'][] = 'Meta';
                                $data['Role'][] = '#'.$metaDef->Id;
                                $data['Title'][] = $metaDef->Title;
                                $data['Datatype'][] = $metaDef->Datatype;
                                $data['Default'][] = $metaDef->Default;
                                $data['Nullable'][] = $validation['nullable']?'true':'false';
                                $data['Match'][] = $validation['match'];
                                $data['Min'][] = $validation['min'];
                                $data['Max'][] = $validation['max'];
                                $data['Sortable'][] = '';
                                $data['QuickAdd'][] = '';
                                $data['Multiple'][] = '';
                                $data['Fieldlike'][] = '';
                            }

                            //sortable,quickadd,filter,fieldlike
                            $tagDefs = $schema->getTagDefs();
                            foreach($tagDefs as $tagDef) {
                                //$validation = $metaDef->Validation->getValidationArray();
                                $data['Type'][] = ucfirst($tagDef->Direction);
                                $data['Role'][] = '#'.$tagDef->Id;
                                $data['Title'][] = $tagDef->Title;
                                $data['Datatype'][] = $tagDef->Partial;
                                $data['Default'][] = '';
                                $data['Nullable'][] = '';
                                $data['Match'][] = '';
                                $data['Min'][] = '';
                                $data['Max'][] = '';
                                $data['Sortable'][] = $tagDef->Sortable ? 'true' : 'false';
                                $data['QuickAdd'][] = $tagDef->QuickAdd ? 'true' : 'false';
                                $data['Multiple'][] = $tagDef->Multiple ? 'true' : 'false';
                                $data['Fieldlike'][] = $tagDef->Fieldlike ? 'true' : 'false';
                            }
                            //print_r($schema);

                            $end = microtime(true);

                            $this->printTable($data,$end-$start);
                        } else {
                            $start = microtime(true);
                            $target = $this->ElementService->getBySlug($target);

                            $data = array(
                                'Name' => array($target->Name),
                                'Slug' => array($target->Slug),
                                'Description' => array($target->Description),
                                'Base URL' => array($target->BaseURL),
                                'Default Order' => array($target->DefaultOrder),
                                'Allow Slug Slashes' => array($target->AllowSlugSlashes?"true":"false"),
                                'Anchored Site' => array($target->AnchoredSiteSlug),
                            );

                            $end = microtime(true);

                            $this->printTable($data,$end-$start);
                        }
                    }

                    //*********************************************
                    //*** SELECT **********************************
                    //*********************************************
                    else if($action == 'select') {

                        $codeBuffer = array();
                        $m = array();

                        if(preg_match('/^(select)\s+(?P<fields>.+)\s+(from)\s+(?P<element>[@a-z0-9\-]+)(?P<conditions>.+)?/i',$input,$m)) {

                            $start = microtime(true);

                            $selectedFields = array();
                            $selectedMeta = array();

                            if(strtolower($m['fields']) == 'count') {
                                $fields = 'count';
                            } else {

                                $fields = preg_split('/\s*,\s*/',$m['fields']);

                                $validFields = array('Title','Slug','ActiveDate','Status','CreationDate','ModifiedDate','SortOrder','NodeRef');
                                $validMeta = array();

                                $validFieldsLower = array();
                                foreach($validFields as $f)
                                    $validFieldsLower[] = strtolower($f);

                                if(substr($m['element'],0,1) == '@') {
                                    $target = $this->AspectService->getBySlug(ltrim($m['element'],'@'));
                                    if($target == null)
                                        throw new Exception('Aspect not found: '.$m['element']);
                                    $schema = $target->Schema;
                                    $metaDefs = $schema->getMetaDefs();
                                    foreach($metaDefs as $metaDef) {
                                        $validFieldsLower[] = '#'.strtolower($metaDef->Id);
                                        $validMeta[] = '#'.strtolower($metaDef->Id);
                                    }
                                } else {
                                    $target = $this->ElementService->getBySlug($m['element']);
                                    if($target == null)
                                        throw new Exception('Element not found: '.$m['element']);
                                    $schema = $target->Schema;
                                    $metaDefs = $schema->getMetaDefs();
                                    foreach($metaDefs as $metaDef) {
                                        $validFieldsLower[] = '#'.strtolower($metaDef->Id);
                                        $validMeta[] = '#'.strtolower($metaDef->Id);
                                    }
                                }

                                $validFieldsLowerFlipped = array_flip($validFieldsLower);

                                foreach($fields as $field) {
                                    if(!in_array(strtolower($field),$validFieldsLower))
                                        throw new Exception("Invalid field '$field'. Permitted fields: ".implode(array_merge($validFields,$validMeta),', '));

                                    if(in_array(strtolower($field),$validMeta))
                                        $selectedMeta[] = strtolower($field);
                                    else
                                        $selectedFields[] = $validFields[$validFieldsLowerFlipped[strtolower($field)]];
                                }
                            }

                            $nq = new NodeQuery();
                            $codeBuffer[] = '$nq = new NodeQuery();';

                            $nq->setParameter('Elements.in',$m['element']);
                            $codeBuffer[] = '$nq->setParameter("Elements.in","'.$m['element'].'");';

                            $limit = null;
                            $offset = 0;

                            if($fields == 'count') {

                                $nq->setParameter('Count.only',true);
                                $codeBuffer[] = '$nq->setParameter("Count.only",true);';

                            } else {

                                if(!empty($selectedMeta)) {
                                    $nq->setParameter('Meta.select',implode($selectedMeta,','));
                                    $codeBuffer[] = '$nq->setParameter("Meta.select","'.implode($selectedMeta,',').'");';
                                }

                                $conditions = $m['conditions'];

                                //PROCESS LIMIT
                                if(preg_match('/(limit)\s+(?P<limit>\d+)/i',$conditions,$limitMatch)) {
                                    $limit = intval($limitMatch['limit']);
                                    $codeBuffer[] = '$nq->setLimit('.$limit.');';
                                }

                                //PROCESS OFFSET
                                if(preg_match('/(offset)\s+(?P<offset>\d+)/i',$conditions,$offsetMatch)) {
                                    $offset = intval($offsetMatch['offset']);
                                    $codeBuffer[] = '$nq->setOffset('.$offset.');';
                                }

                                //todo: process where
                                //todo: process order by
                                //todo: process as json

                            }

                            if($fields == 'count') {

                                $total = $this->NodeService->findAll($nq)->getTotalRecords();
                                $codeBuffer[] = '$total = $this->NodeService->findAll($nq)->getTotalRecords()';

                                $data = array(
                                    'Total' => array($total),
                                );

                            } else {

                                //if no limit set or limit is > 1000, loop through in chunks of 1000 until no results or requested limit is reached
                                $count = 0;
                                $nq->setOffset($offset);
                                $nq->setLimit($limit == null || $limit > 1000 ? 1000 : $limit);

                                $results = $this->NodeService->findAll($nq)->getResults();
                                $codeBuffer[] = '$results = $this->NodeService->findAll($nq)->getResults();';

                                $data = array();
                                while(!empty($results)) {
                                    if(!empty($results)) {
                                        foreach($results as $r) {
                                            foreach($selectedFields as $field) {
                                                $data[$field][] = $r->$field;
                                            }
                                            foreach($selectedMeta as $field) {
                                                $data[$field][] = $r->getMetaValue($field);
                                            }
                                            $count++;
                                        }
                                    }

                                    $results = null;

                                    if($limit != null && $count >= $limit)
                                        break;

                                    if($limit == null || $limit > 1000) {
                                        $offset += 1000;

                                        $nq->clearResults();
                                        $nq->setOffset($offset);
                                        $results = $this->NodeService->findAll($nq)->getResults();
                                    }
                                }
                            }

                            $end = microtime(true);
                            $this->printTable($data,$end-$start);

                            if((isset($history[0]) && $history[0] != $input) || empty($history))
                                array_unshift($history,$input);

                        }
                        //*********************************************
                        //*** UPDATE **********************************
                        //*********************************************
                        else if($action == 'update') {
                            throw new Exception('you wish!');
                        }
                        //*********************************************
                        //*** DELETE **********************************
                        //*********************************************
                        else if($action == 'delete') {
                            throw new Exception('you wish!');
                        }
                        else {
                            throw new Exception("shut yo mouth");
                        }
                    }
                    //print_r($parts);
                }
            } catch(Exception $e) {
                echo "ERROR: ".$e->getMessage()."\n";
            }
        } while ($input != 'exit' && $input != 'quit');

        echo "Bye\n";
    }

    /**
     * @param  $data - array of columns (index by string heading), each column is an array of string values
     * @return void
     */
    private function printTable(array $data,$duration = null)
    {
        if(!empty($data)) {
            $maxWidths = array();
            $total = count(reset($data));

            foreach($data as $heading => $col) {
                $maxWidths[$heading] = strlen($heading);

                foreach($col as $j => $row) {

                    //pass in types?

                    //todo: format dates
                    //todo: format floats, how accurately detect a float (not an int)
                    //todo: format booleans & flags
                    //todo: truncate html and text fields

                    if(strlen($row) > $maxWidths[$heading])
                        $maxWidths[$heading] = strlen($row);
                }
            }

            $sep = "+";
            foreach($data as $heading => $col) {
                $sep .= "-".str_pad("",$maxWidths[$heading],'-')."-+";
            }
            $sep .= "\n";

            echo $sep;

            echo "|";
            foreach($data as $heading => $col) {
                echo " ".str_pad($heading,$maxWidths[$heading])." |";
            }
            echo "\n";

            echo $sep;

            for($i = 0; $i < $total; $i++) {
                echo "|";
                foreach($data as $heading => $col) {

                    $val = $data[$heading][$i];

                    $padding = STR_PAD_RIGHT;
                    if(is_numeric($val))
                        $padding = STR_PAD_LEFT;

                    echo " ".str_pad($val,$maxWidths[$heading],' ',$padding)." |";
                }
                echo "\n";
            }

            echo $sep;
            echo $total." row".($total == 1 ? '' : 's')." in set";
        } else {
            echo "Empty set";
        }

        if($duration !== null) {
            echo " (".sprintf('%01.2f',$duration)." sec)";
        }

        echo "\n";
    }



}
