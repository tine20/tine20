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
class Calendar_Model_AttendeeFilter extends Tinebase_Model_Filter_Abstract 
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
        1 => 'in'
    );

    /**
     * @var array maps abstract operators to sql operators
     */
    protected $_opSqlMap = array(
        'equals'     => array('sqlop' => ' = ?'   ,     'wildcards' => '?'  ),
        'in'         => array('sqlop' => ' IN (?)',     'wildcards' => '?'  ),
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
        $_select->joinLeft(
            /* table  */ array('attendee' => $_backend->getTablePrefix() . 'cal_attendee'), 
            /* on     */ $_backend->getAdapter()->quoteIdentifier('attendee.cal_event_id') . ' = ' . $_backend->getAdapter()->quoteIdentifier($_backend->getTableName() . '.id'),
            /* select */ array());
        
        foreach ($this->_value as $attendee) {
            
        }
        
        
    }
    
}