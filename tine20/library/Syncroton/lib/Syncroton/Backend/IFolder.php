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
interface Syncroton_Backend_IFolder extends Syncroton_Backend_IBackend
{
    /**
     * get folder indentified by $folderId
     *
     * @param  Syncroton_Model_Device|string  $deviceId
     * @param  string                         $folderId
     * @return Syncroton_Model_IFolder
     */
    public function getFolder($deviceId, $folderId);
    
    /**
     * get array of ids which got send to the client for a given class
     *
     * @param  Syncroton_Model_Device|string  $deviceId
     * @param  string                         $class
     * @return array
     */
    public function getFolderState($deviceId, $class);
    
    /**
     * delete all stored folderId's for given device
     *
     * @param  Syncroton_Model_Device|string  $deviceId
     */
    public function resetState($deviceId);
}
