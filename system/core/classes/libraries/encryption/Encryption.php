<?php
/**
 * Encryption
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
 * @version     $Id: Encryption.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Class for simple Encryption. Provides encoding/decoding methods
 * for use on any string and encryption (one-way) for passwords.
 *
 * @package     CrowdFusion
 */
class Encryption implements EncryptionInterface
{
    /**
     * The hash key to use for our "encryption"
     *
     * @var string
     **/
    protected $key;
    protected $includeSSLKey;

    protected $Crypt;
    protected $RequestContext;

    /**
     * Creates a new Encryption object with the specified key
     *
     * @param RequestContext $RequestContext                The Request Context
     * @param string         $encryptionSecretKey           A key to use for encryption and decryption.
     * @param boolean        $encryptionCookieIncludeSSLKey If true, then the secured cookie will integrate the servers SSL key
     */
    public function __construct(RequestContext $RequestContext, $encryptionSecretKey, $encryptionCookieIncludeSSLKey = false)
    {
        $this->RequestContext = $RequestContext;

        if ($encryptionSecretKey == '' || strlen($encryptionSecretKey) < 8)
            throw new EncryptionException('Encryption key error: Key must be at least 8 characters.');
        else
            $this->key = $encryptionSecretKey;

        $this->includeSSLKey = $encryptionCookieIncludeSSLKey;

    }

    /**
     * Encodes the message.
     *
     * @param string $message The message that will be encoded
     *
     * @return string encoded message
     **/
    public function encode($message)
    {
        return strtr(base64_encode($this->encrypt($message)), '+/=', '-_,');
    }

    /**
     * Decodes the message
     *
     * @param string $message The message to decode
     *
     * @return string decoded message
     **/
    public function decode($message)
    {
        return $this->decrypt(base64_decode(strtr($message,
                                    '-_,', // search
                                    '+/='))); // replace
    }

    /**
     * Provides one-way encryption we'll use to store passwords in the database
     *
     * @param string $password The plaintext password
     *
     * @return string the encrypted password
     */
    public function encryptPassword($password)
    {
        return md5(md5($password.$this->key));
    }


    /**
     * Decrypts the given value using the included phpseclib AES implementation
     *
     * @param string $value The encrypted value to decrypt
     * @param string $iv    (optional) The initialization vector
     *
     * @return string the decrypted string
     */
    public function decrypt($value, $iv = null)
    {
        if(empty($value))
            return false;

        include_once PATH_SYSTEM . '/vendors/phpseclib0/Crypt/Rijndael.php';
        include_once PATH_SYSTEM . '/vendors/phpseclib0/Crypt/AES.php';

        $this->Crypt = new Crypt_AES();
        $this->Crypt->setKey($this->key);

        if(!is_null($iv))
            $this->Crypt->setIV($iv);
        return $this->Crypt->decrypt($value);
    }

    /**
     * Encrypts the given value using the included phpseclib AES implementation
     *
     * @param string $value The value to encrypt
     * @param string $iv    (optional) the initialization vector
     *
     * @return string the encrypted version of $value
     */
    public function encrypt($value, $iv = null)
    {
        if(empty($value))
            return false;
        
        include_once PATH_SYSTEM . '/vendors/phpseclib0/Crypt/Rijndael.php';
        include_once PATH_SYSTEM . '/vendors/phpseclib0/Crypt/AES.php';

        $this->Crypt = new Crypt_AES();
        $this->Crypt->setKey($this->key);
        if (!is_null($iv))
            $this->Crypt->setIV($iv);
        return $this->Crypt->encrypt($value);
    }

    /**
     * Retrieve a secured cookie value
     *
     * @param string $value The secured value
     *
     * @return string the value stored in the secured cookie or false if unsuccessful
     */
    public function decryptSecureCookie($value)
    {
        $cookieValues = explode('|', $value, 4);
        if ((count($cookieValues) === 4) && ($cookieValues[1] == 0 || $cookieValues[1] >= time())) {
            $userid = $cookieValues[0];
            $expire = $cookieValues[1];
            $encrValue = $cookieValues[2];

            $key = hash_hmac('sha1', $userid.$expire, $this->key);
            $value = $this->decrypt(base64_decode(str_replace(' ', '+', $encrValue)), md5($expire));

            if ($this->includeSSLKey == true && ($sslKey = $this->Request->getServerAttribute('SSL_SESSION_ID')))
                $verifyKey = hash_hmac('sha1', $userid . $expire . $value . $sslKey, $key);
            else
                $verifyKey = hash_hmac('sha1', $userid . $expire . $value, $key);

            if (strcmp($verifyKey, $cookieValues[3]) === 0)
                return $value;
        }
        return false;
    }

    /**
     * Secure a cookie value
     *
     * The initial value is transformed with this protocol :
     *
     *  secureValue = username|expire|base64((value)k,expire)|HMAC(user|expire|value,k)
     *  where k = HMAC(user|expire, sk)
     *  and sk is server's secret key
     *  (value)k,md5(expire) is the result an cryptographic function (ex: AES256) on "value" with key k and initialisation vector = md5(expire)
     *
     * @param string  $value  unsecure value
     * @param integer $expire expiration time
     * @param string  $userid user id, or uses RequestContext->getUserRef
     *
     * @return string secured value
     */
    public function encryptSecureCookie($value, $expire, $userid = null)
    {
        $expire = (strcmp(strtolower($expire), 'never') === 0 ? time() + 60 * 60 * 24 * 6000 : $expire);

        if (is_null($userid))
            $userid = (string)$this->RequestContext->getUserRef();

        $key = hash_hmac('sha1', $userid.$expire, $this->key);
        $encrValue = base64_encode($this->encrypt($value, md5($expire)));

        if ($this->includeSSLKey == true && ($sslKey = $this->Request->getServerAttribute('SSL_SESSION_ID')))
            $verifyKey = hash_hmac('sha1', $userid . $expire . $value . $sslKey, $key);
        else
            $verifyKey = hash_hmac('sha1', $userid . $expire . $value, $key);

        $result = array($userid, $expire, $encrValue, $verifyKey);
        return(implode('|', $result));
    }


} // END class Encryption
