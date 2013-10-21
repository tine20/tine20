<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * MailAccount-MailAccount-Cleared filter Class
 *
 * @package     HumanResources
 * @subpackage  Model
 */
class HumanResources_Model_AccountQuicksearchFilter extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'contains',
    );
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     * 
     * @todo to be removed once we split filter model / backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        $ec = HumanResources_Controller_Employee::getInstance();
        $filter = new HumanResources_Model_EmployeeFilter(array(
            
        ));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'n_fn', 'operator' => 'contains', 'value' => $this->_value)));
        
        $employees = $ec->search($filter);
        
        $db = Tinebase_Core::getDb();
        
        if ($employees->count()) {
            $_select->where(
                $db->quoteInto($db->quoteIdentifier('employee_id') . ' IN (?) ', $employees->id, 'array')
            );
        } else {
            $_select->where(
                $db->quoteInto($db->quoteIdentifier('employee_id') . ' = (?) ', 'xxxxxxxxxxxxxxx', 'array')
            );
        }
    }
}
