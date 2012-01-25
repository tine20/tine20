<?php

/**
 * Syncope
 *
 * @package     Command
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 Syncope module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle ActiveSync Sync command
 *
 * @package     Backend
 */
 
class Syncope_Backend_Device implements Syncope_Backend_IDevice
{
    /**
     * the database adapter
     * 
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    protected $_tablePrefix;
    
    public function __construct(Zend_Db_Adapter_Abstract $_db, $_tablePrefix = 'syncope_')
    {
        $this->_db          = $_db;
        $this->_tablePrefix = $_tablePrefix;
    }

    /**
     * create new device
     * 
     * @param Syncope_Model_IDevice $_device
     * @return Syncope_Model_IDevice
     */
    public function create(Syncope_Model_IDevice $_device)
    {
        $id = sha1(mt_rand(). microtime());
        
        $this->_db->insert($this->_tablePrefix . 'device', array(
        	'id'         => $id, 
        	'deviceid'   => $_device->deviceid,
        	'devicetype' => $_device->devicetype,
        	'policy_id'  => $_device->policy_id,
        	'policykey'  => $_device->policykey,
        	'owner_id'   => $_device->owner_id,
        	'useragent'  => $_device->useragent,
        	'acsversion' => $_device->acsversion,
        	'remotewipe' => $_device->remotewipe
        ));
        
        return $this->get($id);
    }
    
    /**
     * @param string  $_id
     * @throws Syncope_Exception_NotFound
     * @return Syncope_Model_IDevice
     */
    public function get($_id)
    {
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'device')
            ->where('id = ?', $_id);
            
        $stmt = $this->_db->query($select);
        $device = $stmt->fetchObject('Syncope_Model_Device');
        
        if (! $device instanceof Syncope_Model_IDevice) {
            throw new Syncope_Exception_NotFound('id not found');
        }
        
        return $device;
    }
    
    public function delete($_id)
    {
        $id = $_id instanceof Syncope_Model_IDevice ? $_id->id : $id;
        
        $result = $this->_db->delete($this->_tablePrefix . 'device', array('id' => $id));
        
        return (bool) $result;
    }
    
    public function update(Syncope_Model_IDevice $_device)
    {
        $this->_db->update($this->_tablePrefix . 'device', array(
        	'acsversion'   => $_device->acsversion,
        	'policykey'    => $_device->policykey,
        	'pingfolder'   => $_device->pingfolder,
        	'pinglifetime' => $_device->pinglifetime,
        	'remotewipe'   => $_device->remotewipe
        ), array(
        	'id = ?' => $_device->id
        ));
        
        return $this->get($_device->id);
        
    }
}
