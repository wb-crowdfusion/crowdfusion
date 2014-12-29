<?php
/**
 * ModelMapper
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
 * @version     $Id: ModelMapper.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Provides functions to translate between arrays and ModelObjects.
 * Also, provides a way to get a new ModelObject instance with defaults filled.
 *
 * @package     CrowdFusion
 */
class ModelMapper
{
    protected $InputClean  = null;
    protected $DateFactory = null;

    /**
     * [IoC] Injects the InputClean object
     *
     * @param InputClean $InputClean An instance of InputClean
     *
     * @return void
     */
    public function setInputClean(InputClean $InputClean)
    {
        $this->InputClean = $InputClean;
    }

    /**
     * [IoC] Injects the DateFactory
     *
     * @param DateFactory $DateFactory The DateFactory, where all the cool girls are!
     *
     * @return void
     */
    public function setDateFactory(DateFactory $DateFactory)
    {
        $this->DateFactory = $DateFactory;
    }

    /**
     * Fills all the persistent fields in {@link &$model} from values
     * in {@link $fields}
     *
     * @param array       $fields An array with fieldnames as the keys and field values as the values
     * @param ModelObject &$model The ModelObject that will have persistent fields filled
     *
     * @return ModelObject Persistent Fields filled
     */
    public function persistentArrayToModel(array $fields, ModelObject &$model)
    {
        $fields = array_intersect_key($fields, array_flip($model->getPersistentFields()));

        foreach ($fields as $key => $value) {
            $datatype = $model->getDatatype($key);

            //Convert INT
            if ($datatype === 'int') {
                $fields[$key] = intval($value);
            }

            //Convert FLOAT
            if ($datatype === 'float') {
                $fields[$key] = floatval($value);
            }

            //Convert BOOLEAN
            if ($datatype === 'boolean') {
                $fields[$key] = StringUtils::strToBool($value);
            }
        }

        $model->setFromArray($fields);

        return $model;
    }

    /**
     * Fills in all the default fields on {@link &$model}
     *
     * @param ModelObject &$model The model object to fill
     *
     * @return void
     */
    public function defaultsOnModel(ModelObject &$model)
    {
        foreach ($model->getPersistentFields() as $key) {
            $default = $model->getDefault($key);

            if ($model->getDatatype($key) === 'date' && $default !== null)
                $default = $this->DateFactory->newStorageDate($default);

            if ($default !== null)
                $model->$key = $default;
        }
    }

    /**
     * Fills the {@link &$model} with values from the {@link $fields}
     *
     * @param array       $fields    An array with field names as keys and the values to store.
     * @param array       $rawFields An array with field names as keys and the raw value as the value.
     *                                  Used for 'html' typed fields, for raw access to the field value.
     * @param ModelObject &$model    The ModelObject we're filling
     * @param Errors      $errors    The Errors object to update if there are any field errors
     *
     * @return ModelObject Returns &$model after modifications
     */
    public function inputArrayToModel(array $fields, array $rawFields, ModelObject &$model, Errors $errors)
    {
        $fields = array_intersect_key($fields, array_flip($model->getPersistentFields()));

        foreach ($fields as $key => $value) {
            $datatype = $model->getDatatype($key);

            // Clean HTML
            if ($datatype === 'html') {
                $validation   = $model->getValidation($key);
                $fields[$key] = $this->InputClean->clean($rawFields[$key],
                                                        array_key_exists('allowedtags', $validation) ?
                                                            $validation['allowedtags']
                                                        :   null);
            }

            // Convert INT
            if ($datatype === 'int' && !empty($value)) {
                if (is_numeric($value) === false)
                    $errors->addFieldError('invalid',
                                            get_class($model) . '.' . $key,
                                            'field',
                                            $model->getFieldTitle($key),
                                            $value,
                                            "Cannot convert string [{$value}] to integer.");
                else
                    $fields[$key] = intval($value);
            }

            // Convert FLOAT
            if ($datatype === 'float' && !empty($value)) {
                if (is_numeric($value) === false)
                    $errors->addFieldError('invalid',
                                            get_class($model) . '.' . $key,
                                            'field',
                                            $model->getFieldTitle($key),
                                            $value,
                                            "Cannot convert string [{$value}] to float.");
                else
                    $fields[$key] = floatval($value);
            }

            // Convert BOOLEAN
            if ($datatype === 'boolean')
                $fields[$key] = StringUtils::strToBool($value);

            // Convert DATE
            if ($datatype === 'date' && strlen(trim($value)) > 0) {
                try {
                    $fields[$key] = $this->DateFactory->newLocalDate(trim($value));
                } catch(DateException $e) {
                    $errors->addFieldError('invalid',
                                            get_class($model) . '.' . $key,
                                            'field',
                                            $model->getFieldTitle($key),
                                            $value,
                                            "Cannot convert string [{$value}] to date.");
                }
            }
        }

        $model->setFromArray($fields);

        return $model;
    }


    /**
     * Returns an array of only the persistent fields of {@link $model}
     *
     * @param ModelObject $model The modelObject to analyze
     *
     * @return array An array with persistent field names as keys with their values
     */
    public function modelToPersistentArray(ModelObject $model)
    {
        $persistent = array_intersect_key($model->toArray(), array_flip($model->getPersistentFields()));

        return $persistent;
    }

}

?>
