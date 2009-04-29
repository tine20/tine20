<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Cache.php 7879 2009-04-26 06:28:07Z l.kneschke@metaways.de $
 * 
 */

/**
 * sql cache backend class for Felamimail messages
 *
 * @package     Felamimail
 */
class Felamimail_Backend_Cache_Sql_Message extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'felamimail_cache_message';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Felamimail_Model_Message';

    /**
     * delete all cached messages for one folder
     *
     * @param string $_folderId
     */
    public function deleteByFolderId($_folderId)
    {
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('folder_id') . ' = ?', $_folderId)
        );
        
        $this->_db->delete($this->_tablePrefix . $this->_tableName, $where);
    }

    /**
     * get count of cached messages by folder (id) 
     *
     * @param string $_folderId
     * @return integer
     * 
     * @todo write test
     */
    public function searchCountByFolderId($_folderId)
    {
        $filter = new Felamimail_Model_MessageFilter(array(
            array('field' => 'folder_id', 'operator' => 'equals', 'value' => $_folderId)
        ));
        
        $count = $this->searchCount($filter);
        return $count;
    }
    
    /**
     * get messageuids by folder (id)
     *
     * @param string $_folderId
     * @return array
     * 
     * @todo write test
     */
    public function getMessageuidsByFolderId($_folderId)
    {
        $select = $this->_db->select();
        $select->from(array($this->_tableName => $this->_tablePrefix . $this->_tableName), $this->_tableName . '.messageuid')
                ->where($this->_db->quoteInto($this->_db->quoteIdentifier('folder_id') . ' = ?', $_folderId));
                //->order($this->_tableName . '.messageuid ASC');
        
        $stmt = $this->_db->query($select);
        $rows = (array)$stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $result = array();
        foreach ($rows as $row) {
            $result[] = $row['messageuid'];
        }

        return $result;
    }
    
    /**
     * delete messages with given messageuids by folder (id)
     *
     * @param array $_msguids
     * @param string $_folderId
     * @return boolean success
     * 
     * @todo write test
     * @todo add tablename to identifiers?
     */
    public function deleteMessageuidsByFolderId($_msguids, $_folderId)
    {
        if (empty($_msguids) || !is_array($_msguids)) {
            return FALSE;
        }
        
        $where = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('messageuid') . ' IN (?)', $_msguids),
            $this->_db->quoteInto($this->_db->quoteIdentifier('folder_id') . ' = ?', $_folderId)
        );
        
        $this->_db->delete($this->_tablePrefix . $this->_tableName, $where);
        
        return TRUE;
    }
}
