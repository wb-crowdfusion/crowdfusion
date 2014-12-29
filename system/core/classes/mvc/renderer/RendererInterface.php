<?php
/**
 * RendererInterface
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
 * @version     $Id: RendererInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * RendererInterface
 *
 * @package     CrowdFusion
 */
interface RendererInterface {

    /**
     *
     *
     * @throws Exception If template engine doesn't support requested content type (thrown from TemplateEngine)
     * @throws Exception If no template engine is capable of rendering the view template supplied
     */
    public function renderView(View $view, $contentType, $cacheEnabled = true);

    public function processTemplate(Template $template, $contentType, &$globals, $isFinalPass = false, $isDependentPass = false);
}
?>
