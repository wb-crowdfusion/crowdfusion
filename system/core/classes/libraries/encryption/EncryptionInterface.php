<?php
/**
 * EncryptionInterface
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
 * @version     $Id: EncryptionInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * EncryptionInterface
 *
 * @package     CrowdFusion
 */
/**
 * Interface for Encryption, for encrypting/encoding data
 *
 * @package    CrowdFusion-Libraries
 * @subpackage Encryption
 * @version    $Id: EncryptionInterface.php 2012 2010-02-17 13:34:44Z ryans $
 **/

/**
 * Interface for Encryption, for encrypting and encoding data
 *
 * @package    CrowdFusion-Libraries
 * @subpackage Encryption
 **/
interface EncryptionInterface
{
    /**
     * Encodes the message.
     *
     * @param string $message The message that will be encoded
     *
     * @return string encoded message
     **/
    public function encode($message);

    /**
     * Decodes the message
     *
     * Not all implementations will support this method and should
     * throw EncryptionException if decoding is not possible.
     *
     * @param string $message The message to decode
     *
     * @return string decoded message
     **/
    public function decode($message);

    /**
     * Provides one-way encryption we'll use to store passwords in the database
     *
     * @param string $password The plaintext password
     *
     * @return string the encrypted password
     */
    public function encryptPassword($password);

    /**
     * Encrypts the given value
     *
     * @param string $value The value to encrypt
     *
     * @return string the encrypted version of $value
     */
    public function encrypt($value);

    /**
     * Decrypts the given value
     *
     * @param string $value The encrypted value to decrypt
     *
     * @return string the decrypted string
     */
    public function decrypt($value);

    /**
     * Secure a cookie value
     *
     * @param string  $value  unsecure value
     * @param integer $expire expiration time
     * @param string  $userid user id, or uses RequestContext->getUserRef
     *
     * @return string secured value
     */
    public function encryptSecureCookie($value, $expire, $userid = null);

    /**
     * Retrieve a secured cookie value
     *
     * @param string $value The secured value
     *
     * @return string the value stored in the secured cookie or false if unsuccessful
     */
    public function decryptSecureCookie($value);

} // END interface EncryptionInterface
