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
 * @todo add save rights function
 */

/**
 * backend to handle phones
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Snom_Phone
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
	 * search phones
	 * 
     * @param Voipmanager_Model_SnomPhoneFilter $_filter
     * @param Tinebase_Model_Pagination|optional $_pagination
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomPhone
	 */
    public function search(Voipmanager_Model_SnomPhoneFilter $_filter, $_pagination)
    {	
        $where = array();
        
        $select = $this->_db->select()
            ->from(array('phones' => SQL_TABLE_PREFIX . 'snom_phones'));
            
        if($_pagination instanceof Tinebase_Model_Pagination) {
            $_sort = $_pagination->toArray();
            
            if($_sort['sort'] == 'location_id') {
                $select->join(array('loc' => SQL_TABLE_PREFIX . 'snom_location'), 'phones.location_id = loc.id', array('location' => 'name'));    
                $_sort['sort'] = 'location';
                $_pagination->setFromArray($_sort);
            }
    
            if($_sort['sort'] == 'template_id') {
                $select->join(array('temp' => SQL_TABLE_PREFIX . 'snom_templates'), 'phones.template_id = temp.id', array('template' => 'name'));        
                $_sort['sort'] = 'template';
                $_pagination->setFromArray($_sort);
            }
            
            $_pagination->appendPagination($select);
        }

        if(!empty($_filter->query)) {
            $select->where($this->_db->quoteInto('(macaddress LIKE ? OR ipaddress LIKE ? OR description LIKE ?)', '%' . $_filter->query . '%'));
        } else {
            // handle the other fields separately
        }

        if(!empty($_filter->accountId)) {
            $_validPhoneIds = $this->getValidPhoneIds($_filter->accountId);   
            if(empty($_validPhoneIds)) {
                return new Tinebase_Record_RecordSet('Voipmanager_Model_SnomPhone', array());    
            }         
            $select->where($this->_db->quoteInto('id IN (?)', $_validPhoneIds));
        }

        $stmt = $select->query();
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_SnomPhone', $rows);
		
        return $result;
	}
    
    
	/**
	 * write phone ACL
	 * 
     * @param Voipmanager_Model_SnomPhoneRight $_acl
	 * @return Voipmanager_Model_SnomPhone the phone
	 */
    public function createACL(Voipmanager_Model_SnomPhoneRight $_acl)
    {
        if (!$_acl->getId()) {
            $id = $_acl->generateUID();
            $_acl->setId($id);
        }
        
        unset($_acl->accountDisplayName);
        
        $result = $this->_db->insert(SQL_TABLE_PREFIX . 'snom_phones_acl', $_acl->toArray());
        
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
        $where = $this->_db->quoteInto('snom_phone_id = ?', $_phoneId);
        $result = $this->_db->delete(SQL_TABLE_PREFIX . 'snom_phones_acl', $where);
        
        return $result;
    }

    /**
     * get phone owner
     * 
     * @param string $_phoneId
     * @return Tinebase_Record_RecordSet of Voipmanager_Model_SnomPhoneRight with phone owners
     */    
    public function getPhoneRights($_phoneId)
    {
        $phoneId = Voipmanager_Model_SnomPhone::convertSnomPhoneIdToInt($_phoneId);
        
        $select = $this->_db->select()    
            ->from(SQL_TABLE_PREFIX . 'snom_phones_acl')
            ->where($this->_db->quoteInto('account_type = ?', 'user'))
            ->where($this->_db->quoteInto('snom_phone_id = ?', $phoneId))
            ->where($this->_db->quoteIdentifier('read_right'). '= 1')
            ->where($this->_db->quoteIdentifier('write_right'). '= 1')
            ->where($this->_db->quoteIdentifier('dial_right'). '= 1');            

        $stmt = $select->query();
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);      
        
        $result = new Tinebase_Record_RecordSet('Voipmanager_Model_SnomPhoneRight', $rows);
        
        return $result;        
    }    
    
    /**
     * set phone rights
     *
     * @param Voipmanager_Model_SnomPhone $_phone
     * 
     * @todo test
     */
    public function setPhoneRights(Voipmanager_Model_SnomPhone $_phone)
    {
        if ($_phone->rights instanceOf Tinebase_Record_RecordSet) {
            $rightsToSet = $_phone->rights;
        } else {
            $rightsToSet = new Tinebase_Record_RecordSet('Voipmanager_Model_SnomPhoneRight', $_phone->rights);
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
	 * @return Voipmanager_Model_SnomPhone the phone
	 */    
    public function getValidPhoneIds($_accountId)
    {
        if(empty($_accountId)) {
            throw new UnderflowException('no accountId set');
        }    
        
        $select = $this->_db->select()    
            ->from(SQL_TABLE_PREFIX . 'snom_phones_acl', array('snom_phone_id'))
            ->where($this->_db->quoteInto('account_id = ?', $_accountId));            

        $stmt = $select->query();
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);      
        
        return $rows;  
    }
    
	/**
	 * get one phone identified by id
	 * 
     * @param string|Voipmanager_Model_SnomPhone $_id
	 * @return Voipmanager_Model_SnomPhone the phone
	 */
    public function get($_id)
    {	
        $phoneId = Voipmanager_Model_SnomPhone::convertSnomPhoneIdToInt($_id);
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_phones')
            ->where($this->_db->quoteInto('id = ?', $phoneId));

        $row = $this->_db->fetchRow($select);
        if (!$row) {
            throw new UnderflowException('phone not found');
        }

        $result = new Voipmanager_Model_SnomPhone($row);
        
        return $result;
	}
    
    
	/**
	 * get one myPhone identified by id
	 * 
     * @param string|Voipmanager_Model_SnomPhone $_id
     * @param string $_accountId
	 * @return Voipmanager_Model_SnomPhone the phone
	 */
    public function getMyPhone($_id, $_accountId)
    {	

        $_validPhoneIds = $this->getValidPhoneIds($_accountId);   
        if(empty($_validPhoneIds)) {
            throw new UnderflowException('not enough rights to display/edit phone');
        }         

    
        $phoneId = Voipmanager_Model_SnomPhone::convertSnomPhoneIdToInt($_id);
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_phones')
            ->where($this->_db->quoteInto('id = ?', $phoneId))
            ->where($this->_db->quoteInto('id IN (?)', $_validPhoneIds));
            
        $row = $this->_db->fetchRow($select);
        if (!$row) {
            throw new UnderflowException('phone not found / not enough rights');
        }

        $result = new Voipmanager_Model_SnomPhone($row);
        
        return $result;
	}    
    
	     
    /**
     * get one phone identified by id
     * 
     * @param string $_macAddress the macaddress of the phone
     * @return Voipmanager_Model_SnomPhone the phone
     */
    public function getByMacAddress($_macAddress)
    {   
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_phones')
            ->where($this->_db->quoteInto('macaddress = ?', $_macAddress));

        $row = $this->_db->fetchRow($select);
        if (!$row) {
            throw new UnderflowException('phone not found');
        }

        $result = new Voipmanager_Model_SnomPhone($row);
        
        return $result;
    }     
	
    /**
     * insert new phone into database
     *
     * @param Voipmanager_Model_SnomPhone $_phone the phonedata
     * @return Voipmanager_Model_SnomPhone
     */
    public function create(Voipmanager_Model_SnomPhone $_phone)
    {
        if (!$_phone->isValid()) {
            throw new Exception('invalid phone');
        }
        
        if (empty($_phone->id)) {
        	$_phone->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $phoneData = $_phone->toArray();
        unset($phoneData['lines']);
        unset($phoneData['rights']);
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'snom_phones', $phoneData);

        return $this->get($_phone);
    }
    
    /**
     * update an existing phone
     *
     * @param Voipmanager_Model_SnomPhone $_phone the phonedata
     * @return Voipmanager_Model_SnomPhone
     */
    public function update(Voipmanager_Model_SnomPhone $_phone)
    {
        if (! $_phone->isValid()) {
            throw new Exception('invalid phone');
        }
        
        $phoneId = $_phone->getId();
        $phoneData = $_phone->toArray();
        unset($phoneData['id']);
        unset($phoneData['lines']);
        unset($phoneData['rights']);
        
        $where = array($this->_db->quoteInto('id = ?', $phoneId));
        $this->_db->update(SQL_TABLE_PREFIX . 'snom_phones', $phoneData, $where);
        
        return $this->get($_phone);
    }        
    
    /**
     * update redirect for an existing phone
     *
     * @param Voipmanager_Model_SnomPhone $_phone the phonedata
     * @return Voipmanager_Model_SnomPhone
     */
    public function updateRedirect(Voipmanager_Model_SnomPhone $_phone)
    {
        if (! $_phone->isValid()) {
            throw new Exception('invalid phone');
        }
        
        $phoneId = $_phone->getId();
        $redirectData = array(
            'redirect_event'    => $_phone->redirect_event,
            'redirect_number'   => $_phone->redirect_number,
            'redirect_time'     => $_phone->redirect_time
        );
        
        $where = array($this->_db->quoteInto('id = ?', $phoneId));
        $this->_db->update(SQL_TABLE_PREFIX . 'snom_phones', $redirectData, $where);
        
        return $this->get($_phone);
    }            
    
    /**
     * update an existing myPhone
     *
     * @param Voipmanager_Model_SnomPhone $_phone the phonedata
     * @return Voipmanager_Model_SnomPhone
     */
    public function updateMyPhone(Voipmanager_Model_MyPhone $_phone, $_accountId)
    {
        if (! $_phone->isValid()) {
            throw new Exception('invalid myPhone');
        }
        
        $_validPhoneIds = $this->getValidPhoneIds($_accountId);   
        if(empty($_validPhoneIds)) {
            throw new UnderflowException('not enough rights to edit phone');
        }         
        
        $phoneId = $_phone->getId();
        $phoneData = $_phone->toArray();
        unset($phoneData['id']);
        unset($phoneData['template_id']);

        $where = array($this->_db->quoteInto('id = ?', $phoneId), $this->_db->quoteInto('id IN (?)', $_validPhoneIds) );

        $this->_db->update(SQL_TABLE_PREFIX . 'snom_phones', $phoneData, $where);

  
        return $this->get($phoneId);
    }      
    
    
    /**
     * delete phone(s) identified by phone id
     *
     * @param string|array|Tinebase_Record_RecordSet $_id
     * @return void
     */
    public function delete($_id)
    {
        foreach ((array)$_id as $id) {
            $phoneId = Voipmanager_Model_SnomPhone::convertSnomPhoneIdToInt($id);
            $where[] = $this->_db->quoteInto('id = ?', $phoneId);
        }

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);

            // NOTE: cascading delete for lines and phone_settings
            // SECOND NOTE: using array for second argument won't work as delete function joins array items using "AND"
            foreach($where AS $where_atom)
            {
                $this->_db->delete(SQL_TABLE_PREFIX . 'snom_phones', $where_atom);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
    }
        
    /**
     * update an existing phone
     *
     * @param Voipmanager_Model_SnomPhone $_phone the phonedata
     * @return Voipmanager_Model_SnomPhone
     */
    public function updateStatus(Voipmanager_Model_SnomPhone $_phone)
    {
        if (! $_phone->isValid()) {
            throw new Exception('invalid phone');
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

        $where = array($this->_db->quoteInto('id = ?', $phoneId));
        $this->_db->update(SQL_TABLE_PREFIX . 'snom_phones', $statusData, $where);
        
        return $this->get($_phone);
    }
          
    /**
     * set value of http_client_info_sent
     *
     * @param string|array|Tinebase_Record_RecordSet $_id
     * @param boolean $_status
     */
    public function setHttpClientInfoSent($_id, $_status)
    {
        foreach ((array)$_id as $id) {
            $phoneId = Voipmanager_Model_SnomPhone::convertSnomPhoneIdToInt($id);
            $where[] = $this->_db->quoteInto('id = ?', $phoneId);
        }

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);

            // NOTE: cascading delete for lines and phone_settings
            // SECOND NOTE: using array for second argument won't work as delete function joins array items using "AND"
            #foreach($where AS $where_atom)
            #{
            #    $this->_db->delete(SQL_TABLE_PREFIX . 'snom_phones', $where_atom);
            #}
    
            $phoneData = array(
                'http_client_info_sent' => (bool) $_status
            );
            $this->_db->update(SQL_TABLE_PREFIX . 'snom_phones', $phoneData, $where);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
    }
}
