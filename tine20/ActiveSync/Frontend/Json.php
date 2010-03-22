<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the ActiveSync application
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 */
class ActiveSync_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    protected $_applicationName = 'ActiveSync';
    
    /**
     * Set sync filter
     * 
     * @param $deviceId
     * @param $contentType one of {Calendar, Contacts, Email, Tasks}
     * @param $filterId
     * 
     * @return device data
     */
    public function setDeviceContentFilter($deviceId, $contentType, $filterId)
    {
        
    }
    
    /**
     * Returns registry data of the application.
     *
     * Each application has its own registry to supply static data to the client.
     * Registry data is queried only once per session from the client.
     *
     * This registry must not be used for rights or ACL purposes. Use the generic
     * rights and ACL mechanisms instead!
     */
    public function getRegistryData()
    {
        
        $deviceBackend = new ActiveSync_Backend_Device();
        $userDevices = $deviceBackend->search(new ActiveSync_Model_DeviceFilter(array(
            array('field' => 'owner_id', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId())
        )));
        
        return array(
            'userDevices' => $userDevices->toArray()
        );
    }
}