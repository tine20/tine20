<?php

/**
 * Syncope
 *
 * @package     Command
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     Backend
 */
 
interface Syncope_Backend_IDevice
{
    /**
    * Create a new device
    *
    * @param  Syncope_Model_IDevice $_device
    * @return Syncope_Model_IDevice
    */
    public function create(Syncope_Model_IDevice $_device);
    
    /**
     * Deletes one or more existing devices
     *
     * @param string|array $_id
     * @return void
     */
    public function delete($_id);
    
    /**
     * Return a single device
     *
     * @param string $_id
     * @return Syncope_Model_IDevice
     */
    public function get($_id);
    
    /**
     * @param unknown_type $userId
     * @param unknown_type $deviceId
     * @return Syncope_Model_IDevice
     */
    public function getUserDevice($userId, $deviceId);
    
    /**
     * Upates an existing persistent record
     *
     * @param  Syncope_Model_IDevice $_device
     * @return Syncope_Model_IDevice
     */
    public function update(Syncope_Model_IDevice $_device);    
}
