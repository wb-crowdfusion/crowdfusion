<?php
/**
 * AbstractApiController
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
 * @version     $Id: AbstractApiController.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AbstractApiController
 *
 * @package     CrowdFusion
 */
abstract class AbstractApiController extends AbstractController
{

    protected $charset;

    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    protected $errorCodeResolver;

    public function setErrorCodeResolver(MessageCodeResolver $ErrorCodeResolver)
    {
        $this->errorCodeResolver = $ErrorCodeResolver;
    }

    protected $ErrorHandler;

    public function setErrorHandler(ErrorHandler $ErrorHandler)
    {
        $this->ErrorHandler = $ErrorHandler;
    }

    protected function checkNonce($action = null)
    {
        $nonce = $this->Request->getParameter('action_nonce');

        // action was submitted, so verify the nonce
        // NOTE: this verifies the nonce against the original method
        if(!$this->Nonces->verify($nonce, is_null($action)?$this->originalAction:$action)) {
            throw new NoncesException('Nonce mismatch for action ['.$this->originalAction.'].');
        }

        //if(LOG_ENABLE) System::log(self::$logType, 'Passed nonce check.');
    }

    protected function handleActionInternal($method)
    {
        try {

            $this->Response->addHeader('Cache-Control', 'no-cache, must-revalidate, post-check=0, pre-check=0');
            $this->Response->addHeader('Expires', $this->DateFactory->newLocalDate()->toRFCDate());

            $handler = $this->RequestContext->getControls()->getControl('view_handler');

            switch($handler)
            {
                case 'xml':
                    $this->Response->sendHeader('Content-Type', 'application/xml; charset="'.$this->charset.'"');
                    break;

                case 'json':
                default:

                    $this->Response->sendHeader('Content-Type', 'application/json; charset="'.$this->charset.'"');
                    break;
            }

            parent::handleActionInternal($method);

            if(!is_null($this->view) && !is_string($this->view->getName()))
                throw new Exception('Method ['.$method.'] failed to return a valid view, was ['.$this->view->getName().']');

        } catch (NonceException $ne) {
            $this->bindToActionDatasource(array());
            //if(LOG_ENABLE) System::log(self::$logType, 'Validation Exception: ['.print_r($ve, true).']');

            $this->errors->addGlobalError($ne->getCode(), 'The record you attempted to edit has expired. This is typically caused by keeping a page open for longer than 6 hours without any changes.');

            echo $this->errorsResponse();
            return null;

        } catch (ValidationException $ve) {
            $this->bindToActionDatasource(array());
            //if(LOG_ENABLE) System::log(self::$logType, 'Validation Exception: ['.print_r($ve, true).']');

            $this->errors->addErrors($ve->getErrors()->getErrors());

            echo $this->errorsResponse();
            return null;

        } catch (ActionException $ae) {
            $this->bindToActionDatasource(array());
            //if(LOG_ENABLE) System::log(self::$logType, 'Action Exception: ['.print_r($ae, true).']');

            // store error messages and error fields somewhere
            $this->errors->addGlobalError($ae->getCode(), $ae->getMessage());

            $view = ($ae->getView())?$ae->getView():$this->formView();
            $data = $ae->getData();

            $this->view = new View($view, $data);
        } catch(Exception $e) {

            $this->ErrorHandler->sendErrorEmail($e);

            $this->errors->addGlobalError($e->getCode(), $e->getMessage())->throwOnError();

            echo $this->errorsResponse();
            return null;

        }

        return true;
    }

    /**
     * copied from AbstractController
     */
    protected function passthruParameter(&$dto, $variableName) {
        $reqName = str_replace('.','_', $variableName);
        if($this->Request->getParameter($reqName) !== null && $this->Request->getParameter($reqName) != '')
            $dto->setParameter($variableName, $this->Request->getParameter($reqName));
    }

    protected function errorsResponse()
    {
        $handler = $this->RequestContext->getControls()->getControl('view_handler');

        $errors = $this->errors->getErrors(); //array of FieldValidationError or ValidationError




        switch($handler)
        {
            case 'xml':
                $s = '';

                if(!empty($errors)) {


                    foreach($errors as $error) {
                        if($error instanceof FieldValidationError)

                            $s .= '<Error>\
                                <Code>'+htmlentities($error->getFailureCode())+'</Code>\
                                <Resolved>'+htmlentities($error->getFieldResolved())+'</Resolved>\
                                <Type>'+htmlentities($error->getFieldType())+'</Type>\
                                <Title>'+htmlentities($error->getFieldTitle())+'</Title>\
                                <Value>'+htmlentities($error->getValue())+'</Value>\
                                <Message>'+htmlentities($this->errorCodeResolver->resolveMessageCode(
                                        $error->getFailureCode(),
                                        $error->getFieldResolved(),
                                        $error->getFieldType(),
                                        array(
                                            $error->getFieldTitle(),
                                            $error->getValue(),
                                            $error->getFieldType()
                                        ),
                                        $error->getDefaultErrorMessage()))+'</Message>\
                                </Error>';

                        else if($error instanceof ValidationError)
                            $s .= '<Error>\
                                <Code>'+htmlentities($error->getErrorCode())+'</Code>\
                                <Message>'+htmlentities($this->errorCodeResolver->resolveMessageCode(
                            $error->getErrorCode(),
                            null,
                            null,
                            null,
                            $error->getDefaultErrorMessage()))+'</Message>\
                                </Error>';
                    }
                } else {
                    $s .= '<Error>\
            <Code>-1</Code>\
            <Message>no data</Message>\
            </Error>';
                }

                $xml .= '<?xml version="1.0"?>
<API><Errors>'.$s.'</Errors></API>';

                return $xml;

            case 'json':
            default:

                $s = array('Errors'=>array());

                if(!empty($errors)) {

                    foreach($errors as $error) {
                        if($error instanceof FieldValidationError)
                            $s['Errors'][] = array(
                                'Code'=>$error->getFailureCode(),
                                'Resolved'=>$error->getFieldResolved(),
                                'Type'=>$error->getFieldType(),
                                'Title'=>$error->getFieldTitle(),
                                'Value'=>$error->getValue(),
                                'Message'=>$this->errorCodeResolver->resolveMessageCode(
                                        $error->getFailureCode(),
                                        $error->getFieldResolved(),
                                        $error->getFieldType(),
                                        array(
                                            $error->getFieldTitle(),
                                            $error->getValue(),
                                            $error->getFieldType()
                                        ),
                                        $error->getDefaultErrorMessage())

                            );
                        else if($error instanceof ValidationError)
                            $s['Errors'][] = array(
                                'Code'=>$error->getErrorCode(),
                                'Message'=>$this->errorCodeResolver->resolveMessageCode(
                            $error->getErrorCode(),
                            null,
                            null,
                            null,
                            $error->getDefaultErrorMessage())
                            );
                    }

                } else {
                    $s['Errors'][] = array('Code' => -1, 'Message' => 'no error message');
                }
                return JSONUtils::encode($s);

        }

    }


    protected function readDTO($dto)
    {

        if(!$dto->hasResults())
        {
            return $this->noresultsResponse();
        }


        $handler = $this->RequestContext->getControls()->getControl('view_handler');

        switch($handler)
        {
            case 'xml':
                $this->Response->addHeader('Content-Type', 'application/xml; charset="'.$this->charset.'"');

                $s = '<?xml version="1.0"?>
<NodeFindAll>
<TotalRecords>'.$dto->getTotalRecords().'</TotalRecords>
<Nodes>';

                foreach($dto->getResults() as $result)
                    $s .= $this->xmlify(ArrayUtils::flattenObjectsUsingKeys($this->NodeMapper->populateNodeCheaters($result)->toArray(), $this->getEncodeKeys()), 'Node');

                $s .= '    </Nodes>
</NodeFindAll>';

                return $s;

            case 'json':
            default:
                $this->Response->addHeader('Content-Type', 'application/json; charset="'.$this->charset.'"');

                $s = array('TotalRecords' => $dto->getTotalRecords(), 'Nodes' => array());

                foreach($dto->getResults() as $result)
                    $s['Nodes'][] = ArrayUtils::flattenObjectsUsingKeys($this->NodeMapper->populateNodeCheaters($result)->toArray(), $this->getEncodeKeys());


                return JSONUtils::encode($s);
        }
    }


    protected function noresultsResponse()
    {

        $handler = $this->RequestContext->getControls()->getControl('view_handler');

            switch($handler)
            {
                case 'xml':
                    $s .= '<?xml version="1.0"?>
    <API>
        <TotalRecords>0</TotalRecords>
        <Nodes />
    </API>';

                    return $s;

                case 'json':
                default:

                    $s = array(
                        'TotalRecords'=>0,
                        'Nodes'=> array()
                    );
                    return JSONUtils::encode($s);

            }

    }


    protected function getEncodeKeys()
    {
        $keys = $this->Request->getParameter('Keys');

        if($keys == null)
            $keys = $this->defaultEncodeKeys();
        else if(strpos($keys,',') !== FALSE)
            $keys = explode(',', $keys);
        else
            $keys = array($keys);

        return $keys;

    }

    protected function defaultEncodeKeys()
    {
        return array(
                'Slug',
                'Title',
                'Status',
                'ActiveDate',
                'CreationDate',
                'ModifiedDate',
                'SortOrder',
                'OutTags',
                'InTags',
                'Metas',
//                'NodeRef',
//                'NodePartials',
                'Element.Slug',
                //'Site',
                'RecordLink',
                //'Cheaters'
        );
    }

    protected function xmlify($data, $rootNodeName, $xml = null, $depth = 0)
    {
        if($rootNodeName == null)
            $rootNodeName = 'Node';

        if($xml == null)
            $xml = simplexml_load_string("<$rootNodeName />");

        foreach($data as $key => $val) {

            if(is_numeric($key))
                $key = "value";
            else if($depth == 0)
                $key = preg_replace('/[^a-z]/i', '', $key);

            if(is_array($val)) {

                $node = $xml->addChild($key);
                $this->xmlify($val, $rootNodeName, $node, $depth + 1);

            } else {

                $val = is_bool($val) ? ($val ? 'true' : 'false') : htmlspecialchars($val);

                if($depth > 0 && $key != 'value') {
                    $node = $xml->addChild('entry',$val);
                    $node->addAttribute('key',$key);
                } else {
                    $xml->addChild($key,$val);
                }
            }
        }

        //strip off xml header so this snippet can be used in a template loop
        return str_replace('<?xml version="1.0"?>','',$xml->asXML());
    }

    /**
     * copied from AbstractWebController
     * changed templateVars to use Request->getParameter()
     */
    protected function buildLimitOffset(&$dto) {

        $dto->isRetrieveTotalRecords(true);

        $offset = $this->Request->getParameter('Offset');
        $maxrows = $this->Request->getParameter('MaxRows');
        $page = $this->Request->getParameter('Page');

        if(empty($maxrows) || !is_numeric($maxrows)) {
            $dto->setLimit(25);
        } else {
            $dto->setLimit($maxrows);
        }


        if(!empty($offset) && is_numeric($offset)) {

            $dto->setOffset($offset);

        } else if(empty($maxrows) || empty($page) || !is_numeric($maxrows) || !is_numeric($page)) {

            // nothing

        } else {

            // set StartItem & EndItem as template variables
            //$this->templateVars['StartItem'] = $maxrows * ($page - 1) + 1;
            //$this->templateVars['EndItem'] = $this->Request->getParameter('StartItem') + $maxrows - 1;

            $dto->setOffset($maxrows * ($page - 1));
        }

    }

    /**
     * copied from AbstractCmsController
     * removed bypassSorts check
     */
    protected function buildSorts(&$dto) {

        if(!empty($this->params['sort']))
            $dto->setOrderBys($this->params['sort']);

    }

    /**
     * copied from AbstractCmsController
     * removed bypassFilters check
     */
    protected function buildFilters(&$dto) {

        if(isset($this->rawParams['filter']))
            foreach((array)$this->rawParams['filter'] as $name => $value) {
                $dto->setParameter($name, $value);
            }

    }

    protected function getTag()
    {
        return new Tag(
            $this->Request->getParameter('TagElement'),
            $this->Request->getParameter('TagSlug'),
            $this->Request->getParameter('TagRole'),
            $this->Request->getParameter('TagValue'),
            $this->Request->getParameter('TagValueDisplay')
        );

    }

    protected function getNodeRef()
    {
        $elementSlug = $this->Request->getParameter('ElementSlug');
        $nodeSlug = $this->Request->getParameter('NodeSlug');

        if(empty($siteSlug) && empty($elementSlug) && empty($nodeSlug))
            return null;

        $element = $this->ElementService->getBySlug($elementSlug);

        if($nodeSlug == '')
            $noderef = new NodeRef($element);
        else
            $noderef = new NodeRef($element,$nodeSlug);

        return $noderef;
    }
}