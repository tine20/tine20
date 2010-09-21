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
     * @param int $_accountId
     * @param string $_grant if grant is empty, all grants are returned
     * @return array
     */
    public function getByAcl($_grant, $_accountId)
    {
        $select = $this->_getAclSelect('id');
        $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('customfield_acl.account_grant') . ' = ?', $_grant));
        
        // use grants sql helper fn of Tinebase_Container to add account and grant values
        Tinebase_Container::addGrantsSql($select, $_accountId, $_grant, 'customfield_acl');

        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        return $rows;
    }
    
    /**
     * get acl select
     * 
     * @param string $_cols
     * @return Zend_Db_Select
     */
    protected function _getAclSelect($_cols = '*')
    {
        return $this->_getSelect($_cols)
            ->join(array(
                /* table  */ 'customfield_acl' => SQL_TABLE_PREFIX . 'customfield_acl'), 
                /* on     */ "{$this->_db->quoteIdentifier('customfield_acl.customfield_id')} = {$this->_db->quoteIdentifier('customfield_config.id')}",
                /* select */ array()
            );
    }
    
    /**
     * all grants for configs defined by filter
     * 
     * @param $_accountId
     * @param $_filter
     */
    public function getAclByFilter($_accountId, $_filter)
    {
        $select = $this->_getAclSelect();
        $this->_addFilter($select, $_filter);
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        $stmt = $this->_db->query($select);
        $rows = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        return $rows;
    }
}
