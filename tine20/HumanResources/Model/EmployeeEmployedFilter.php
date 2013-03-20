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
class HumanResources_Model_EmployeeEmployedFilter extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
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
        $now = new Tinebase_DateTime();
        $now->setHour(0)->setMinute(0)->setSecond(0);
        $db = Tinebase_Core::getDb();
        
        if ($this->_value == 1) {
            $_select->where(
                $db->quoteInto('(' . $db->quoteIdentifier('employment_end') . ' >= ? ', $now, 'datetime') . ' OR ' .  $db->quoteIdentifier('employment_end') . ' IS NULL )'
            );
            $_select->where(
                $db->quoteInto('( ' . $db->quoteIdentifier('employment_begin') .' <= ? ', $now, 'datetime') .' OR ( ' . $db->quoteIdentifier('employment_begin') .' IS NULL ))'
            );
        } else {
            $_select->where(
                $db->quoteInto($db->quoteIdentifier('employment_end') .' < ? ', $now, 'datetime')
            );
            $_select->orWhere(
                $db->quoteInto($db->quoteIdentifier('employment_begin') .' > ? ', $now, 'datetime')
            );
        }
    }
}
