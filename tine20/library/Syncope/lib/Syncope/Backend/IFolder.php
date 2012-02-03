<?php
/**
 * Tine 2.0
 *
 * @package     Syncope
 * @subpackage  Backend
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * sql backend class for the folder state
 *
 * @package     Syncope
 * @subpackage  Backend
 */
interface Syncope_Backend_IFolder
{
    /**
     * @param Syncope_Model_IFolder $_folder
     * @return Syncope_Model_IFolder
     */
    public function create(Syncope_Model_IFolder $_folder);
    
    public function delete($_id);
    
    public function get($_id);
    
    /**
     * get folder indentified by $_folderId
     *
     * @param  Syncope_Model_Device|string  $_deviceId
     * @param  string                       $_folderId
     * @return Syncope_Model_IFolder
     */
    public function getFolder($_deviceId, $_folderId);
    
    /**
     * get array of ids which got send to the client for a given class
     *
     * @param Syncope_Model_Device $_deviceId
     * @param string $_class
     * @return array
     */
    public function getFolderState($_deviceId, $_class);
    
    /**
     * delete all stored folderId's for given device
     *
     * @param Syncope_Model_Device $_deviceId
     * @param string $_class
     */
    public function resetState($_deviceId);
    
    public function update(Syncope_Model_IFolder $_folder);
}
