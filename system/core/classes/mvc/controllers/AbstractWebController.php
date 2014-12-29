<?php
/**
 * AbstractWebController
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
 * @version     $Id: AbstractWebController.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AbstractWebController
 *
 * @package     CrowdFusion
 */
abstract class AbstractWebController extends AbstractController
{


    protected $NodeBinder;
    protected $NodeMapper;
    protected $ElementService;
    protected $TagsHelper;
    protected $RegulatedNodeService;
    protected $NodeRefService;

    public function setNodeMapper(NodeMapper $NodeMapper)
    {
        $this->NodeMapper = $NodeMapper;
    }

    public function setNodeRefService(NodeRefService $NodeRefService)
    {
        $this->NodeRefService = $NodeRefService;
    }

    public function setElementService(ElementService $ElementService)
    {
        $this->ElementService = $ElementService;
    }

    public function setTagsHelper(TagsHelper $TagsHelper)
    {
        $this->TagsHelper = $TagsHelper;
    }

    /**
     * [IoC] Inject NodeBinder
     *
     * @param NodeBinder $NodeBinder NodeBinder
     *
     * @return void
     */
    public function setNodeBinder(NodeBinder $NodeBinder)
    {
        $this->NodeBinder = $NodeBinder;
    }


    /**
     * [IoC] Inject RegulatedNodeService
     *
     * @param RegulatedNodeService $RegulatedNodeService RegulatedNodeService
     *
     * @return void
     */
    public function setRegulatedNodeService(NodeServiceInterface $RegulatedNodeService)
    {
        $this->RegulatedNodeService = $RegulatedNodeService;
    }

    protected function checkNonce()
    {
        $nonce = $this->RequestContext->getControls()->getControl('action_nonce');

        // action was submitted, so verify the nonce
        // NOTE: this verifies the nonce against the original method
        if(!$this->Nonces->verify($nonce, $this->originalAction)) {
            $this->errors->addGlobalError('nonce.mismatch', 'Stale form data, please try again.')->throwOnError();
        }

        //if(LOG_ENABLE) System::log(self::$logType, 'Passed nonce check.');
    }

    protected function handleActionInternal($method)
    {
        try {

            parent::handleActionInternal($method);

            if(is_string($this->view))
                $this->view = new View($this->view);

            if(!is_null($this->view) && !is_string($this->view->getName()))
                throw new Exception('Method ['.$method.'] failed to return a valid view, was ['.$this->view->getName().']');

        } catch (ValidationException $ve) {
            //if(LOG_ENABLE) System::log(self::$logType, 'Validation Exception: ['.print_r($ve, true).']');

            $this->errors->addErrors($ve->getErrors()->getErrors());

            $this->view = new View($this->formView());

        } catch (ActionException $ae) {
            //if(LOG_ENABLE) System::log(self::$logType, 'Action Exception: ['.print_r($ae, true).']');

            // store error messages and error fields somewhere
            $this->errors->addGlobalError($ae->getCode(), $ae->getMessage());

            $view = ($ae->getView())?$ae->getView():$this->formView();
            $data = $ae->getData();

            $this->view = new View($view, $data);
        }

        return true;
    }


    protected function readDTO(DTO &$dto)
    {

        if($dto->isRetrieveTotalRecords()) {
            $this->templateVars['TotalRecords'] = $dto->getTotalRecords();
            $this->templateVars['TotalPages'] = intval(($dto->getTotalRecords()-1)/$dto->getLimit())+1; 
        }

        return $dto->getResultsAsArray();
    }

    protected function readNodeQuery(DTO &$dto)
    {
        return $this->readDTO($dto);
    }


    protected function buildLimitOffset(&$dto)
    {

        if (!array_key_exists('MaxRows', $this->templateVars) || !is_numeric($this->templateVars['MaxRows'])) {
            $dto->setLimit(25);
        } else {
            $dto->setLimit($this->templateVars['MaxRows']);
        }

        if(!empty($this->templateVars['Page']))
            $dto->isRetrieveTotalRecords(true);

        if (!empty($this->templateVars['Offset']) && is_numeric($this->templateVars['Offset'])) {

            $dto->setOffset($this->templateVars['Offset']);

        } elseif (empty($this->templateVars['MaxRows']) || empty($this->templateVars['Page'])
            || !is_numeric($this->templateVars['MaxRows']) || !is_numeric($this->templateVars['Page'])) {

            // nothing

        } else {

            // set StartItem & EndItem as template variables
            $this->templateVars['StartItem'] = $this->templateVars['MaxRows'] *($this->templateVars['Page']-1) +1;
            $this->templateVars['EndItem'] = $this->templateVars['StartItem'] +$this->templateVars['MaxRows'] -1;

            $dto->setOffset($this->templateVars['MaxRows'] *($this->templateVars['Page']-1));
        }

    }

    protected function buildNodeRef($slugKey = null, $elementKey = 'Element')
    {

        if (empty($this->params[$elementKey]))
            throw new Exception($elementKey.' parameter is required');


        $nodeElement = $this->ElementService->getBySlug($this->params[$elementKey]);

        if (!is_null($slugKey) && $this->Request->getParameter($slugKey) != '') {
            $slug = $this->Request->getParameter($slugKey);

            $this->nodeRef = new NodeRef($nodeElement, $slug);
        } else {
            $this->nodeRef = new NodeRef($nodeElement);
        }


        return $this->nodeRef;
    }

    protected function bindNodeToActionDatasource(Node $node)
    {
        $this->bindToActionDatasource(array($node));
    }



    /* BASIC DATASOURCES */

    protected function filterTags()
    {
        $data       = array();
        $returndata = array();
        $predicates = array();
        $count      = 0;
        $arr        = $this->getData();
        if (!empty($this->templateVars['Page']) && !empty($this->templateVars['MaxRows'])) {
            $this->templateVars['StartItem'] = (($this->templateVars['Page']-1)*$this->templateVars['MaxRows']) +1;
            $this->templateVars['EndItem'] = ($this->templateVars['Page'])*$this->templateVars['MaxRows'];
        }

        $partial = $this->getTemplateVariable('Partial');

        $partials = array();
        if(!empty($partial)) {
            $partialsStr = explode(',',$partial);
            foreach($partialsStr as $partial)
                $partials[] = new TagPartial($partial);
        }

        if (!empty($arr))
            foreach ((array)$arr as $tag) {
                if (!empty($partials)) {
                    $found = false;
                    foreach($partials as $partial) {
                        if($this->TagsHelper->matchPartial($partial, $tag))
                        {
                            $found = true;
                            break;
                        }
                    }
                    if(!$found)
                        continue;
                }

                if (($this->getTemplateVariable('Status.eq') != null) &&
                  $tag['TagLinkStatus'] != $this->getTemplateVariable('Status.eq'))
                    continue;
                if (($this->getTemplateVariable('Status.isActive') != null) && empty($tag['TagLinkURL']))
                    continue;

                $data[] = $tag;
            }

        $this->templateVars['TotalRecords'] = sizeof($data);
        if (!empty($data))
            foreach ((array)$data as $row) {
                if (!empty($this->templateVars['StartItem']) && $this->templateVars['StartItem'] > ++$count)
                    continue;

                $returndata[] = $row['TagLinkNode'];

                if (!empty($this->templateVars['MaxRows']) && sizeof($returndata) >= $this->templateVars['MaxRows'])
                    break;
            }
        return $returndata;
    }

    protected function filterAlphaIndex()
    {

        $data = array();
        $arr = $this->getData();

        $foundChars = array();
        if(!empty($arr))
            foreach((array)$arr as $row) {
                if(!empty($this->templateVars['OrderByLastName']) && $this->templateVars['OrderByLastName'] == 1) {
                    if(preg_match("/(.+)\s([^\s^\,]+),?(\sjr\.?)/i",$row[$this->templateVars['AlphaField']],$m)) {
                            $row[$this->templateVars['AlphaField']] = $m[2].", ".$m[1].$m[3];
                    } elseif(preg_match("/(.+)\s(.+)/i",$row[$this->templateVars['AlphaField']],$m)) {
                            $row[$this->templateVars['AlphaField']] = $m[2].", ".$m[1];
                    }
                }
                $valToMatch = $row[$this->templateVars['AlphaField']];
                $firstChar = strtolower(substr($valToMatch, 0, 1));
                $isDigit = !preg_match("/[a-z]/", $firstChar);
                if($isDigit)
                    $foundChars['#'][] = $row;
                else
                    $foundChars[$firstChar][] = $row;
            }

        foreach (array('num'=>'#','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z') as $link => $key) {
            $letter = is_numeric($link) ? $key : $link;
            $data[] = array(
                'LetterLink'        => $letter,
                'LetterDisplay'     => $key,
                'HasLetter'         => array_key_exists($key, $foundChars) == true ? '1'               : '0',
                'SerializedRecords' => isset($foundChars[$key])                    ? $foundChars[$key] : array());
        }

        $this->fv['TotalRecords'] = sizeof($data);
        return $data;

    }

//    protected function sections()
//    {
//
//        //filter section data by type
//        if ($this->getTemplateVariable('FilterSectionType') != null) {
//            $data = array();
//            $count = 0;
//            $arr = $this->getData();
//            if ($this->getTemplateVariable('Page') != null && $this->getTemplateVariable('MaxRows') != null) {
//                $this->setTemplateVariable('StartItem', $this->getTemplateVariable('Page') * $this->getTemplateVariable('MaxRows') + 1);
//                $this->setTemplateVariable('EndItem', ($this->getTemplateVariable('Page') + 1) * $this->getTemplateVariable('MaxRows'));
//            }
//
//            $min  = $this->getTemplateVariable('Min') != null ?intVal($this->getTemplateVariable('Min')) : 1;
//            $max  = $this->getTemplateVariable('Max') != null ?intVal($this->getTemplateVariable('Max')) : -1;
//            $type = $this->getTemplateVariable('FilterSectionType');
//
//            $lastseccount = 0;
//            foreach ((array)$arr as $row) {
//                if (empty($row['SectionType']) || (!empty($type) && $row['SectionType'] != $type))
//                    continue;
//                $data[] = $row;
//              // $lastseccount = $row['SectionCount'];
//                $lastseccount = $row['SortOrder'];
//                ++$count;
//
//                if ($this->getTemplateVariable('MaxRows') != null && sizeof($data) >= $this->getTemplateVariable('MaxRows'))
//                    break;
//            }
//
//            $this->setTemplateVariable('TotalRecords',sizeof($data));
//            return $data;
//        }
//        // read section data from AJAX post
//        elseif(isset($this->params['PopulateSection'])) {
//
//            //$data = array('Tags' => array_merge(isset($this->params['Tags']['out'])?$this->params['Tags']['out']:array()));
//
//            // TODO: no tag cheaters
//
//            foreach ($this->params as $key => $value) {
//                if (!isset($data[$key]))
//                    $data[$key] = $value;
//            }
//
//            return array($data);
//
//        // TODO: read section data from passed in array
//        } elseif ($this->getData() != null){
//
//        // TODO: read section data from db
//        } else {
//
//        }
//
//    }


}