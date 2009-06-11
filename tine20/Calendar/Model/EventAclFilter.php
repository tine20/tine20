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
 * NOTE: If the required grant is other than GRANT_READ, we skip all free/busy 
 *       grants. In this case, also stage 2 is not nessesary!
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
        parent::appendFilterSql($_select, $_backend);
        
        // directly filter for required grant if its other than _only_ GRANT_READ
        if (count($this->_requiredGrants) > 1 || $this->_requiredGrants[0] != Tinebase_Model_Container::GRANT_READ) {
            foreach ($this->_requiredGrants as $grant) {
                $_select->orHaving($_backend->getAdapter()->quoteIdentifier('grant-' . $grant) . ' = 1');
            }
        }
    }
    
    /**
     * stage 2 checks
     * 
     * NOTE: depends on all acl filters of group
     * 
     * @param Tinebase_Model_Filter_FilterGroup
     * @param Tinebase_Record_RecordSet
     *
     */
    public static function stage2($_filterGroup, $eventSet) 
    {
        // pool together all freebusy containers
        $idFilters = $_filterGroup->getAclFilters();
        $freebusyContainers = array();
        foreach ($idFilters as $filter) {
            if ($filter instanceof Calendar_Model_EventAclFilter) {
                $freebusyContainers = array_unique(array_merge($freebusyContainers, $filter->getFreebusyContainers()));
            }
        }
        
        if (! empty($freebusyContainers)) {
            // do the actual cleanup
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
        
        // we only need to include free/busy if required grant is _only_ GRANT_READ
        if (count($this->_requiredGrants) != 1 || $this->_requiredGrants[0] != Tinebase_Model_Container::GRANT_READ) {
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
                            $freebusyContainers = array_merge($freebusyContainers, Tinebase_Container::getInstance()->getPersonalContainer($userId, 'Calendar', $userId, 0, true)->getId());
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