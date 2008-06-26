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
class Voipmanager_Backend_Asterisk_Peer
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;    
    
	/**
	 * the constructor
	 */
    public function __construct()
    {
        $this->_db = Zend_Registry::get('dbAdapter');
    }
    
    /**
	 * search for peers
	 * 
     * @param string $_sort
     * @param string $_dir
	 * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_AsteriskPeer
	 */
    public function search($_sort = 'id', $_dir = 'ASC', $_filter = NULL)
    {	
        $where = array();
        
        if(!empty($_filter)) {
            $_fields = "callerid,context,fullcontact,ipaddr";            
            $where = $this->_getSearchFilter($_filter, $_fields);
        }
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'asterisk_peers');

        $select->order($_sort.' '.$_dir);

        foreach($where as $whereStatement) {
            $select->where($whereStatement);
        }               
       
        $stmt = $this->_db->query($select);

        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
       	$result = new Tinebase_Record_RecordSet('Voipmanager_Model_AsteriskPeer', $rows);
		
        return $result;
	}
    
	/**
	 * get peer by id
	 * 
     * @param string $_id the id of the peer
	 * @return Voipmanager_Model_AsteriskPeer
	 */
    public function get($_id)
    {	
        $peerId = Voipmanager_Model_AsteriskPeer::convertLineIdToInt($_id);
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'asterisk_peers')
            ->where($this->_db->quoteInto('id = ?', $peerId));
            
        $row = $this->_db->fetchRow($select);
        
        if (!$row) {
            throw new UnderflowException('peer not found');
        }

        $result = new Voipmanager_Model_AsteriskPeer($row);
        
        return $result;
	}
	   
    /**
     * add new asterisk peer
     *
     * @param Voipmanager_Model_AsteriskPeer $_peer the peer data
     * @return Voipmanager_Model_AsteriskPeer
     */
    public function create(Voipmanager_Model_AsteriskPeer $_peer)
    {
        if (!$_peer->isValid()) {
            throw new Exception('invalid peer');
        }

        if (empty($_peer->id) ) {
            $_peer->setId(Tinebase_Record_Abstract::generateUID());
        }
        
        $peer = $_peer->toArray();
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'asterisk_peers', $peer);

        return $this->get($_peer);
    }
    
    /**
     * update an existing asterisk peer
     *
     * @param Voipmanager_Model_AsteriskPeer $_peer the peer data
     * @return Voipmanager_Model_AsteriskPeer
     */
    public function update(Voipmanager_Model_AsteriskPeer $_peer)
    {
        if (!$_peer->isValid()) {
            throw new Exception('invalid peer');
        }
        
        $peerId = $_peer->getId();
        $peerData = $_peer->toArray();
        unset($peerData['id']);

        $where = array($this->_db->quoteInto('id = ?', $peerId));
        
        $this->_db->update(SQL_TABLE_PREFIX . 'asterisk_peers', $peerData, $where);
        
        return $this->get($_peer);
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
            $sipPeerId = Voipmanager_Model_AsteriskPeer::convertAsteriskPeerIdToInt($id);
            $where[] = $this->_db->quoteInto('id = ?', $sipPeerId);
        }

        try {
            $this->_db->beginTransaction();

            // NOTE: using array for second argument won't work as delete function joins array items using "AND"
            foreach($where AS $where_atom)
            {
                $this->_db->delete(SQL_TABLE_PREFIX . 'asterisk_peers', $where_atom);
            }

            $this->_db->commit();
        } catch (Exception $e) {
            $this->_db->rollBack();
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
