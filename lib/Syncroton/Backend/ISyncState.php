<?php
/**
 * Syncroton
 *
 * @package     Syncroton
 * @subpackage  Backend
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * sql backend class for the folder state
 *
 * @package     Syncroton
 * @subpackage  Backend
 */
interface Syncroton_Backend_ISyncState extends Syncroton_Backend_IBackend
{
    /**
     * create new sync state
     * 
     * @param  Syncroton_Model_IDevice  $model
     * @param  boolean                  $keepPreviousSyncState
     */
    #public function create($model, $keepPreviousSyncState = true);

    /**
     * always returns the latest syncstate
     *
     * @param  Syncroton_Model_IDevice|string  $deviceId
     * @param  Syncroton_Model_IFolder|string  $folderId
     * @return Syncroton_Model_SyncState
     */
    public function getSyncState($deviceId, $folderId);

    public function resetState($_deviceId, $_type);

    /**
     * get array of ids which got send to the client for a given class
     *
     * @param Syncroton_Model_Device $_deviceId
     * @param string $_class
     * @return array
     */
    public function validate($_deviceId, $_syncKey, $_type);
}
