<?php
/**
 * NoncesInterface
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
 * @version     $Id: NoncesInterface.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * Interface for creating and verifying nonces
 *
 * A nonce stands for number used once (it is similar in spirit to a nonce word).
 * It is often a random or pseudo-random number issued in an authentication protocol
 * to ensure that old communications cannot be reused in replay attacks.
 *
 * The salt and expiration policy is to be determined by the implementation
 *
 * @package     CrowdFusion
 */
interface NoncesInterface
{

    /**
     * Verifies the validity of a nonce against the supplied action
     *
     * @param string $nonce  Full nonce string supplied by the request
     * @param string $action Arbitrary string action to verify nonce against
     *
     * @return boolean True if nonce is valid or false if not valid
     */
    public function verify($nonce, $action);

    /**
     * Creates a new nonce string for the given action, typically derived
     * from the user, a salt, and valid only for a given amount of time
     *
     * @param string $action Arbitrary string action to create nonce for
     *
     * @return string Nonce
     */
    public function create($action);


}