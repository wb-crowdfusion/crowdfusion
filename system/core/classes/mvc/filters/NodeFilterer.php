<?php
/**
 * NodeFilterer
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
 * @version     $Id: NodeFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * NodeFilterer
 *
 * @package     CrowdFusion
 */
class NodeFilterer extends AbstractFilterer
{


    public function hasAspect()
    {
        $element = $this->getLocal('Element');
        $aspectSlugs = $element['AspectSlugs'];

        return in_array(ltrim(strtolower($this->getRequiredParameter('aspect')), '@'), (array)$aspectSlugs);
    }


    /**
     * Encodes the current records (from the locals array) into a JSON object.
     *
     * Expected Params:
     *  keys  string A single string or a list of comma-separated strings representing the keys to encode
     *               from the locals array.  If this param is not provided a default list of well-known keys is used.
     *
     * @return string
     */
    public function jsonEncode()
    {
        $keys = $this->getEncodeKeys();

        return JSONUtils::encode(ArrayUtils::flattenObjectsUsingKeys($this->locals, $keys));
    }

    public function xmlEncode()
    {
        $keys = $this->getEncodeKeys();

        return $this->xmlify(ArrayUtils::flattenObjectsUsingKeys($this->locals, $keys), $this->getParameter('rootNodeName'));
    }

    protected function getEncodeKeys()
    {
        $keys = $this->getParameter('keys');

        if($keys == null)
            $keys = $this->defaultEncodeKeys();
        else if(strpos($keys,',') !== FALSE)
            $keys = explode(',', $keys);
        else
            $keys = array($keys);

        return $keys;

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
//                'Site',
                'RecordLink',
//                'Cheaters'
        );
    }


}
