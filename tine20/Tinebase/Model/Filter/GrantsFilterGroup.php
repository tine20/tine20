<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014-2019 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @var string the alias for the acl table name
     */
    protected $_joinedTableAlias = null;

    /**
     * @var string acl record column for join with acl table
     */
    protected $_aclIdColumn = 'id';

    /**
     * @var array one of these grants must be met
     */
    protected $_requiredGrants = [
        Tinebase_Model_Grants::GRANT_READ
    ];

    protected $_tempBackend = null;

    /**
     * sets the grants this filter needs to assure
     *
     * @param array $_grants
     */
    public function setRequiredGrants(array $_grants)
    {
        $this->_requiredGrants = $_grants;
    }

    /**
     * gets aclFilter of this group
     *
     * @return array
     */
    public function getAclFilters()
    {
        return [$this];
    }

    /**
     * appends custom filters to a given select object
     *
     * @param  Zend_Db_Select||Tinebase_Backend_Sql_Filter_GroupSelect $select
     * @param  Tinebase_Backend_Sql_Abstract     $backend
     * @return void
     */
    public function appendFilterSql($select, $backend)
    {
        if ($this->_ignoreAcl) {
            return;
        }

        $user = isset($this->_options['user']) ? $this->_options['user'] : Tinebase_Core::getUser();

        $this->_joinedTableAlias = uniqid($this->_aclTableName);
        $db = $backend->getAdapter();
        $select->join(array(
            /* table  */ $this->_joinedTableAlias => SQL_TABLE_PREFIX . $this->_aclTableName),
            /* on     */ "{$db->quoteIdentifier($this->_joinedTableAlias . '.record_id')} = {$db->quoteIdentifier($backend->getTableName() . '.' . $this->_aclIdColumn)}",
            /* select */ array()
        );

        $this->_tempBackend = $backend;
        try {
            Tinebase_Container::addGrantsSql($select, $user, $this->_requiredGrants, $this->_joinedTableAlias, false,
                [$this, 'addGrantsSqlCallback']);
        } finally {
            $this->_tempBackend = null;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' $select after appending grants sql: ' . $select);
    }

    /**
     * appends container_acl sql
     *
     * @param  Zend_Db_Select    $_select
     * @param  integer           $iteration
     * @return string table identifier to work on
     */
    public function addGrantsSqlCallback($_select, $iteration)
    {
        $db = $_select->getAdapter();
        $tblAlias = $this->_aclTableName . $iteration;
        $_select->joinLeft(array(
            /* table  */ $tblAlias => SQL_TABLE_PREFIX . $this->_aclTableName),
            /* on     */ $db->quoteIdentifier($tblAlias . '.record_id') . ' = ' . $db->quoteIdentifier($this->_tempBackend->getTableName() . '.' . $this->_aclIdColumn),
            array()
        );
        return $tblAlias;
    }
}
