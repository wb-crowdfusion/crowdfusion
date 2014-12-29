<?php
/**
 * MetaPartial
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
 * @version     $Id: MetaPartial.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * MetaPartial
 *
 * @package     CrowdFusion
 * @property string $MetaName
 * @property string $MetaValueDisplay
 * @property mixed $MetaValue
 */
class MetaPartial extends Object
{
    /**
     * Builds the MetaPartial object
     *
     * @param mixed  $metaOrName Can be a string or an array or a Meta/MetaPartial object that will be used to define this object.
     *                            If a string, then it's assumed to be the Name for the meta.
     *                            If an array, the keys expected should match the {@link $fields}
     *                            If a Meta or MetaPartial object, then it will be used to build the MetaPartial
     * @param string $value      (optional) The value for this meta
     * @param string $sectionID  (optional) The section ID for this meta
     */
    public function __construct($metaOrName, $value = '')
    {
        $this->fields = array('MetaName'         => '');

        if ($metaOrName instanceof MetaPartial || $metaOrName instanceof Meta)
            $this->fields = $metaOrName->toArray();
        else {

            if (is_array($metaOrName))
                $this->fields = array_merge($this->fields, $metaOrName);
            else if (is_string($metaOrName)) {


                if (strpos($metaOrName, '#') === false) {
                    // assume first param is element
                    $this->fields['MetaName']      = $metaOrName;
                    if($value != '')
                        $this->fields['MetaValue']     = $value;
                } else {
                    // assume first param is tag string
                    if (preg_match("/(meta)?#([a-z0-9-]+)?(=((\")?([^\"]*)(\")?))?$/", $metaOrName, $valuematch)) {
                        $this->fields['MetaName'] = $valuematch[2];

                        if (isset($valuematch[6]))
                            $this->fields['MetaValue'] = $valuematch[6];
                    }
                }

            }
        }

        if (empty($this->fields['MetaName']))
            throw new MetaException('Invalid MetaPartial: No name was specified');

        $this->fields['MetaName'] = strtolower($this->fields['MetaName']);

        if (!SlugUtils::isSlug($this->fields['MetaName']))
            throw new MetaException('Invalid MetaPartial: "'.$this->fields['MetaName'] .'" must be valid slug');

        if (!empty($this->fields['MetaValue']))
            $this->fields['MetaValue'] = preg_replace("/\s+/s", ' ', $this->fields['MetaValue']);

    }

    /**
     * Returns true if the passed {@link $meta} matches this MetaPartial at least partially
     *
     * @param mixed $meta Can be a Meta or MetaPartial object
     *
     * @return boolean
     */
    public function match($meta)
    {
        if(!is_array($meta))
            $meta = $meta->toArray();

        foreach (array('MetaName', 'MetaValue') as $key) {
            if (!isset($this->fields[$key]))
                continue;

            if (strcmp(''.$meta[$key],
                    ''.$this->fields[$key]) !== 0)
                return false;
        }

        return true;
    }

    /**
     * Returns true if the passed {@link $meta} matches this MetaPartial exactly
     *
     * @param mixed $meta Can be a Meta or MetaPartial object
     *
     * @return boolean
     */
    public function matchExact($meta)
    {
        $metaArray = $meta->toArray();
        foreach (array('MetaName', 'MetaValue') as $key) {
            if (strcmp($metaArray[$key], $this->fields[$key]) !== 0)
                return false;
        }

        return true;
    }

    /**
     * Returns the string representation of this MetaPartial
     *
     * @return string
     */
    public function toString()
    {
        $metaString = '#';

        $metaString .= $this->fields['MetaName'];

        if (!empty($this->fields['MetaValue']))
            $metaString .= '="'. $this->fields['MetaValue'] .'"';

        return $metaString;
    }

    /**
     * Same as toString(), but allows PHP to natively convert the object
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }


}

?>
