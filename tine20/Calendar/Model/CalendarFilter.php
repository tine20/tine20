<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Calendar Container Filter
 * 
 * NOTE: In the Calendar app ACL is managed by the GrantFilter
 *       so we ignore container ACL stuff here!
 *       
 * NOTE: An Event might be part of multiple calendars:
 *  - The originate calender represented by container_id
 *  - Multiple attendee calender represented by multiple displaycontainer_ids
 *  So if a user filters for certain calendars, we have to look for originate 
 *  and display containers. (@see Calendar_Backend_Sql for details of the data model)
 *  
 * @package Calendar
 */
class Calendar_Model_CalendarFilter extends Tinebase_Model_Filter_Container
{
    
    /**
     * @var array One of theese grants must be given
     */
    protected $_requiredGrants = NULL;
    
    /**
     * appends sql to given select statement
     * 
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * @throws Tinebase_Exception_NotFound
     */
    public function appendFilterSql($_select, $_backend)
    {
        $this->_options['ignoreAcl'] = TRUE;
        $this->_resolve();
        
        $quotedDisplayContainerIdentifier = $_backend->getAdapter()->quoteIdentifier('attendee.displaycontainer_id');
        
        $_select->where($this->_getQuotedFieldName($_backend) . ' IN (?)', empty($this->_containerIds) ? " " : $this->_containerIds);
        $_select->orWhere($quotedDisplayContainerIdentifier  .  ' IN (?)', empty($this->_containerIds) ? " " : $this->_containerIds);
    }
    
    public function setRequiredGrants(array $_grants)
    {
        $this->_requiredGrants = $_grants;
    }
}