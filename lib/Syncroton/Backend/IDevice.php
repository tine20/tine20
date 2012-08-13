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
 
interface Syncroton_Backend_IDevice extends Syncroton_Backend_IBackend
{
    /**
     * @param unknown_type $userId
     * @param unknown_type $deviceId
     * @return Syncroton_Model_IDevice
     */
    public function getUserDevice($userId, $deviceId);
}
