<?php
/**
 * SQLDuplicateKeyException
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
 * @version     $Id: SQLDuplicateKeyException.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * SQLDuplicateKeyException
 *
 * @package     CrowdFusion
 */
class SQLDuplicateKeyException extends Exception
{

    protected $sql = false;

    /**
     * Creates the exception.
     *
     * Note, $sql is the first param
     *
     * @param string $sql     The SQL that caused the error
     * @param string $message The error message
     * @param string $code    The error code
     */
    public function __construct($sql, $message, $code = 0)
    {
        parent::__construct($message, $code);
        $this->sql = $sql;
    }

    /**
     * Returns the stored SQL
     *
     * @return string
     */
    public function getSQL()
    {
        return $this->sql;
    }

}