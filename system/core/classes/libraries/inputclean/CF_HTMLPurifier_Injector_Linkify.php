<?php
/**
 * Injector that converts http, https and ftp text URLs to actual links.
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
 * @version     $Id: CF_HTMLPurifier_Injector_Linkify.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Injector that converts http, https and ftp text URLs to actual links.
 *
 * @package     CrowdFusion
 */
class CF_HTMLPurifier_Injector_Linkify extends HTMLPurifier_Injector
{

    public $name = 'Linkify';
    public $needed = array('a' => array('href'));

    public function handleText(&$token) {
        if (!$this->allowsElement('a')) return;

        if (strpos($token->data, '://') === false) {
            // our really quick heuristic failed, abort
            // this may not work so well if we want to match things like
            // "google.com", but then again, most people don't
            return;
        }

        // there is/are URL(s). Let's split the string:
        // Note: this regex is extremely permissive
        $bits = preg_split("/([a-zA-Z]+[:\/\/]+[A-Za-z0-9\-_]+\\.+[A-Za-z0-9\.,\/%&=\?\-_#@]+)/S", $token->data, -1, PREG_SPLIT_DELIM_CAPTURE);

        $token = array();

        // $i = index
        // $c = count
        // $l = is link
        for ($i = 0, $c = count($bits), $l = false; $i < $c; $i++, $l = !$l) {
            if (!$l) {
                if ($bits[$i] === '') continue;
                $token[] = new HTMLPurifier_Token_Text($bits[$i]);
            } else {
                $token[] = new HTMLPurifier_Token_Start('a', array('href' => $bits[$i]));
                $token[] = new HTMLPurifier_Token_Text($bits[$i]);
                $token[] = new HTMLPurifier_Token_End('a');
            }
        }

    }

}

?>
