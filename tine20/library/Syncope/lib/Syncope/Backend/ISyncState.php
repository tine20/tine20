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
interface Syncope_Backend_ISyncState
{
    /**
     * create new sync state
     *
     * @param Syncope_Model_ISyncState $_syncState
     * @return Syncope_Model_ISyncState
     */
    public function create(Syncope_Model_ISyncState $_syncState);
    
    public function resetState($_deviceId, $_type);
    
    public function update(Syncope_Model_ISyncState $_syncState);
    
    /**
     * get array of ids which got send to the client for a given class
     *
     * @param Syncope_Model_Device $_deviceId
     * @param string $_class
     * @return array
     */
    public function validate($_deviceId, $_syncKey, $_type);
}
