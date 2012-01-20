<?php
/**
 * Tine 2.0
 *
 * @package     Syncope
 * @subpackage  Backend
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Syncope module in or to the 
 *              United States of America is excluded from the scope of this license.
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
class Syncope_Backend_ContentState implements Syncope_Backend_IContentState
{
    /**
     * the database adapter
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    public function __construct(Zend_Db_Adapter_Abstract $_db)
    {
        $this->_db = $_db;
    }
    
    /**
     * create new content state
     *
     * @param Syncope_Model_IContentState $_state
     * @return Syncope_Model_IContentState
     */
    public function create(Syncope_Model_IContentState $_state)
    {
        $id = sha1(mt_rand(). microtime());
        $deviceId = $_state->device_id instanceof Syncope_Model_IDevice ? $_state->device_id->id : $_state->device_id;
    
        $this->_db->insert('syncope_contentstates', array(
        	'id'            => $id, 
        	'device_id'     => $deviceId,
        	'class'         => $_state->class,
        	'collectionid'  => $_state->collectionid,
        	'contentid'     => $_state->contentid,
        	'creation_time' => $_state->creation_time->format('Y-m-d H:i:s'),
        	'is_deleted'    => isset($_state->is_deleted) ? (int)!!$_state->is_deleted : 0
        ));
        
        return $this->get($id);
    }
    
    /**
     * mark state as deleted. The state gets removed finally, 
     * when the synckey gets validated during next sync.
     * 
     * @param Syncope_Model_IContentState|string $_id
     */
    public function delete($_id)
    {
        $id = $_id instanceof Syncope_Model_IContentState ? $_id->id : $_id;
        
        $this->_db->update('syncope_contentstates', array(
                	'is_deleted' => 1
        ), array(
                	'id = ?' => $id
        ));
        
    }
    
    /**
     * @param string  $_id
     * @throws Syncope_Exception_NotFound
     * @return Syncope_Model_IContentState
     */
    public function get($_id)
    {
        $select = $this->_db->select()
            ->from('syncope_contentstates')
            ->where('id = ?', $_id);
    
        $stmt = $this->_db->query($select);
        $state = $stmt->fetchObject('Syncope_Model_ContentState');
        
        if (! $state instanceof Syncope_Model_IContentState) {
            throw new Syncope_Exception_NotFound('id not found');
        }
        
        if (!empty($state->creation_time)) {
            $state->creation_time = new DateTime($state->creation_time, new DateTimeZone('utc'));
        }
    
        return $state;
    }
    /**
    * get array of ids which got send to the client for a given class
    *
    * @param Syncope_Model_IDevice $_deviceId
    * @param string $_class
    * @return array
    */
    public function getClientState($_deviceId, $_class, $_collectionId)
    {
        $deviceId = $_deviceId instanceof Syncope_Model_IDevice ? $_deviceId->id : $_deviceId;
                
        $select = $this->_db->select()
            ->from('syncope_contentstates', 'contentid')
            ->where($this->_db->quoteIdentifier('device_id') . ' = ?',    $deviceId)
            ->where($this->_db->quoteIdentifier('class') . ' = ?',        $_class)
            ->where($this->_db->quoteIdentifier('collectionid') . ' = ?', $_collectionId);

        
        $stmt = $this->_db->query($select);
        $result = $stmt->fetchAll(Zend_Db::FETCH_COLUMN);
    
        return $result;
    }
    
    public function resetState($_deviceId, $_class, $_collectionId)
    {
        $deviceId = $_deviceId instanceof Syncope_Model_IDevice ? $_deviceId->id : $_deviceId;
         
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('device_id') . ' = ?',    $deviceId),
            $this->_db->quoteInto($this->_db->quoteIdentifier('class') . ' = ?',        $_class),
            $this->_db->quoteInto($this->_db->quoteIdentifier('collectionid') . ' = ?', $_collectionId)
        );
        
        $this->_db->delete('syncope_contentstates', $where);
    }
}
