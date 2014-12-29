<?php
/**
 * ActionException
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
 * @version     $Id: ActionException.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * ActionException
 *
 * @package     CrowdFusion
 */
class ActionException extends Exception {

    private $view = false;
    private $data = false;

    public function __construct($view = false, $data = false) {
        parent::__construct();
        $this->view = $view;
        $this->data = $data;
    }

    public function getView() {
        return $this->view;
    }

    public function getData() {
        return $this->data;
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}," .
                "view: [{$this->view}]\n";
    }

}