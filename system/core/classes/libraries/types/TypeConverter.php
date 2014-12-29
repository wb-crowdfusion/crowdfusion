<?php
/**
 * TypeConverter
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
 * @version     $Id: TypeConverter.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * TypeConverter
 *
 * @package     CrowdFusion
 */
class TypeConverter implements TypeConverterInterface
{

    public function setInputClean(InputCleanInterface $InputClean)
    {
        $this->InputClean = $InputClean;
    }

    public function setDateFactory(DateFactory $DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }

    public function convertFromString(ValidationExpression $validation, $value, $rawValue = null, $fromStorage = false)
    {
        if(is_null($rawValue))
            $rawValue = $value;

        $vArray = $validation->getValidationArray();
        $datatype = $validation->getDatatype();

        switch($datatype)
        {
            case 'flag':
                if($value == false || strlen(trim($value)) == 0) {
                    $value = null;
                } else {
                    $value = 1;
                }
                break;

            //Clean HTML
            case 'html':
                $value = $this->InputClean->clean($rawValue,((array_key_exists('allowedtags',$vArray))?$vArray['allowedtags']:null));
                break;

            // Sanitize URL
            case 'url':
                $urlCleaned = URLUtils::safeURL($rawValue);
                if(!empty($urlCleaned))
                    $value = $urlCleaned;
                break;

            //Convert INT
            case 'int':
                if(!empty($value) && is_numeric(str_replace(',', '',$value)) == FALSE) {
                    throw new TypeConversionException("Cannot convert string value [{$value}] to integer");
                } else {
                    if($value === '')
                        $value = null;
                    else
                        $value = intval(str_replace(',', '',$value));
                }
                break;

            //Convert FLOAT
            case 'float':
                if(!empty($value) && is_numeric(str_replace(',', '',$value)) == FALSE) {
                    throw new TypeConversionException("Cannot convert string value [{$value}] to float");
                } else {
                    if($value === '')
                        $value = null;
                    else
                        $value = floatval(str_replace(',', '',$value));
                }
                break;

            //Convert BOOLEAN
            case 'boolean':
                $value = StringUtils::strToBool($value);
                break;

            //Convert DATE
            case 'date':

                if(!empty($value) && strlen(trim($value)) > 0) {
                    try {
                        if($fromStorage)
                            $value = $this->DateFactory->newStorageDate(trim($value));
                        else
                            $value = $this->DateFactory->newLocalDate(trim($value));

                    } catch(DateException $e) {
                        throw new TypeConversionException("Cannot convert string value [{$value}] to date");
                    }
                } else {
                    $value = null;
                }
                break;


        }
        unset($vArray);
        unset($datatype);
        unset($rawValue);
        unset($validation);

        return $value;
    }





}