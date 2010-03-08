<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        add save rights function
 * @todo        add search function to interface again when search function is removed
 */

/**
 * backend to handle phones
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Snom_Phone extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'snom_phones';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Voipmanager_Model_Snom_Phone';
    
	/**
	 * write phone ACL
	 * 
     * @param Voipmanager_Model_Snom_PhoneRight $_acl
	 * @return Voipmanager_Model_Snom_Phone the phone
	 */
    public function createACL(Voipmanager_Model_Snom_PhoneRight $_acl)
    {
        if (!$_acl->getId()) {
            $id = $_acl->generateUID();
            $_acl->setId($id);
        }
        
        unset($_acl->account_name);
        
        $result = $this->_db->insert($this->_tablePrefix . 'snom_phones_acl', $_acl->toArray());
        
        return $result;
    }
    
	/**
	 * delete phone ACLs
	 * 
     * @param string $_phoneId
	 * @return query result
	 */
    public function deleteACLs($_phoneId)
    {        
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('snom_phone_id') . ' = ?', $_phoneId);
        $result = $this->_db->delete($this->_tablePrefix . 'snom_phones_acl', $where);
        
        return $result;
    }

    /**
     * get phone owner
     * 
     * @param string $_phoneId
     * @return Tinebase_Record_RecordSet of Voipmanager_Model_Snom_PhoneRight with phone owners
     */    
    public function getPhoneRights($_phoneId)
    {
        $phoneId = Voipmanager_Model_Snom_Phone::convertSnomPhoneIdToInt($_phoneId);
        
        $select = $this->_db->select()    
            ->from($this->_tablePrefix . 'snom_phones_acl')
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('account_type') . ' = ?', Tinebase_Acl_Rights::ACCOUNT_TYPE_USER))
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('snom_phone_id') . ' = ?', $phoneId))
            ->where($this->_db->quoteIdentifier('read_right'). '= 1')
            ->where($this->_db->quoteIdentifier('write_right'). '= 1')
            ->where($this->_db->quoteIdentifier('dial_right'). '= 1');            

        $stmt = $select->query();
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);      
        
        $result = new Tinebase_Record_RecordSet('Voipmanager_Model_Snom_PhoneRight', $rows);
        
        return $result;        
    }    
    
    /**
     * set phone rights
     *
     * @param Voipmanager_Model_Snom_Phone $_phone
     * 
     * @todo test
     */
    public function setPhoneRights(Voipmanager_Model_Snom_Phone $_phone)
    {
        if ($_phone->rights instanceOf Tinebase_Record_RecordSet) {
            $rightsToSet = $_phone->rights;
        } else {
            $rightsToSet = new Tinebase_Record_RecordSet('Voipmanager_Model_Snom_PhoneRight', $_phone->rights);
        }
        
        // delete old rights
        $this->deleteACLs($_phone->getId());
        
        // add new rights        
        foreach ($rightsToSet as $right) {
            $right->snom_phone_id = $_phone->getId();
            $right->read_right = 1;
            $right->write_right = 1;
            $right->dial_right = 1;
            $this->createACL($right);
        }        
    }
   
	/**
	 * get valid phone ids according to phones_acl, identified by account id
	 * 
     * @param string| $_accountId
	 * @return Voipmanager_Model_Snom_Phone the phone
	 * @throws Voipmanager_Exception_InvalidArgument
	 */    
    public function getValidPhoneIds($_accountId)
    {
        if(empty($_accountId)) {
            throw new Voipmanager_Exception_InvalidArgument('no accountId set');
        }    
        
        $select = $this->_db->select()    
            ->from($this->_tablePrefix . 'snom_phones_acl', array('snom_phone_id'))
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('account_id') . ' = ?', $_accountId));            

        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        $stmt = $select->query();
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);      
        
        return $rows;  
    }
    
	/**
	 * get one myPhone identified by id
	 * 
     * @param string|Voipmanager_Model_Snom_Phone $_id
     * @param string $_accountId
	 * @return Voipmanager_Model_Snom_Phone the phone
	 * @throws Voipmanager_Exception_AccessDenied
	 */
    public function getMyPhone($_id, $_accountId)
    {	

        $_validPhoneIds = $this->getValidPhoneIds($_accountId);   
        if(empty($_validPhoneIds)) {
            throw new Voipmanager_Exception_AccessDenied('not enough rights to display/edit phone');
        }         

    
        $phoneId = Voipmanager_Model_Snom_Phone::convertSnomPhoneIdToInt($_id);
        
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'snom_phones')
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $phoneId))
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' IN (?)', $_validPhoneIds));
            
        $row = $this->_db->fetchRow($select);
        if (!$row) {
            throw new Voipmanager_Exception_AccessDenied('phone not found / not enough rights');
        }

        $result = new Voipmanager_Model_Snom_Phone($row);
        
        return $result;
	}    
    
	     
    /**
     * get one phone identified by id
     * 
     * @param string $_macAddress the macaddress of the phone
     * @return Voipmanager_Model_Snom_Phone the phone
     * @throws Voipmanager_Exception_NotFound
     */
    public function getByMacAddress($_macAddress)
    {   
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'snom_phones')
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('macaddress') . ' = ?', $_macAddress));

        $row = $this->_db->fetchRow($select);
        if (!$row) {
            throw new Voipmanager_Exception_NotFound('phone not found');
        }

        $result = new Voipmanager_Model_Snom_Phone($row);
        
        return $result;
    }     
	
    /**
     * update redirect for an existing phone
     *
     * @param Voipmanager_Model_Snom_Phone $_phone the phonedata
     * @return Voipmanager_Model_Snom_Phone
     * @throws  Voipmanager_Exception_Validation
     */
    public function updateRedirect(Voipmanager_Model_Snom_Phone $_phone)
    {
        if (! $_phone->isValid()) {
            throw new Voipmanager_Exception_Validation('invalid phone');
        }
        
        $phoneId = $_phone->getId();
        $redirectData = array(
            'redirect_event'    => $_phone->redirect_event,
            'redirect_number'   => $_phone->redirect_number,
            'redirect_time'     => $_phone->redirect_time
        );
        
        $where = array($this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $phoneId));
        $this->_db->update($this->_tablePrefix . 'snom_phones', $redirectData, $where);
        
        return $this->get($_phone);
    }            
    
    /**
     * update an existing phone
     *
     * @param Voipmanager_Model_Snom_Phone $_phone the phonedata
     * @return Voipmanager_Model_Snom_Phone
     * @throws  Voipmanager_Exception_Validation
     */
    public function updateStatus(Voipmanager_Model_Snom_Phone $_phone)
    {
        if (! $_phone->isValid()) {
            throw new Voipmanager_Exception_Validation('invalid phone');
        }
        $phoneId = $_phone->getId();
        $phoneData = $_phone->toArray();
        $statusData = array(
            'ipaddress'             => $_phone->ipaddress,
            'current_software'      => $_phone->current_software,
            'current_model'         => $_phone->current_model,
            'settings_loaded_at'    => $phoneData['settings_loaded_at'],
            'firmware_checked_at'   => $phoneData['firmware_checked_at']
        );

        $where = array($this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $phoneId));
        $this->_db->update($this->_tablePrefix . 'snom_phones', $statusData, $where);
        
        return $this->get($_phone);
    }
          
    /**
     * set value of http_client_info_sent
     *
     * @param string|array|Tinebase_Record_RecordSet $_id
     * @param boolean $_status
     * @throws  Voipmanager_Exception_Backend
     */
    public function setHttpClientInfoSent($_id, $_status)
    {
        foreach ((array)$_id as $id) {
            $phoneId = Voipmanager_Model_Snom_Phone::convertSnomPhoneIdToInt($id);
            $where[] = $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $phoneId);
        }

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);

            // NOTE: cascading delete for lines and phone_settings
            // SECOND NOTE: using array for second argument won't work as delete function joins array items using "AND"
            #foreach($where AS $where_atom)
            #{
            #    $this->_db->delete($this->_tablePrefix . 'snom_phones', $where_atom);
            #}
    
            $phoneData = array(
                'http_client_info_sent' => (bool) $_status
            );
            $this->_db->update($this->_tablePrefix . 'snom_phones', $phoneData, $where);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw new Voipmanager_Exception_Backend($e->getMessage());
        }
    }
}
