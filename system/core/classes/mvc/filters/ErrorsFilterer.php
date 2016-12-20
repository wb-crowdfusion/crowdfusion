<?php
/**
 * ErrorsFilterer
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
 * @version     $Id: ErrorsFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * ErrorsFilterer
 *
 * @package     CrowdFusion
 */
class ErrorsFilterer extends MsgFilterer
{

    public function setErrorCodeResolver(MessageCodeResolver $ErrorCodeResolver)
    {
        $this->errorCodeResolver = $ErrorCodeResolver;
    }

    public function apiJson()
    {
        $errors = $this->getGlobal('Errors'); //array of FieldValidationError or ValidationError

        $s = array();

        if(empty($errors)) {

            $s[] = array(
                'Code'=>'-1',
                'Message'=>'no data'
            );

        } else {

            foreach($errors as $error) {
                if($error instanceof FieldValidationError)
                    $s[] = array(
                        'Code'=>$error->getFailureCode(),
                        'Resolved'=>$error->getFieldResolved(),
                        'Type'=>$error->getFieldType(),
                        'Title'=>$error->getFieldTitle(),
                        'Value'=>$error->getValue(),
                        'Message'=>$this->errorCodeResolver->resolveMessageCode(
                                $error->getFailureCode(),
                                array(
                                    $error->getFieldTitle(),
                                    $error->getValue(),
                                    $error->getFieldType()
                                ),
                                $error->getDefaultErrorMessage())

                    );
                else if($error instanceof ValidationError)
                    $s[] = array(
                        'Code'=>$error->getErrorCode(),
                        'Message'=>$this->errorCodeResolver->resolveMessageCode(
                    $error->getErrorCode(),
                    null,
                    $error->getDefaultErrorMessage())
                    );
            }
        }

        return JSONUtils::encode($s);
    }

    public function apiXml()
    {
        $errors = $this->getGlobal('Errors'); //array of FieldValidationError or ValidationError

        $s = '';

        if(empty($errors)) {

            $s .= '<Error>\
                <Code>-1</Code>\
                <Message>no data</Message>\
                </Error>';

        } else {

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
                    $error->getDefaultErrorMessage()))+'</Message>\
                        </Error>';
            }
        }

        return $s;
    }

    public function globalErrorString()
    {
        $errors = $this->getGlobal('Errors'); //array of FieldValidationError or ValidationError

        if(empty($errors)) return '';

        foreach((array)$errors as $error) {
            if(!($error instanceof ValidationError)) continue;
            return $this->errorCodeResolver->resolveMessageCode(
                $error->getErrorCode(),
                null,
                $error->getDefaultErrorMessage()
            );
        }

    }

    public function globalErrorCode()
    {
        $errors = $this->getGlobal('Errors'); //array of FieldValidationError or ValidationError

        if(empty($errors)) return '';

        foreach((array)$errors as $error) {
            if(!($error instanceof ValidationError)) continue;
            return $error->getErrorCode();
        }

    }

   /**
    * options: list mode <-> br mode, before & after strings
    * params: field (fieldvalidationerror to use)
    *         defaults to global error list
    * @param
    *
    * @return
    *
    * @throws Exception
    */
    public function render()
    {
        $field = $this->getParameter('field');

        $mode = $this->getParameter('mode');
        $mode = $mode == null ? 'list' : $mode;

        $css = $this->getParameter('css');

        $prefix = $this->getParameter('prefix');
        $prefix = $prefix == null ? '' : $prefix;

        $postfix = $this->getParameter('postfix');
        $postfix = $postfix == null ? '' : $postfix;

        $errors = $this->getGlobal('Errors'); //array of FieldValidationError or ValidationError


        if(empty($errors)) return '';


        if(empty($field)) {

            $s = '';

            if($mode == 'list') {
                $s = "<ul";
                if(!empty($css))
                    $s .= " class=\"{$css}\"";
                $s .= ">\n";
            }

            $cnt = 0;
            foreach($errors as $error) {
                if(!($error instanceof ValidationError)) continue;

                $msg = $this->errorCodeResolver->resolveMessageCode(
                    $error->getErrorCode(),
                    null,
                    $error->getDefaultErrorMessage());

                if($mode == 'br')
                    $s .= "{$prefix}{$msg}{$postfix}<br/>\n";
                else if($mode == 'list')
                    $s .= "<li>{$prefix}{$msg}{$postfix}</li>\n";
                $cnt++;
            }

            //none of the errors were global errors, try specifying the 'field' parameter
            if($cnt == 0)
                return '';

            if($mode == 'list')
                $s .= "</ul>\n";

            return $s;

        } else {

            foreach($errors as $error) {
                if(!($error instanceof FieldValidationError)) continue;

                if($field == $error->getFailureCode()) {

                    $msg = $this->errorCodeResolver->resolveMessageCode(
                        $error->getFailureCode(),
                        array(
                            $error->getFieldTitle(),
                            $error->getValue(),
                            $error->getFieldType()
                        ),
                        $error->getDefaultErrorMessage());

                    if(!empty($css))
                        return "<span class=\"{$css}\">{$msg}</span>";

                    return $msg;
                }
            }

        }

        return '';
    }

}
