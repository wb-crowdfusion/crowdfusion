<?php
/**
 * MetaDef
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
 * @version     $Id: MetaDef.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * MetaDef represents in an object the Meta items that are defined in the XML.
 *
 * A meta is expected to have the following properties:
 *  id    - unique slug that identifies the meta property
 *  title - Title that provides a human readable description of what data the field contains
 *
 * @package     CrowdFusion
 * @property string $Id
 * @property string $Title
 * @property string $Datatype
 * @property string $Default
 * @property ValidationExpression $Validation
 */
class MetaDef extends Object
{

}