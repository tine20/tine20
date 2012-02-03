<?php

/**
 * Syncope
 *
 * @package     Model
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
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

