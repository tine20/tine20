<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * filters for events with the given attendee
 * 
 * 
 * @package     Calendar
 */
class Calendar_Model_AttenderFilter extends Tinebase_Model_Filter_Abstract 
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
        1 => 'in'
    );

    /**
     * sets value
     *
     * @param mixed $_value
     */
    public function setValue($_value)
    {
        if ($this->_operator == 'equals') {
            $this->_value = array($_value);
        } else {
            $this->_value = $_value;
        }
    }
    
    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        $gs = new Tinebase_Backend_Sql_Filter_GroupSelect($_select);
        $adapter = $_backend->getAdapter();
        
        foreach ($this->_value as $attender) {
        	$gs->orWhere(
        	    $adapter->quoteInto($adapter->quoteIdentifier('attendee.user_type') . ' = ?', $attender['user_type']) . ' AND ' .
                $adapter->quoteInto($adapter->quoteIdentifier('attendee.user_id') .   ' = ?', $attender['user_id'])
        	);
        }
        $gs->appendWhere(Zend_Db_Select::SQL_OR);
    }
}