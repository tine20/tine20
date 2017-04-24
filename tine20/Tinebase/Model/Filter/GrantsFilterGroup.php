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
     * @var string acl record column for join with acl table
     */
    protected $_aclIdColumn = 'id';

    /**
     * @var array one of these grants must be met
     */
    protected $_requiredGrants = array(
        Tinebase_Model_PersistentFilterGrant::GRANT_READ
    );

    /**
     * is acl filter resolved?
     *
     * TODO needed here?
     *
     * @var boolean
     */
    protected $_isResolved = FALSE;

    /**
     * sets the grants this filter needs to assure
     *
     * @param array $_grants
     */
    public function setRequiredGrants(array $_grants)
    {
        $this->_requiredGrants = $_grants;
        $this->_isResolved = FALSE;
    }

    /**
     * appends custom filters to a given select object
     *
     * @param  Zend_Db_Select                    $select
     * @param  Tinebase_Backend_Sql_Abstract     $backend
     * @return void
     */
    public function appendFilterSql($select, $backend)
    {
        $this->_appendAclSqlFilter($select, $backend);
    }

    /**
     * add account id to filter
     *
     * @param Zend_Db_Select $select
     * @param Tinebase_Backend_Sql_Abstract $backend
     */
    protected function _appendAclSqlFilter($select, $backend)
    {
        if (! $this->_isResolved) {
            $this->_appendGrantsFilter($select, $backend);

            $this->_isResolved = TRUE;
        }
    }

    /**
     * append grants acl filter
     * 
     * @param Zend_Db_Select $select
     * @param Tinebase_Backend_Sql_Abstract $backend
     * @param Tinebase_Model_User $user
     */
    protected function _appendGrantsFilter($select, $backend, $user = null)
    {
        if ($this->_ignoreAcl) {
            return;
        }

        if (! $user) {
            $user = isset($this->_options['user']) ? $this->_options['user'] : Tinebase_Core::getUser();
        }

        $db = $backend->getAdapter();
        $select->join(array(
            /* table  */ $this->_aclTableName => SQL_TABLE_PREFIX . $this->_aclTableName), 
            /* on     */ "{$db->quoteIdentifier($this->_aclTableName . '.record_id')} = {$db->quoteIdentifier($backend->getTableName() . '.' . $this->_aclIdColumn)}",
            /* select */ array()
        );
        
        Tinebase_Container::addGrantsSql($select, $user, $this->_requiredGrants, $this->_aclTableName);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' $select after appending grants sql: ' . $select);
    }
}
