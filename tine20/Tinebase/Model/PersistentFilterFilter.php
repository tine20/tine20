<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        move acl ensurance to persistent filter controller
 * @todo        implement + use generic grant filter with Tinebase_Container::addGrantsSql)
 *                 -> see for example Tinebase_CustomField_Config::getByAcl()
 *                 -> or generalize _appendGrantsFilter()
 */

/**
 *  persistent filter filter class
 * 
 * @package     Tinebase
 * @subpackage  Filter 
 */
class Tinebase_Model_PersistentFilterFilter extends Tinebase_Model_Filter_GrantsFilterGroup
{
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Tinebase_Model_PersistentFilter';
    
    /**
     * @var string acl table name
     */
    protected $_aclTableName = 'filter_acl';
    
    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'             => array('filter' => 'Tinebase_Model_Filter_Id'),
        'query'          => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('name'))),
        'application_id' => array('filter' => 'Tinebase_Model_Filter_Id'),
        'account_id'     => array('filter' => 'Tinebase_Model_Filter_Id'),
        'name'           => array('filter' => 'Tinebase_Model_Filter_Text'),
        'model'          => array('filter' => 'Tinebase_Model_Filter_Text'),
    );
    
    /**
     * is acl filter resolved?
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
     * set options 
     *
     * @param  array $_options
     * @throws Tinebase_Exception_Record_NotDefined
     */
    protected function _setOptions(array $_options)
    {
        $_options['ignoreAcl'] = isset($_options['ignoreAcl']) ? $_options['ignoreAcl'] : false;
        
        $this->_options = $_options;
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
            $user = Tinebase_Core::getUser();
            
            // if this is called from setup, no user instance exists
            if (is_string($user)) {
                return;
            }
            
            $this->_appendAccountFilter($select, $backend, $user);
            
            if (! $this->_options['ignoreAcl']) {
                $this->_appendGrantsFilter($select, $backend, $user);
            }
            
            $this->_isResolved = TRUE;
        }
    }
    
    /**
     * append accountfilter
     * 
     * @param Zend_Db_Select $select
     * @param Tinebase_Backend_Sql_Abstract $backend
     * @param Tinebase_Model_User $user
     */
    protected function _appendAccountFilter($select, $backend, $user)
    {
        $accountIdFilter = $this->_findFilter('account_id');
        $userId = $user->getId();
        
        // set user account id as filter
        if ($accountIdFilter === null) {
            $accountIdFilter = $this->createFilter('account_id', 'equals', $userId);
            $this->addFilter($accountIdFilter);
        } else {
            $accountIdFilter->setValue($userId);
        }
        
        $groupSelect = new Tinebase_Backend_Sql_Filter_GroupSelect($select);
    
        $db = $backend->getAdapter();
        $accountIdFilter->appendFilterSql($groupSelect, $backend);
        $groupSelect->orWhere($db->quoteIdentifier('filter.account_id') . ' IS NULL');
        $groupSelect->appendWhere(Zend_Db_Select::SQL_AND);
        
        $this->removeFilter('account_id');
    }
}
