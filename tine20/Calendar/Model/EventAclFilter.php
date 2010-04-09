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
     * @var array freebusy containers added to query
     */
    private $_freebusyContainers = array();
    
    /**
     * appends sql to given select statement
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
        
        // directly filter for required grant is only possible if requiredgrants does not contains GRANT_READ
        if (! in_array(Tinebase_Model_Grants::GRANT_READ, $this->_requiredGrants)) {
            foreach ($this->_requiredGrants as $grant) {
                if ($grant == Tinebase_Model_Grants::GRANT_ADMIN) {
                    // admin grant not yet implemented
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Checking for admin grant is not yet implemented, results might be diffrent as expected");
                    continue;
                }
                $_select->orHaving($_backend->getAdapter()->quoteIdentifier($grant) . ' = 1');
            }
        }
    }
    
    /**
     * returns all freebusy container ids this idfilter added to query
     *
     * @return array
     */
    public function getFreebusyContainers()
    {
        return $this->_freebusyContainers;
    }
}