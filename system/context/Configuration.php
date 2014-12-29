<?php
/**
 * Configuration
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
 * @package   CrowdFusion
 * @copyright 2009-2010 Crowd Fusion Inc.
 * @license   http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version   $Id: Configuration.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Configuration
 *
 * @package   CrowdFusion
 */
class Configuration
{


    public function loadContextFile($contextXML, $ignoreInfo = false) {

        $config = array('objects'=> array(), 'propertyFiles' => array());

        if(file_exists($contextXML)) {

            $basedir = dirname($contextXML);

            $xml = ContextUtils::parseXMLFile($contextXML);

            foreach($xml as $name => $node) {

                switch((string)$name) {

                    case 'info':
                        if($ignoreInfo)
                            break;

                        if(!empty($node->priority))
                            $config['priority'] = (int)$node->priority;

                        if(!empty($node->description))
                            $config['description'] = (string)$node->description;

                        if(!empty($node->title))
                            $config['title'] = (string)$node->title;

                        if(!empty($node->version))
                            $config['version'] = (string)$node->version;

                        if(!empty($node->provider))
                            $config['provider'] = (string)$node->provider;
                        break;

                    case 'import':
                        $importFile = ''.$node;
                        if($importFile != '')
                            $config = array_merge_recursive($config, $this->loadContextFile($basedir.DIRECTORY_SEPARATOR.$importFile, $ignoreInfo = true));
                        break;

                    case 'property-files':
                        foreach($node as $resourceFile) {
                            if($resourceFile->getName() == 'property-file' && ''.$resourceFile != '')
                                $config['propertyFiles'][] = (string)$basedir.DIRECTORY_SEPARATOR.$resourceFile;
                        }

                        break;
                    case 'objects':
                        foreach($node as $objectNode) {
                            if($objectNode->getName() == 'object') {
                                $object = array();
                                foreach($objectNode->attributes() as $name => $value)
                                    $object[$name] = (string)$value;

                                foreach($objectNode as $childNode) {

                                    switch($childNode->getName()) {

                                        case 'alias':
                                            $object['aliases'][] = (string)$childNode;
                                            break;

                                        case 'constructor-arg':
//                                            if(($name = (string)$childNode->attributes()->name) != '')
//                                                $object['constructor-args'][$name] = $this->getPropertyValue($childNode);
//                                            else
                                                $object['constructor-args'][] = $this->getPropertyValue($childNode);
                                            break;

                                        case 'property':
                                            if(($name = (string)$childNode->attributes()->name) != '')
                                                $object['properties'][$name] = $this->getPropertyValue($childNode);
                                            else
                                                throw new ApplicationContextException('Object "'.$object['id'].'" property is missing "name" attribute.');
//                                                $object['properties'][] = $this->getPropertyValue($childNode);
                                            break;

                                        case 'invoke':
                                            $invokeMethod = array('name' => (string)$childNode->attributes()->name);
                                            foreach($childNode as $methodArgNode) {
                                                if(($name = (string)$methodArgNode->attributes()->name) != '')
                                                    $invokeMethod['method-args'][$name] = $this->getPropertyValue($methodArgNode);
                                                else
                                                    $invokeMethod['method-args'][] = $this->getPropertyValue($methodArgNode);
                                            }
                                            $object['invokes'][] = $invokeMethod;
                                            break;

                                        case 'initialize-method':
                                            $object['initialize-method'] = (string)$childNode->attributes()->name;
                                            break;

                                    }


                                }


                                $config['objects'][] = $object;
                            }
                        }
                        break;

                    case 'events':
                        foreach($node as $bindNode) {
                            $event = array();
                            foreach($bindNode->attributes() as $name => $value)
                                $event[$name] = (string)$value;

                            $config['events'][strtolower($bindNode->getName())][] = $event;
                        }
                        break;

                }
            }

        }

        return $config;
    }


    protected function getPropertyValue($node) {

        $value = array();
        $foundVal = false;
        foreach($node->attributes() as $name => $v) {
            switch($name) {
                case 'ref':
                case 'constant':
                case 'property':
                case 'value':
                    $foundVal = true;
            }
            $value[$name] = (string)$v;
        }

        if($node->children()->count() > 0) { //array
            $i = 0;
            foreach($node->children() as $valueNode) {

                $key = (string)$valueNode->attributes()->key;
                if(!$key) $key = (int)$valueNode->attributes()->index;
                if(!$key) $key = $i;

                $value['array'][$key] = $this->getPropertyValue($valueNode);
                $i++;
            }
        } else {
            if(!$foundVal) $value['value'] = (string)$node;
        }

        return $value;
    }

}