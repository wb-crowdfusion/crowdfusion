<?php
/**
 * EncryptionFilterer
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
 * @version     $Id: EncryptionFilterer.php 2012 2010-02-17 13:34:44Z ryans $
 */

/**
 * EncryptionFilterer
 *
 * @package     CrowdFusion
 */
class EncryptionFilterer extends AbstractFilterer
{

    public function setEncryption(EncryptionInterface $Encryption)
    {
        $this->Encryption = $Encryption;
    }

    protected function getDefaultMethod()
    {
        return "encrypt";
    }

    protected function encrypt()
    {
        $val = $this->getRequiredParameter('value');

        return $this->Encryption->encode($val);
    }

}