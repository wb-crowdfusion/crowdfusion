<?php
/**
 * FormFilterer
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
 * @version     $Id: FormFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * FormFilterer
 *
 * @package     CrowdFusion
 */
class FormFilterer extends AbstractFilterer
{

    public function setInputClean(InputCleanInterface $InputClean)
    {
        $this->InputClean = $InputClean;
    }


    /**
     * Returns all the fields passed into 'fields' as hidden fields to submit
     *
     * Expected Param:
     *  fields array A PHP array that needs to be converted to hidden fields
     *
     * @return string
     */
    public function hiddenFormFields()
    {
        $fields = $this->getParameter('fields');

        if (empty($fields))
            return '';

        $html_array = ArrayUtils::htmlArrayFlatten($fields);

        foreach ($html_array as $name => $value) {
            $name  = htmlentities($name, ENT_QUOTES);
            $value = htmlentities($value, ENT_QUOTES);

            $output[] = "<input type=\"hidden\" name=\"$name\" value=\"$value\" />";
        }

        return join("\n", $output);
    }

    /**
     * Converts text into a form suitable for editing in a textarea.
     *
     * Expected Params:
     *  value string
     *
     * @return string
     */
    public function unAutoParagraph()
    {
        $comments = $this->getParameter('value');

        return $this->InputClean->unAutoParagraph($comments);
    }
}