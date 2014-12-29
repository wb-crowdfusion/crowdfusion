<?php
/**
 * SimpleXMLExtended
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
 * @version     $Id: SimpleXMLExtended.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Adds a few new methods to SimpleXML for namespace support and ease of access.
 *
 * @package     CrowdFusion
 */
class SimpleXMLExtended extends SimpleXMLElement
{

    /**
     * Retrieves and attribute by name
     *
     * @param string $name The name of the attribute to get
     *
     * @return mixed Returns nothing if not found, or a string for the value of the found attribute
     */
    public function attribute($name)
    {
        $v = $this->attributes()->$name;
        if(!empty($v))
            return (string)$v;
    }


    /**
     * xpathNS() register namespaces for an xpath query
     *
     * @param string $path       The xpath query to run
     * @param array  $namespaces The namespaces in format like [prefix => ns]
     *
     * @see SimpleXML::registerXPathNamespace()
     *
     * @return mixed An array of SimpleXMLElement matching items or false on error
     */
    public function xpathNS($path, $namespaces)
    {
        foreach ($namespaces as $prefix => $ns)
            $this->registerXPathNamespace($prefix, $ns);

        return $this->xpath($path);
    }

    /**
     * Queries for a single result of an xpath query respecting the namespaces given
     *
     * @param string $path       The xpath query
     * @param array  $namespaces The namespaces [prefix => ns]
     *
     * @see SimpleXML::registerXPathNamespace()
     *
     * @return mixed The SimpleXMLElement that matches the xpath query or FALSE
     */
    public function xpathNSOne($path, $namespaces)
    {
        $result = $this->xpathNS($path, $namespaces);
        if(is_array($result))
            return current($result);

        return $result;

    }

    /**
     * Query an xpath and return the first result
     *
     * @param string $path The xpath query
     *
     * @return SimpleXMLElement The matching result or FALSE
     */
    public function xpathOne($path)
    {
        $result = $this->xpath($path);
        if(is_array($result))
            return current($result);

        return $result;

    }

    /**
     * Adds text in CDATA format to the element
     *
     * @param string $cdata_text The data that needs cdata'ing
     *
     * @return void
     */
    public function addCData($cdata_text)
    {
        $node= dom_import_simplexml($this);
        $no = $node->ownerDocument;
        $node->appendChild($no->createCDATASection($cdata_text));
    }

    /**
     * Adds a child to the current element, but with automatic CDATA support.
     *
     * @param string $name      The name of the child element to add
     * @param string $value     The value of the child element
     * @param string $namespace If specified, use this namespace
     *
     * @return SimpleXMLElement a SimpleXMLElement object representing the child added to the XML node.
     */
    public function addChild($name, $value=null, $namespace=null)
    {
        $esc_val = utf8_encode(htmlentities($value, ENT_QUOTES, 'UTF-8', false));
        if ($value == $esc_val)
            return parent::addChild($name, $esc_val, $namespace);
        else {
            $xml_field = parent::addChild($name, '', $namespace);
            $xml_field->addCData($esc_val);
            $xml_field->addAttribute('isCDATA', true);

            return $xml_field;
        }
    }

    public function addXMLElement(SimpleXMLExtended $source)
    {
        $new_dest = $this->addChild($source->getName(),strval($source));

        foreach($source->attributes() as $key => $value)
            if($key != 'isCDATA')
                $new_dest->addAttribute($key, $value);

        foreach ($source->children() as $child)
            $new_dest->addXMLElement($child);


    }

    /**
     * Appends the specified {@link $child} to the current element
     *
     * @param SimpleXMLExtended $child The child element to append
     *
     * @return this
     */
    public function append(SimpleXMLExtended $child)
    {
        $node1   = dom_import_simplexml($this);
        $dom_sxe = dom_import_simplexml($child);
        $node2   = $node1->ownerDocument->importNode($dom_sxe, true);
        $node1->parentNode->appendChild($node2);

        return $this;
    }

    /**
     * Replaces the current element with the element specified
     *
     * @param SimpleXMLExtended $child The element to replace me
     *
     * @return this
     */
    public function replace(SimpleXMLExtended $child)
    {
        $node1 = dom_import_simplexml($this);
        $dom_sxe = dom_import_simplexml($child);
        $node2 = $node1->ownerDocument->importNode($dom_sxe, true);
        $node1->parentNode->replaceChild($node2, $node1);

        return $this;
    }

    /**
     * Retrieves all the children of the specified element
     *
     * @param string $name The element to find children for
     *
     * @return array Array of child elements
     */
    public function childNodes($name = null)
    {
        if(empty($this->$name))
            return array();
        return $this->$name->children();
    }

    /**
     * Returns a properly indented form of the current XML tree
     *
     * @param string $level The number of spaces to use as indentation.
     *
     * @return string Beautifully formatted XML code that will make your mom smile. Use your manners!
     *
     * In the event that pretty-printing fails (e.g. due to memory constraints), defaults to
     * returning $this->asXML().
     */
    public function asPrettyXML($level = 4)
    {
        // get an array containing each XML element
        $xml = preg_replace('/<((\S+)([^>]+)?)><\/\2>/s', "<$1/>\n", $this->asXML());
        $xml = explode("\n", preg_replace('/>\s*</s', ">\n<", $xml));

        // hold current indentation level
        $indent = 0;

        // hold the XML segments
        $pretty = array();

        // shift off opening XML tag if present
        if (count($xml) && preg_match('/^<\?\s*xml/', $xml[0])) {
            $pretty[] = array_shift($xml);
        }

        foreach ($xml as $el) {
            if (preg_match('/^<([\w])+[^>]*[^\/]>$/U', $el)) {
                // opening tag, increase indent
                $pretty[] = str_repeat(' ', $indent) . $el;
                $indent += $level;
            } else {
                if (preg_match('/^<\/.+>$/', $el)) {
                    // closing tag, decrease indent
                    $indent -= $level;
                }
                if(preg_match('/^</', $el))
                    $pretty[] = str_repeat(' ', abs($indent)) . $el;
                else
                    $pretty[] = $el;
            }
        }

        $prettyXML = implode("\n", $pretty);

        // The above implementation relies extensively on regexp functions, which can fail due to config settings,
        // yet not throw an exception (see http://nz.php.net/manual/en/function.preg-last-error.php). Rather
        // than check every call, simply fall back to $this->asXML() if the pretty-print result winds up
        // empty (which is the likely result of such a failure).
        return (!empty($prettyXML) ? $prettyXML : $this->asXML());
    }

    /**
     * Override parent xpath function to return empty array if boolean is returned
     *
     */
    public function xpath($path)
    {
        $result = parent::xpath($path);
        if(empty($result))
            return array();

        return $result;
    }


}
