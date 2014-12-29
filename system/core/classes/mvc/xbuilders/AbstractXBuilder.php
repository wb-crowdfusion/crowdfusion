<?php
/**
 * AbstractXBuilder
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
 * @version     $Id: AbstractXBuilder.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * AbstractXBuilder
 *
 * @package     CrowdFusion
 */
abstract class AbstractXBuilder implements XBuilderInterface{

    protected $filepath = null;
    protected $template = null;
    protected $globals = null;
    protected $templateVars = null;


    protected $xml = null;
    protected $current_method = '';
    protected $current_depth = -1;

    public function handleBuild($xml, $filepath, Template $template, $globals) {

        $this->current_method = '';
        $this->current_depth = -1;

        $this->template = $template;
        $this->templateVars = $template->getLocals();
        $this->globals = $globals;
        $this->filepath = $filepath;

        $this->xml = new XMLReader();

        $this->xml->XML($xml);

        $result = $this->parseChildren();

        $this->xml->close();

        return $result;
    }


    /**
     * Returns an array of globals set for the filterer
     *
     * @return array
     */
    protected function getGlobals()
    {
        return $this->globals;
    }

    /**
     * Returns the value for a global by {@link $name}
     *
     * @param string $name The name of the global
     *
     * @return mixed
     */
    protected function getGlobal($name)
    {
        return array_key_exists($name, $this->globals)?$this->globals[$name]:null;
    }

    /**
     * Returns the value for a parameter by {@link $name}
     *
     * @param string $name The name of the parameter
     *
     * @return mixed
     */
    protected function getParameter($name)
    {
        return array_key_exists($name, $this->templateVars)?$this->templateVars[$name]:null;
    }

    /**
     * Returns the value for a parameter or throws an exception is not found
     *
     * @param string $name The name of the parameter
     *
     * @return mixed
     */
    protected function getRequiredParameter($name)
    {
        if(!array_key_exists($name, $this->templateVars))
            throw new FiltererException('Required parameter ['.$name.'] is missing on xmodule call ['.$template->getName().']');

        return $this->templateVars[$name];
    }

    /**
     * Returns an array of parameters
     *
     * @return array
     */
    protected function getParameters()
    {
        return $this->templateVars;
    }


    protected function parseChildren($str = '', $prefix = '') {

        try {
            $parent_method = $this->current_method;
            $parent_depth = $this->current_depth;

            while ($this->xml->read() && $this->xml->depth > $parent_depth) {
                $name = $this->xml->name;
                $depth = $this->xml->depth;
                $empty = false;

                switch ($this->xml->nodeType) {


                    case XMLReader::END_ELEMENT:

                        $method = '_'.$prefix.$name;

                        if(!method_exists($this, $method))
                            break;

                        $str .= $this->$method();
                        break;

                    case XMLReader::ELEMENT:

                        if($this->xml->isEmptyElement) {
                            $empty = true;
                        }

                        //REMOVE XML NAMESPACE
                        //THIS ALLOWS ELEMENTS FROM MULTIPLE NAMESPACES TO BE USED IN THE XMODULES
                        $pos = strpos($name,":");
                        if($pos !== FALSE) {
                            $name = substr($name,$pos+1);
                        }

                        $method = $prefix.$name;

                        $this->current_method = $method;
                        $this->current_depth = $depth;

                        if(!method_exists($this, $method))
                            break;

                        $str .= $this->$method();

                        if($empty) {

                            $method = '_'.$prefix.$name;

                            if(!method_exists($this, $method))
                                break;

                            $str .= $this->$method();
                        }

                        break;

                }

            }

            return $str;

        }catch (Exception $e)
        {
            throw new Exception('Unable to parse xmod template ['.$this->filepath.'], original error: '.$e->getMessage());
        }
    }

    protected function _text() {

        $value = '';
        $name = $this->xml->name;
        if($this->xml->isEmptyElement)
            return $value;

        while ($this->xml->read()) {
            switch ($this->xml->nodeType) {
                case XMLReader::END_ELEMENT:
                case XMLReader::ELEMENT:
                    return $value;

                case XMLReader::TEXT:
                case XMLReader::CDATA:
                case XMLReader::WHITESPACE:
                case XMLReader::SIGNIFICANT_WHITESPACE:
                    $value .= $this->xml->value;
                    break;
            }
        }
        return $value;
    }

    protected function _attributes() {

        $arr = array();
        $count = $this->xml->attributeCount;

        for($i = 0; $i < $count; ++$i) {
            $this->xml->moveToAttributeNo($i);
            $arr[$this->xml->name] = $this->xml->value;
        }

        return $arr;
    }

    protected function template() {

        $src = $this->xml->getAttribute('src');
        if(strpos($src, '?') === FALSE)
            $src .= "?inherit=true";

        $this->xhtml[] = "\t{% template {$src} %}";
    }

}