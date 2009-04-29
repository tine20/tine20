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
}
