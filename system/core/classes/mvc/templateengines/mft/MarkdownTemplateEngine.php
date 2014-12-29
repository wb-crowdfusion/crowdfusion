<?php
/**
 * MarkdownTemplateEngine
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
 * @version     $Id: MarkdownTemplateEngine.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * MarkdownTemplateEngine
 *
 * @package     CrowdFusion
 */
class MarkdownTemplateEngine extends CFTemplateEngine{


    protected function filterTemplateContents($parsedContent, Template $template, &$globals)
    {
        require_once PATH_SYSTEM.'/vendors/PHP_Markdown_Extra_1.2.4/markdown.php';
        return Markdown($parsedContent);
    }



}
