<?php
/**
 * TemplateFilterer
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
 * @version     $Id: TemplateFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * TemplateFilterer
 *
 * @package     CrowdFusion
 */
class TemplateFilterer extends AbstractFilterer
{

    public function setTemplateService(TemplateService $TemplateService)
    {
        $this->TemplateService = $TemplateService;
    }

    /**
     * Returns TRUE if the template specified by 'name' exists.
     *
     * Expected Params:
     *  name string The name of the template to look for
     *
     * @return boolean
     */
    public function exists()
    {
        $file = $this->getParameter('name');

        return $this->TemplateService->fileExists($file);
    }


}