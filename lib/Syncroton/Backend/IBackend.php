<?php
/**
 * Syncroton
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
 
interface Syncroton_Backend_IBackend
{
    /**
     * Create a new device
     *
     * @param  Syncroton_Model_IDevice $device
     * @return Syncroton_Model_IDevice
     */
    public function create($model);
    
    /**
     * Deletes one or more existing devices
     *
     * @param string|array $_id
     * @return void
     */
    public function delete($id);
    
    /**
     * Return a single device
     *
     * @param string $_id
     * @return Syncroton_Model_IDevice
     */
    public function get($id);
    
    /**
     * Upates an existing persistent record
     *
     * @param  Syncroton_Model_IDevice $_device
     * @return Syncroton_Model_IDevice
     */
    public function update($model);    
}
