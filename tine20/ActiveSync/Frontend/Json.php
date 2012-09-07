<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the ActiveSync application
 *
 * @package     ActiveSync
 * @subpackage  Frontend
 */
class ActiveSync_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    protected $_applicationName = 'ActiveSync';
    
    /**
     * Set sync filter
     * 
     * @param  string $deviceId
     * @param  string $class one of {Calendar, Contacts, Email, Tasks}
     * @param  string $filterId
     * @return array device data
     */
    public function setDeviceContentFilter($deviceId, $class, $filterId)
    {
        $device = ActiveSync_Controller_Device::getInstance()->setDeviceContentFilter($deviceId, $class, $filterId);
        
        return $device->toArray();
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
