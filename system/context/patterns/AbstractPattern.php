<?php
/**
 * Abstract class used for defining pattern (or scope) classes
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
 * @version   $Id: AbstractPattern.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Abstract class used for defining pattern (or scope) classes
 *
 * A pattern class represents the model or pattern for instantiation within the
 * container.  Examples include Singleton or Prototype.
 *
 * @package   CrowdFusion
 */
abstract class AbstractPattern {

	protected $instantiator;

	public function __construct($instantiator) {
		$this->instantiator = $instantiator;
	}

	public function getClassName() {
		return $this->instantiator->className;
	}

	public abstract function instance();

}
