<?php
/**
 * SlugsFilterer
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
 * SlugsFilterer
 *
 * @package     CrowdFusion
 */
class SlugsFilterer extends AbstractFilterer
{


    protected function getDefaultMethod()
    {
        return "create";
    }

    /**
     * Returns a slug for the specified parameter
     *
     * Expected Parameters:
     *  value string The string to turn into a slug
     *
     * @return string
     */
    public function create()
    {
        $slug = $this->getParameter('value');
        $allowSlashes = StringUtils::strToBool($this->getParameter('allowSlashes'));

        return SlugUtils::createSlug($slug,$allowSlashes);
    }

    public function unsluggify()
    {
        $slug = $this->getParameter('value');

        return SlugUtils::unsluggify($slug);
    }
}