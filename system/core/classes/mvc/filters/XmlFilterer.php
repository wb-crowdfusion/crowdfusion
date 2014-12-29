<?php
/**
 * XmlFilterer
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
 * @version     $Id: XmlFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * XmlFilterer
 *
 * @package     CrowdFusion
 */
class XmlFilterer extends AbstractFilterer
{

    protected function getDefaultMethod()
    {
        return "pretty";
    }



    /**
     * Formats an XML string
     *
     * Expected Param:
     *  value string XML snippet
     *
     * @return string
     */
    protected function pretty()
    {
        $xml = $this->getParameter('value');
        $level = $this->getParameter('level') != null ? intval($this->getParameter('level')) : 6;

        // get an array containing each XML element
        $xml = explode("\n", preg_replace('/>\s*</', ">\n<", $xml));

        // hold current indentation level
        $indent = 0;

        // hold the XML segments
        $pretty = array();

        // shift off opening XML tag if present
        if (count($xml) && preg_match('/^<\?\s*xml/', $xml[0])) {
            $pretty[] = array_shift($xml);
        }

        foreach ($xml as $el) {
            if (preg_match('/^<([\w])+[^>\/]*>$/U', $el)) {
                if($indent < 0) $indent = 0;
                // opening tag, increase indent
                $pretty[] = str_repeat('&nbsp;', $indent) . htmlentities($el,ENT_QUOTES);
                $indent += $level;
            } else {
                if (preg_match('/^<\/.+>$/', $el)) {
                    // closing tag, decrease indent
                    $indent -= $level;
                }
                $pretty[] = str_repeat('&nbsp;', abs($indent)) . htmlentities($el,ENT_QUOTES);
            }
        }

        return implode("<br/>", $pretty);
    }


}