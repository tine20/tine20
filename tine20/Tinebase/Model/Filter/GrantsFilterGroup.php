<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Tinebase_Model_Filter_GrantsFilterGroup 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_GrantsFilterGroup extends Tinebase_Model_Filter_FilterGroup implements Tinebase_Model_Filter_AclFilter
{
    /**
     * @var string acl table name
     */
    protected $_aclTableName = null;
    
    /**
     * @var array one of these grants must be met
     */
    protected $_requiredGrants = array(
        Tinebase_Model_PersistentFilterGrant::GRANT_READ
    );
    
    /**
     * append grants acl filter
     * 
     * @param Zend_Db_Select $select
     * @param Tinebase_Backend_Sql_Abstract $backend
     * @param Tinebase_Model_User $user
     */
    protected function _appendGrantsFilter($select, $backend, $user)
    {
        $db = $backend->getAdapter();
        $select->join(array(
            /* table  */ $this->_aclTableName => SQL_TABLE_PREFIX . $this->_aclTableName), 
            /* on     */ "{$db->quoteIdentifier($this->_aclTableName . '.record_id')} = {$db->quoteIdentifier($backend->getTableName() . '.id')}",
            /* select */ array()
        );
        
        Tinebase_Container::addGrantsSql($select, $user, $this->_requiredGrants, $this->_aclTableName);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' $select after appending grants sql: ' . $select);
    }
}
