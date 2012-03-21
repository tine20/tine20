<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        move acl ensurance to persistent filter controller
 */

/**
 *  persistent filter filter class
 * 
 * @package     Tinebase
 * @subpackage  Filter 
 */
class Tinebase_Model_PersistentFilterFilter extends Tinebase_Model_Filter_FilterGroup
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
     * appends custom filters to a given select object
     * 
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * @return void
     */
    public function appendFilterSql($_select, $_backend)
    {
        // ensure acl policies
        $this->_appendAclSqlFilter($_select, $_backend);
    }
    
    /**
     * add account id to filter
     *
     * @param Zend_Db_Select $_select
     */
    protected function _appendAclSqlFilter($_select, $_backend) {
        
        if (! $this->_isResolved) {
            
            $accountIdFilter = $this->_findFilter('account_id');
            $userId = Tinebase_Core::getUser()->getId();
            
            // set user account id as filter
            if ($accountIdFilter === NULL) {
                $accountIdFilter = $this->createFilter('account_id', 'equals', $userId);
                $this->addFilter($accountIdFilter);

            } else {
                $accountIdFilter->setValue($userId);
            }
            
            $groupSelect = new Tinebase_Backend_Sql_Filter_GroupSelect($_select);
        
            $db = $_backend->getAdapter();
            $accountIdFilter->appendFilterSql($groupSelect, $_backend);
            $groupSelect->orWhere($db->quoteIdentifier('account_id') . ' IS NULL');
            
            $groupSelect->appendWhere(Zend_Db_Select::SQL_AND);
            
            $this->removeFilter('account_id');
        
            $this->_isResolved = TRUE;
        }
    }
}
