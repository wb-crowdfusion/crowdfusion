<?php
/**
 * ObjectService
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
 * @version     $Id: ObjectService.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * ObjectService
 *
 * @package     CrowdFusion
 */
class ObjectService {

//	private $aliases = array ();

	private $name, /*$description,*/ $pattern;

	public function __construct($name/*, $description*/) {
		$this->name = $name;
//		$this->description = $description;
	}

	public function getName() { return $this->name; }
//	public function getDescription() { return $this->description; }
	public function setPattern(AbstractPattern $patternObject) { $this->pattern = $patternObject; }

//	public function addAlias($name) {
//		$this->aliases[] = $name;
//	}

	public function instance() {
		$instance = $this->pattern->instance();
		return $instance;
	}

	public function getClassName() {
		return $this->pattern->getClassName();
	}

}
