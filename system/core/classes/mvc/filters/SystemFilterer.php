<?php
/**
 * SystemFilterer
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
 * @version     $Id: SlugsFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * SystemFilterer
 *
 * @package     CrowdFusion
 */
class SystemFilterer extends AbstractFilterer
{

    protected function getDefaultMethod()
    {
        return "maxUploadSize";
    }

    public function maxUploadSize()
    {
        return FileSystemUtils::humanFileSize(FileSystemUtils::iniValueInBytes(ini_get('upload_max_filesize')));
    }
}