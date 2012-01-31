<?php

/**
 * Syncope
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL),
 *              Version 1, the distribution of the Tine 2.0 Syncope module in or to the
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     Model
 * @property    string   id
 * @property    string   deviceid
 * @property    string   devicetype
 * @property    string   policykey
 * @property    string   policy_id
 * @property    string   owner_id
 * @property    string   acsversion
 * @property    string   pingfolder
 * @property    string   pinglifetime
 * @property    string   remotewipe
 * @property    string   useragent
 */

interface Syncope_Model_IDevice
{
    /**
     * Returns major firmware version of this device
     *
     * @return int/string
     */
    public function getMajorVersion();
    
}

