<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Asterisk peer sql backend
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Asterisk_SipPeer
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
            $this->_db = Zend_Registry::get('dbAdapter');
        }
    }
    
    /**
	 * search for Sip Peers
	 * 
     * @param string $_sort
     * @param string $_dir
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskSipPeer
	 */
    public function search($_sort = 'id', $_dir = 'ASC', $_filter = NULL, $_context = NULL)
    {	
        $where = array();
        
        if(!empty($_filter)) {
            $_fields = "callerid,context,fullcontact,ipaddr";            
            $where = $this->_getSearchFilter($_filter, $_fields);
        }
        
        if(!empty($_context)) {
            $where[] = Zend_Registry::get('dbAdapter')->quoteInto('context = ?', $_context);
        }
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'asterisk_sip_peers');

        $select->order($_sort.' '.$_dir);

        foreach($where as $whereStatement) {
            $select->where($whereStatement);
        }               
       
        $stmt = $this->_db->query($select);

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_AsteriskSipPeer', $rows);
		
        return $result;
	}
    
	/**
	 * get Sip peer by id
	 * 
     * @param string $_id the id of the Sip peer
	 * @return Voipmanager_Model_AsteriskSipPeer
	 */
    public function get($_id)
    {	
        $sipPeerId = Voipmanager_Model_AsteriskSipPeer::convertAsteriskSipPeerIdToInt($_id);
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'asterisk_sip_peers')
            ->where($this->_db->quoteInto('id = ?', $sipPeerId));
            
        $row = $this->_db->fetchRow($select);
        
        if (!$row) {
            throw new UnderflowException('sip peer not found');
        }

        $result = new Voipmanager_Model_AsteriskSipPeer($row);
        
        return $result;
	}
	   
    /**
     * add new asterisk Sip peer
     *
     * @param Voipmanager_Model_AsteriskSipPeer $_peer the Sip peer data
     * @return Voipmanager_Model_AsteriskSipPeer
     */
    public function create(Voipmanager_Model_AsteriskSipPeer $_sipPeer)
    {
        if (!$_sipPeer->isValid()) {
            throw new Exception('invalid sipPeer');
        }

        if (empty($_sipPeer->id) ) {
            $_sipPeer->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $sipPeer = $_sipPeer->toArray();
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'asterisk_sip_peers', $sipPeer);

        return $this->get($_sipPeer);
    }
    
    /**
     * update an existing asterisk sip peer
     *
     * @param Voipmanager_Model_AsteriskSipPeer $_sipPeer the sip peer data
     * @return Voipmanager_Model_AsteriskSipPeer
     */
    public function update(Voipmanager_Model_AsteriskSipPeer $_sipPeer)
    {
        if (!$_sipPeer->isValid()) {
            throw new Exception('invalid sip peer');
        }
        
        $sipPeerId = $_sipPeer->getId();
        $sipPeerData = $_sipPeer->toArray();
        unset($sipPeerData['id']);

        $where = array($this->_db->quoteInto('id = ?', $sipPeerId));
        
        $this->_db->update(SQL_TABLE_PREFIX . 'asterisk_sip_peers', $sipPeerData, $where);
        
        return $this->get($_sipPeer);
    }        
    
    /**
     * delete sip peer(s) identified by sip peer id
     *
     * @param string|array|Tinebase_Record_RecordSet $_id
     * @return void
     */
    public function delete($_id)
    {
        foreach ((array)$_id as $id) {
            $sipPeerId = Voipmanager_Model_AsteriskSipPeer::convertAsteriskSipPeerIdToInt($id);
            $where[] = $this->_db->quoteInto('id = ?', $sipPeerId);
        }

        try {
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($this->_db);

            // NOTE: using array for second argument won't work as delete function joins array items using "AND"
            foreach($where AS $where_atom)
            {
                $this->_db->delete(SQL_TABLE_PREFIX . 'asterisk_sip_peers', $where_atom);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
    }      
    
    
    /**
     * create search filter
     *
     * @param string $_filter
     * @param int $_leadstate
     * @param int $_probability
     * @param bool $_getClosedLeads
     * @return array
     */
    protected function _getSearchFilter($_filter, $_fields)
    {
        $where = array();
        if(!empty($_filter)) {
            $search_values = explode(" ", $_filter);
            
            $search_fields = explode(",", $_fields);
            foreach($search_fields AS $search_field) {
                $fields .= " OR " . $search_field . " LIKE ?";    
            }
            $fields = substr($fields,3);
        
            foreach($search_values AS $search_value) {
                $where[] = Zend_Registry::get('dbAdapter')->quoteInto('('.$fields.')', '%' . $search_value . '%');                            
            }
        }
        return $where;
    }    
}
