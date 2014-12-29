<?php
/**
 * Every call to load the object within the DI container returns the same
 * singular instance.
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
 * @version   $Id: SingletonPattern.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Every call to load the object within the DI container returns the same
 * singular instance.
 *
 * @package   CrowdFusion
 */
class SingletonPattern extends AbstractPattern {

	public $instance = NULL;

	public function instance() {
		if (is_null($this->instance)) {

            //$init1 = microtime(TRUE);

            $this->instance = $this->instantiator->instantiate();

            //$init2 = microtime(TRUE);

            //error_log(str_repeat(' ', ($GLOBALS['depth']*4)).'instantiate '.$this->getClassName().': '.($init2 - $init1)*1000);
        }
		return $this->instance;
	}

}
