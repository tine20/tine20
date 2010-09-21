<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * abstract backend for custom field configs
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
class Tinebase_CustomField_Config extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'customfield_config';

    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_CustomField_Config';
    
    /**
     * get customfield config ids by grant
     * 
     * @param string $_grant
     * @param int $_accountId
     * @return array of ids
     */
    public function getIdsByAcl($_grant, $_accountId)
    {
        $select = $this->_getSelect('id')
            ->join(array(
                /* table  */ 'customfield_acl' => SQL_TABLE_PREFIX . 'customfield_acl'), 
                /* on     */ "{$this->_db->quoteIdentifier('customfield_acl.customfield_id')} = {$this->_db->quoteIdentifier('customfield_config.id')}",
                /* select */ array()
            )
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('customfield_acl.account_grant') . ' = ?', $_grant));
        
        // use grants sql helper fn of Tinebase_Container to add account and grant values
        Tinebase_Container::addGrantsSql($select, $_accountId, $_grant, 'customfield_acl');

        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        return $rows;
    }
}
