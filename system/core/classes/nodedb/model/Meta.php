<?php
/**
 * Meta
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
 * @version     $Id: Meta.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Meta
 *
 * @package     CrowdFusion
 * @property int $MetaID
 * @property string $MetaName
 * @property string $MetaStorageDatatype
 * @property string $MetaValidationDatatype
 * @property mixed $MetaValue
 * @property int $MetaSectionID
 */
class Meta extends Object
{

    /**
     * A holder for the meta fields. Expected keys are:
     *  MetaID (when loaded from the db)
     *  MetaName
     *  MetaValue
     *
     * @var array
     */
    protected $fields = array();

    /**
     * Builds the Meta object
     *
     * @param mixed  $metaOrName Can be a string or an array or a Meta object that will be used to define this object.
     *                            If a string, then it's assumed to be the Name for the meta.
     *                            If an array, the keys expected should match the {@link $fields}
     *                            If a Meta object, then it will be cloned.
     * @param string $value      (optional) The value for this meta
     * @param string $sectionID  (optional) The section ID for this meta
     */
    public function __construct($metaOrName, $value = '')
    {
        if ($metaOrName instanceof Meta)
            $this->fields = $metaOrName->toArray();
        else {
            if (is_array($metaOrName))
                foreach($metaOrName as $key => $val){
                    $this->fields[$key] = $val;
                    unset($key);
                    unset($val);
                }
            else if (is_string($metaOrName)) {

                // assume first param is element
                if (strpos($metaOrName, '=') === false || !empty($value)) {
                    $this->fields['MetaName']      = $metaOrName;
                    $this->fields['MetaValue']     = $value;
                } else
                    throw new MetaException('Creating meta using strings is deprecated, please use array or parameters');
            }
        }
        if(isset($this->fields['NoValidation']))
            return;

        if(empty($this->fields['MetaName'])) {
            throw new MetaException('Invalid meta: No MetaName was specified');
        }

        $this->fields['MetaName'] = strtolower($this->fields['MetaName']);

        if(!SlugUtils::isSlug($this->fields['MetaName']))
            throw new MetaException('Invalid meta: "'.$this->fields['MetaName'] .'" must be valid slug');

    }

    /**
     * Returns the sectionID for this meta
     *
     * @return integer
     */
    public function getMetaSectionID()
    {
        return empty($this->fields['MetaSectionID']) ? 0
                    : $this->fields['MetaSectionID'];
    }

    /**
     * Determines if the passed {@link $meta} is a valid Meta object or not
     *
     * @param mixed $meta Can be an array or a Meta object.
     *
     * @return boolean
     */
    public static function isValidMeta($meta)
    {
        try {
            new Meta($meta);

            return true;
        } catch(MetaException $e) {
            return false;
        }
    }

    /**
     * Returns true if the passed Meta object is an exact match against this one
     *
     * @param Meta $meta The meta object to compare
     *
     * @return boolean
     */
    public function matchExact(Meta $meta)
    {
        $metaArray = $meta->toArray();
        foreach (array('MetaName', 'MetaValue') as $key) {
            if (strcmp($metaArray[$key], $this->fields[$key]) !== 0)
                return false;
        }

        return true;
    }

    /**
     * Returns a string representing the meta object
     *
     * @return string
     */
    public function toString()
    {
        $metaString   = 'meta#';
        $metaString  .= $this->fields['MetaName'];

        if(!empty($this->fields['MetaValue']))
            $metaString .= '="'. $this->fields['MetaValue'] .'"';

        return $metaString;
    }

    public function getID()
    {
        return $this->fields['MetaName'];
    }

    public function getValue()
    {
        return $this->fields['MetaValue'];
    }

    /**
     * Same as toString(), but allows PHP to natively convert the object
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->MetaValue;
    }

}

?>
