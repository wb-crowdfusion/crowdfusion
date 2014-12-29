<?php
/**
 * TemplateCacheInterface
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
 * @version     $Id: TemplateCacheInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Template cache stores cache information for our template system
 *
 * @package     CrowdFusion
 */
interface TemplateCacheInterface
{

    /**
     * Stores the specified template to cache using the cacheKey specified.
     *
     * @param string   $cacheKey The cacheKey for the Template
     * @param Template $template The template to store in the cache
     *
     * @return true
     */
    public function putTemplate($cacheKey, Template $template);

    /**
     * Retrieves the template from Cache specified by {@link $cacheKey}
     *
     * @param string $cacheKey The cachekey of the template to fetch
     *
     * @return Template Returns the specified template, or false if unsuccessful.
     */
    public function get($cacheKey);

    /**
     * Returns a cacheKey for the specified Template
     *
     * @param Template $template The template to generate the cacheKey for
     * @param array    $globals  An array of globals set on this template
     *
     * @return string The cache key
     */
    public function getTemplateCacheKey(Template $template, $globals);


    // deletes for all sites/domains
    // public function delete($templateName, array $templateParameters);
    //
    // public function deleteByDataSource($datasource, array $parameters = array(), Sites $site = null);
    //
    // public function deleteByTemplateParameters(array $parameters, Sites $site = null);
    //
    // public function deleteBySite(Sites $site);
    //
    // public function deleteAll();


}