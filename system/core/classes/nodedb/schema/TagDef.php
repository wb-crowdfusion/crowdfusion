<?php
/**
 * TagDef
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
 * @version     $Id: TagDef.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * TagDef represents in an object the Tag items that are defined in the XML.
 *
 * A tag is expected to have the following properties:
 *  Id       - unique slug that identifies the tag
 *  Title    - Title that provides a human readable description of what data the tag contains
 *  Quickadd - IF 'true' the tag widget will allow quick-adding of new items through this tag
 *  Multiple - If 'true' then there can be more than one tag of this type on the object
 *  Tag_type - Can be 'in' or 'out'. Default 'out'. Specifies tag direction.
 *  Partial  - defines the TagPartial object
 *
 * @package     CrowdFusion
 * @property string $Id
 * @property string $Title
 * @property boolean $Sortable
 * @property boolean $Quickadd
 * @property boolean $Multiple
 * @property string $Direction
 * @property string $Filter
 * @property boolean $Fieldlike
 * @property boolean $Treeorigin
 * @property TagPartial $Partial
 */
class TagDef extends Object
{


}