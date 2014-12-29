<?php
/**
 * Nonces
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
 * @version     $Id: Nonces.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * A nonce stands for number used once (it is similar in spirit to a nonce word).
 * It is often a random or pseudo-random number issued in an authentication protocol
 * to ensure that old communications cannot be reused in replay attacks.
 *
 * This implementation uses a 6-hour expiration period.  The nonce is created using
 * the passed {@link $action}.
 *
 * @package     CrowdFusion
 */
class Nonces implements NoncesInterface
{

    protected $DateFactory;
    protected $Session;
    protected $RequestContext;

    protected $timePeriod;  // 6 hours
    protected $nonceKey;
    protected $acceptableTimePeriods;

    /**
     * Creates the Nonces object
     *
     * @param DateFactory         $DateFactory                A class for creating dates
     * @param EncryptionInterface $Encryption                 Encryption library
     * @param Session             $Session                    The current session
     * @param RequestContext      $RequestContext             The web context
     * @param integer             $nonceTimePeriod            The duration in seconds that a nonce is valid for. Default: 21600 (6 hours)
     */
    public function __construct(DateFactory $DateFactory, EncryptionInterface $Encryption, Session $Session, RequestContext $RequestContext, $nonceTimePeriod = 21600)
    {
        $this->DateFactory    = $DateFactory;
        $this->RequestContext = $RequestContext;
        $this->Session        = $Session;
        $this->Encryption     = $Encryption;

        $this->timePeriod            = $nonceTimePeriod;
    }

    /**
     * Verifies the validity of a nonce against the supplied action.
     *
     * $nonceAcceptableTimePeriods nonce checks are performed,
     * one for the current $nonceTimePeriod period and one for each previous $nonceTimePeriod period.
     * If any time period nonce matches, then the nonce is valid.
     *
     * @param string $nonce  Full nonce string supplied by the request
     * @param string $action Arbitrary string action to verify nonce against
     *
     * @return int Time period nonce was found in (ie. 1, 2, etc.) or false if not valid
     */
    public function verify($nonce, $action)
    {
        $value = $this->Encryption->decryptSecureCookie($nonce);

        if(!$value)
            return false;

        if(strcmp($value, $action) === 0)
            return true;

        return false;
    }

    /**
     * Creates a new nonce string for the given action, typically derived
     * from the user, a salt, and valid only for a given amount of time
     *
     * @param string $action Arbitrary string action to create nonce for
     *
     * @return string Nonce
     */
    public function create($action)
    {
        $expire = time() + $this->timePeriod; // 24 hour expiration

        $user_id = $this->RequestContext->getUserRef();
        if(is_null($user_id))
            $user_id = $this->Session->getID();

        return $this->Encryption->encryptSecureCookie(strtolower($action), $expire, (string)$user_id);
    }

}

?>
