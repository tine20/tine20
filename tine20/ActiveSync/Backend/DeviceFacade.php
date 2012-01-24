<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * backend device class
 * @package     ActiveSync
 */
class ActiveSync_Backend_DeviceFacade implements Syncope_Backend_IDevice
{
    /**
     * @var ActiveSync_Backend_Device
     */
    var $_backend;

    public function __construct()
    {
        $this->_backend = new ActiveSync_Backend_Device();
    }
    
    /**
     * Create a new device
     *
     * @param  Syncope_Model_IDevice $_device
     * @return ActiveSync_Model_Device
     */
    public function create(Syncope_Model_IDevice $_device)
    {
        return $this->_backend->create($_device);
    }
    
    /**
     * Deletes one or more existing devices
     *
     * @param string|array $_id
     * @return void
     */
    public function delete($_id)
    {
        return $this->_backend->delete($_id);
    }
    
    /**
     * Return a single device
     *
     * @param string $_id
     * @return Syncope_Model_IDevice
     */
    public function get($_id)
    {
        return $this->_backend->get($_id);
    }
    
    /**
     * Upates an existing persistent record
     *
     * @param  Syncope_Model_IDevice $_device
     * @return ActiveSync_Model_Device
     */
    public function update(Syncope_Model_IDevice $_device)
    {
        return $this->_backend->update($_device);
    }
}
