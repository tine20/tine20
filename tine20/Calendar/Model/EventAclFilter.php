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
 *  2. transform event set (all events user has only free/busy grant for need to be cleaned)
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
     * resolve container ids
     *
     * @todo speed up retrival of freebusyContainers for all other users by a single call to container class/table
     */
    protected function _resolve()
    {
        if ($this->_isResolved) {
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' already resolved');
            return;
        }
        
        parent::_resolve();
        
        // we only need to include free/busy if required grants contain GRANT_READ
        if (! in_array(Tinebase_Model_Grants::GRANT_READ, $this->_requiredGrants)) {
            return;
        }
        
        switch ($this->_operator) {
            case 'personalNode':
                // user always has free/busy for himself
                if ($this->_value != Tinebase_Core::getUser()->getId() && Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::FREEBUSY, $this->_value)) {
                    // get all personal containers of user!
                    $freebusyContainers = Tinebase_Container::getInstance()->getPersonalContainer($this->_value, 'Calendar', $this->_value, 0, true)->getId();
                }
                break;
                
            case 'specialNode':
                switch ($this->_value) {
                    case 'all':
                    case 'otherUsers':
                        // the difference between 'all' and 'otherUsers' is, that 'all' also include
                        // 'personalNode->currUser' and 'spechialNode->shard' 
                        // in fact, this does not make a difference from the free/busy perspective
                        
                        // get all users which have free/busy pref set to yes
                        $freebusyUsers = Tinebase_Core::getPreference('Calendar')->getUsersWithPref(Calendar_Preference::FREEBUSY, 1);
                        
                        // get personal containers for all this users
                        $freebusyContainers = array();
                        foreach ($freebusyUsers as $userId) {
                        	try {
                                $freebusyContainers = array_merge($freebusyContainers, Tinebase_Container::getInstance()->getPersonalContainer($userId, 'Calendar', $userId, 0, true)->getId());
                        	} catch (Tinebase_Exception_NotFound $e) {
                        		// TODO is it realy nessesary for eadh user to be in one group at least?
                        		Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . " Missconfiguration detected $userId does not belong to any group");
                        	}
                        }
                        break;
                        
                    // no need to include free/busy
                    default:
                        return;
                        break;
                }    
                break;
                
            // no need to include free/busy
            default:
                return;
                break;
        }
        
        if (! empty($freebusyContainers)) {
            $this->_freebusyContainers = array_diff($freebusyContainers, $this->_containerIds);
            $this->_containerIds = array_merge($this->_containerIds, $this->_freebusyContainers);
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