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
 * Calendar Acl Filter
 * 
 * Manages calnedar grant for search actions
 * 
 * 
 * Assuring event grants is a two stage process for search operations.
 *  1. limiting the query (mixture of grants and filter)
 *  2. transform event set (all events user has no read grant for need to be cleaned -> freebusy infos)
 * 
 * NOTE: The effective grants of the events get dynamically appended to the
 *       event rows in SQL (at the moment by the sql backend class.)
 *       As such there is no need to compute the effective grants in stage 2
 * 
 * NOTE: stage 2 is implcitly done in the models setFromArray
 * 
 * 
 * @package Calendar
 */
class Calendar_Model_EventAclFilter extends Tinebase_Model_Filter_Container 
{
    /**
     * appends sql to given select statement
     * 
     * NOTE: $this->_containerIds contains a list of calendars the user has required grant for.
     *       _BUT_ in Calendar Grants are record based!
     *       -> For 'get/sync/export' actions this is no problem, as attendee/organizer have
     *          a copy of the event in one of their personal calendars via the display_calendar
     *       -> For 'update' this is a problem, as user needs grant to direct calendar of
     *          implcit record EDIT GRANT
     *          -> more over a mass update is not a trivial operation in calendar cause of 
     *          real and displaycontaier stuff -> @todo RECHECK implementation
     *       -> There seems to be no 'delete' action in the framework yet... -> ;-)
     * 
     * @todo RETHINK do we need to include record grants in search/get operatios???
     *  --> and even if so, we would only need to append grants for which we query
     *  
     *  
     * @param  Zend_Db_Select                    $_select
     * @param  Tinebase_Backend_Sql_Abstract     $_backend
     * @throws Tinebase_Exception_NotFound
     */
    public function appendFilterSql($_select, $_backend)
    {
        $this->_resolve();
        
        $quotedDisplayContainerIdentifier = $_backend->getAdapter()->quoteIdentifier('attendee.displaycontainer_id');
        
        $_select->where($this->_getQuotedFieldName($_backend) . ' IN (?)', empty($this->_containerIds) ? " " : $this->_containerIds);
        $_select->orWhere($quotedDisplayContainerIdentifier  .  ' IN (?)', empty($this->_containerIds) ? " " : $this->_containerIds);
        
        $db = $_backend->getAdapter();
        foreach ($this->_requiredGrants as $grant) {
            $_select->orHaving($db->quoteInto($db->quoteIdentifier($grant) . ' = ?', 1, Zend_Db::INT_TYPE));
        }
    }
}