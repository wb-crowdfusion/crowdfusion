<?php
/**
 * MarkdownFilterer
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
 * @version     $Id: MarkdownFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * MarkdownFilterer
 *
 * @package     CrowdFusion
 */
class MarkdownFilterer extends AbstractFilterer
{

    protected function getDefaultMethod()
    {
        return "markdown";
    }

    public function markdown()
    {
        $markdownText = $this->getParameter('value') ."\n\n";

        require_once PATH_SYSTEM.'/vendors/PHP_Markdown_Extra_1.2.4/markdown.php';

        if($this->getParameter('tags') != null)
            foreach($this->getParameter('tags') as $tag) {
                        $markdownText .=  "[{$tag['TagValueDisplay']}]: {$tag['TagLinkURL']} \"{$tag['TagLinkTitle']}\"\n";
            }

        return Markdown($markdownText);
    }


}