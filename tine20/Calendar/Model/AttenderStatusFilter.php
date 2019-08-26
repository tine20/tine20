<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * filters for events whose attendee have a certain status
 * 
 * 
 * @package     Calendar
 */
class Calendar_Model_AttenderStatusFilter extends Tinebase_Model_Filter_Text 
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
        1 => 'not',
        2 => 'in',
        3 => 'notin',
    );

    protected $_dnames = [];

    protected $_currentDname = 'attendee';
    
    /**
     * returns quoted column name for sql backend
     *
     * @param  Tinebase_Backend_Sql_Interface $_backend
     * @return string
     * 
     * @todo to be removed once we split filter model / backend
     */
    protected function _getQuotedFieldName($_backend) {
        return $_backend->getAdapter()->quoteIdentifier(
            $this->_currentDname . '.status'
        );
    }

    public function addAttendeeJoinName($dname)
    {
        $this->_dnames[$dname] = $dname;
    }

    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                $_select
     * @param  Tinebase_Backend_Sql_Abstract $_backend
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function appendFilterSql($_select, $_backend)
    {
        if (empty($this->_dnames)) {
            parent::appendFilterSql($_select, $_backend);
        } else {
            foreach ($this->_dnames as $dname) {
                $this->_currentDname = $dname;
                parent::appendFilterSql($_select, $_backend);
            }
        }
    }
}
