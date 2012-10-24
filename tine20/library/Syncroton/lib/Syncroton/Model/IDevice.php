<?php

/**
 * Syncroton
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
 * @property    string   policyId
 * @property    string   ownerId
 * @property    string   acsversion
 * @property    string   pingfolder
 * @property    string   pinglifetime
 * @property    string   remotewipe
 * @property    string   useragent
 * @property    string   imei
 * @property    string   model
 * @property    string   friendlyname
 * @property    string   os
 * @property    string   oslanguage
 * @property    string   phonenumber
 * @property    string   pinglifetime
 * @property    string   pingfolder
 * @property    string   contactsfilter_id
 * @property    string   calendarfilter_id
 * @property    string   tasksfilter_id
 * @property    string   emailfilter_id
 * @property    string   lastsynccollection
 */
interface Syncroton_Model_IDevice
{
    /**
     * Returns major firmware version of this device
     *
     * @return int/string
     */
    public function getMajorVersion();
    
}

