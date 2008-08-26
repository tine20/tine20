<?php

/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
 

/**
 * backend to handle Snom PhoneSetting
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Snom_PhoneSettings
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;    

	/**
	 * the constructor
	 */
    public function __construct($_db = NULL)
    {
        if($_db instanceof Zend_Db_Adapter_Abstract) {
            $this->_db = $_db;
        } else {
            $this->_db = Zend_Db_Table_Abstract::getDefaultAdapter();
        }
    }
          
	/**
	 * get PhoneSetting by id
	 * 
     * @param string $_id the id of the telephone
	 * @return Voipmanager_Model_SnomPhoneSettings
	 */
    public function get($_id)
    {	
        $phoneSettingId = Voipmanager_Model_SnomPhoneSettings::convertSnomPhoneSettingsIdToInt($_id);
        $select = $this->_db->select()->from(SQL_TABLE_PREFIX . 'snom_phone_settings')->where($this->_db->quoteInto('phone_id = ?', $phoneSettingId));
        $row = $this->_db->fetchRow($select);
        if (!$row) {
            throw new UnderflowException('Snom_PhoneSettings id ' . $phoneSettingId . ' not found');
        }

        $result = new Voipmanager_Model_SnomPhoneSettings($row);
        return $result;
	}
	   
    /**
     * add new setting
     *
     * @param Voipmanager_Model_SnomPhoneSettings $_setting the setting data
     * @return Voipmanager_Model_SnomPhoneSettings
     */
    public function create(Voipmanager_Model_SnomPhoneSettings $_setting)
    {
        if (! $_setting->isValid()) {
            throw new Exception('invalid phoneSetting');
        }

        if ( empty($_setting->phone_id) ) {
            $_setting->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $setting = $_setting->toArray();
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'snom_phone_settings', $setting);

        return $this->get($_setting->getId());
    }
    
    /**
     * update an existing setting
     *
     * @param Voipmanager_Model_SnomPhoneSettings $_setting the setting data
     * @return Voipmanager_Model_SnomPhoneSettings
     */
    public function update(Voipmanager_Model_SnomPhoneSettings $_setting)
    {
        if (! $_setting->isValid()) {
            throw new Exception('invalid phoneSetting');
        }
        $settingId = $_setting->getId();
        $settingData = $_setting->toArray();
        unset($settingData['phone_id']);
     
        $where = array($this->_db->quoteInto('phone_id = ?', $settingId));
        $this->_db->update(SQL_TABLE_PREFIX . 'snom_phone_settings', $settingData, $where);
        
        return $this->get($settingId);
    }    


    /**
     * delete setting(s) identified by setting id
     *
     * @param string|array|Tinebase_Record_RecordSet $_id
     * @return void
     */
    public function delete($_id)
    {
        foreach ((array)$_id as $id) {
            $settingId = Voipmanager_Model_SnomPhoneSettings::convertSnomPhoneSettingsIdToInt($id);
            $where[] = $this->_db->quoteInto('phone_id = ?', $settingId);
        }

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);

            // NOTE: using array for second argument won't work as delete function joins array items using "AND"
            foreach($where AS $where_atom)
            {
                $this->_db->delete(SQL_TABLE_PREFIX . 'snom_phone_settings', $where_atom);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
    }
	        

}
