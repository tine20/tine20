<?php
/**
 * Sql Calendar 
 * 
 * @package     Calendar
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Native tine 2.0 events sql backend
 *
 * Events consists of the properties of Calendar_Model_Event except Tags and Notes 
 * which are as always handles by their controllers/backends
 * 
 * @TODO rework fetch handling. all fetch operations should be based on search.
 *       remove old grant sql when done
 * 
 * @package     Calendar 
 * @subpackage  Backend
 */
class Calendar_Backend_Sql extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'cal_events';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Calendar_Model_Event';
    
    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;
    
    /**
     * attendee backend
     * 
     * @var Calendar_Backend_Sql_Attendee
     */
    protected $_attendeeBackend = NULL;
    
    /**
     * list of record based grants
     */
    protected $_recordBasedGrants = array(
        Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY,
        Tinebase_Model_Grants::GRANT_READ, 
        Tinebase_Model_Grants::GRANT_SYNC, 
        Tinebase_Model_Grants::GRANT_EXPORT, 
        Tinebase_Model_Grants::GRANT_EDIT, 
        Tinebase_Model_Grants::GRANT_DELETE,
        Calendar_Model_EventPersonalGrants::GRANT_PRIVATE,
    );
    
    /**
     * the constructor
     *
     * @param Zend_Db_Adapter_Abstract $_db optional
     * @param array $_options (optional)
     */
    public function __construct ($_dbAdapter = NULL, $_options = array())
    {
        parent::__construct($_dbAdapter, $_options);
        
        $this->_attendeeBackend = new Calendar_Backend_Sql_Attendee($_dbAdapter);
    }
    
    /**
     * Creates new entry
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_InvalidArgument
     * @throws  Tinebase_Exception_UnexpectedValue
     */
    public function create(Tinebase_Record_Interface $_record) 
    {
        
        if ($_record->rrule) {
            $_record->rrule = (string) $_record->rrule;
        }
        $_record->rrule   = !empty($_record->rrule)   ? $_record->rrule   : NULL;
        $_record->recurid = !empty($_record->recurid) ? $_record->recurid : NULL;

        $_record->rrule_constraints = $_record->rrule_constraints instanceof Calendar_Model_EventFilter ?
            json_encode($_record->rrule_constraints->toArray()) : NULL;
        
        $event = parent::create($_record);
        $this->_saveExdates($_record);
        //$this->_saveAttendee($_record);
        
        return $this->get($event->getId());
    }
    
    /**
     * Gets one entry (by property)
     *
     * @param  mixed  $_value
     * @param  string $_property
     * @param  bool   $_getDeleted
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_NotFound
     */
    public function getByProperty($_value, $_property = 'name', $_getDeleted = FALSE) 
    {
        //$pagination = new Tinebase_Model_Pagination(array('limit' => 1));
        $filters = new Calendar_Model_EventFilter();
        
        $filter = new Tinebase_Model_Filter_Text($_property, 'equals', $_value);
        $filters->addFilter($filter);

        if ($_getDeleted) {
            $deletedFilter = new Tinebase_Model_Filter_Bool('is_deleted', 'equals', Tinebase_Model_Filter_Bool::VALUE_NOTSET);
            $filters->addFilter($deletedFilter);
        }

        $resultSet = $this->search($filters, NULL, FALSE);
        
        switch (count($resultSet)) {
            case 0: 
                throw new Tinebase_Exception_NotFound($this->_modelName . " record with $_property " . $_value . ' not found!');
                break;
            case 1: 
                $result = $resultSet->getFirstRecord();
                break;
            default:
                throw new Tinebase_Exception_UnexpectedValue(' in total ' . count($resultSet) . ' where found. But only one should!');
        }
        
        return $result;
    }
    
    /**
     * Calendar optimized search function
     * 
     * 1. get all events neglecting grants filter
     * 2. get all related container grants (via resolving)
     * 3. compute effective grants in PHP and only keep events 
     *    user has required grant for
     * 
     * @TODO rethink if an outer container filter could help
     *
     * @param  Tinebase_Model_Filter_FilterGroup    $_filter
     * @param  Tinebase_Model_Pagination            $_pagination
     * @param  boolean                              $_onlyIds
     * @return Tinebase_Record_RecordSet|array
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_onlyIds = FALSE)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' Searching events ...');
        
        if ($_pagination === NULL) {
            $_pagination = new Tinebase_Model_Pagination();
        }

        $getDeleted = is_object($_filter) && $_filter->getFilter('is_deleted');
        $select = parent::_getSelect('*', $getDeleted);
        
        $select->joinLeft(
            /* table  */ array('exdate' => $this->_tablePrefix . 'cal_exdate'),
            /* on     */ $this->_db->quoteIdentifier('exdate.cal_event_id') . ' = ' . $this->_db->quoteIdentifier($this->_tableName . '.id'),
            /* select */ array('exdate' => $this->_dbCommand->getAggregate('exdate.exdate')));
        
        // NOTE: we join here as attendee and role filters need it
        $select->joinLeft(
            /* table  */ array('attendee' => $this->_tablePrefix . 'cal_attendee'),
            /* on     */ $this->_db->quoteIdentifier('attendee.cal_event_id') . ' = ' . $this->_db->quoteIdentifier('cal_events.id'),
            /* select */ array());
        
        if (! $getDeleted) {
            $select->joinLeft(
                /* table  */ array('dispcontainer' => $this->_tablePrefix . 'container'), 
                /* on     */ $this->_db->quoteIdentifier('dispcontainer.id') . ' = ' . $this->_db->quoteIdentifier('attendee.displaycontainer_id'),
                /* select */ array());
            
            $select->where($this->_db->quoteIdentifier('dispcontainer.is_deleted') . ' = 0 OR ' . $this->_db->quoteIdentifier('dispcontainer.is_deleted') . 'IS NULL');
        }
        
        // remove grantsfilter here as we do grants computation in PHP
        $translate = Tinebase_Translation::getTranslation('Calendar');
        $grantsFilter = null;
        // make sure $func is not yet set at this point
        $func = function($filter) use (/*yes & !*/&$func, &$grantsFilter, $translate) {
            if ($filter instanceof Calendar_Model_EventFilter) {
                $filter->filterWalk($func);
            } elseif ($filter instanceof Calendar_Model_GrantFilter) {
                if ($grantsFilter !== null) {
                    throw new Tinebase_Exception_SystemGeneric($translate->_('You can not have more than one grants filter'));
                }
                $grantsFilter = $filter;
                $filter->getParent()->removeFilter($filter);
            }
        };
        $_filter->filterWalk($func);

        
        // clone the filter, as the filter is also used in the json frontend
        // and the calendar filter is used in the UI to
        $clonedFilters = clone $_filter;
        
        // sort filters, roleFilter und statusFilter need to be processed after attenderFilter
        unset($func); // !!! very important, don't separate this and the next line
        $tempFilters = [];
        $func = function($filter) use (/*yes & !*/&$func, &$tempFilters) {
            if ($filter instanceof Calendar_Model_EventFilter) {
                $filter->filterWalk($func);
            } elseif ($filter instanceof Calendar_Model_AttenderRoleFilter || $filter instanceof
                    Calendar_Model_AttenderStatusFilter) {
                $tempFilters[] = $filter;
            }
        };
        $clonedFilters->filterWalk($func);
        foreach ($tempFilters as $tempFilter) {
            $parent = $tempFilter->getParent();
            $parent->removeFilter($tempFilter);
            $parent->addFilter($tempFilter);
        }

        
        $this->_addFilter($select, $clonedFilters);
        
        $select->group($this->_tableName . '.' . 'id');
        Tinebase_Backend_Sql_Abstract::traitGroup($select);
        
        $_pagination->appendPaginationSql($select);
        
        $stmt = $this->_db->query($select);
        $rows = (array)$stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Event base rows fetched: ' . count($rows) . ' select: ' . $select);
        
        $result = $this->_rawDataToRecordSet($rows);

        $this->_checkGrants($result, $grantsFilter);
        
        return $_onlyIds ? $result->{is_bool($_onlyIds) ? $this->_getRecordIdentifier() : $_onlyIds} : $result;
    }

    /**
     * calculate event permissions and remove events that don't match
     * 
     * @param  Tinebase_Record_RecordSet        $events
     * @param  Tinebase_Model_Filter_AclFilter  $grantsFilter
     */
    protected function _checkGrants($events, $grantsFilter)
    {
        $currentContact    = Tinebase_Core::getUser()->contact_id;
        $containerGrants   = Tinebase_Container::getInstance()->getContainerGrantsOfRecords($events, Tinebase_Core::getUser());
        $resolvedAttendees = Calendar_Model_Attender::getResolvedAttendees($events->attendee, true);
        
        $toRemove          = array();
        $inheritableGrants = array(
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY,
            Tinebase_Model_Grants::GRANT_READ,
            Tinebase_Model_Grants::GRANT_SYNC,
            Tinebase_Model_Grants::GRANT_EXPORT,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE,
        );
        
        if ($grantsFilter instanceof Calendar_Model_GrantFilter) {
            $requiredGrants = $grantsFilter->getRequiredGrants();
            if (is_array($requiredGrants)) {
                $requiredGrants = array_intersect($requiredGrants, $this->_recordBasedGrants);
            } else {
                // TODO throw exception here?
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Required grants not set in grants filter: ' . print_r($grantsFilter->toArray(), true));
            }
        }

        /** @var Calendar_Model_Event $event */
        foreach ($events as $event) {
            $containerId = $event->container_id instanceof Tinebase_Model_Container
                ? $event->container_id->getId()
                : $event->container_id;

            // either current user is organizer or has admin right on container
            if ($event->organizer === $currentContact) {
                foreach ($this->_recordBasedGrants as $grant) {
                    $event->{$grant} = true;
                }
                
                // has all rights => no need to filter
                continue;
            }
            if (isset($containerGrants[$containerId]) && $containerGrants[$containerId]
                    ->account_grants[Tinebase_Model_Grants::GRANT_ADMIN]) {
                foreach ($this->_recordBasedGrants as $grant) {
                    if (Calendar_Model_EventPersonalGrants::GRANT_PRIVATE !== $grant) {
                        $event->{$grant} = true;
                    }
                }

                // has all rights => no need to filter
                continue;
            }
            
            // grants to original container
            if (isset($containerGrants[$containerId])) {
                foreach ($this->_recordBasedGrants as $grant) {
                    $event->{$grant} = $containerGrants[$containerId]->account_grants[$grant];
                }
            }
            
            // check grant inheritance
            if ($event->attendee instanceof Tinebase_Record_RecordSet) {
                foreach ($inheritableGrants as $grant) {
                    if (! $event->{$grant}) {
                        foreach ($event->attendee as $attendee) {
                            $attendee = $resolvedAttendees->getById($attendee->getId());
                            
                            if (!$attendee) {
                                continue;
                            }
                            
                            if (   $attendee->displaycontainer_id instanceof Tinebase_Model_Container
                                && $attendee->displaycontainer_id->account_grants 
                                && (    $attendee->displaycontainer_id->account_grants[$grant]
                                     || ($attendee->displaycontainer_id->account_grants[Tinebase_Model_Grants::GRANT_ADMIN]
                                        && Calendar_Model_EventPersonalGrants::GRANT_PRIVATE !== $grant)
                                   )
                            ) {
                                $event->{$grant} = true;
                                break;
                            }
                        }
                    }
                }
            }
            
            // check if one of the grants is set ...
            if (isset($requiredGrants) && is_array($requiredGrants)) {
                foreach ($requiredGrants as $requiredGrant) {
                    if ($event->{$requiredGrant}) {
                        continue 2;
                    }
                }
                
                // ... otherwise mark for removal
                $toRemove[] = $event;
            }
        }
        
        // remove records with non matching grants
        foreach ($toRemove as $event) {
            $events->removeRecord($event);
        }
    }
    
    /**
     * get the basic select object to fetch records from the database
     *  
     * @param array|string|Zend_Db_Expr $_cols columns to get, * per default
     * @param boolean $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     */
    protected function _getSelectSimple($_cols = '*', $_getDeleted = FALSE)
    {
        $select = $this->_db->select();

        $select->from(array($this->_tableName => $this->_tablePrefix . $this->_tableName), $_cols);
        
        if (!$_getDeleted && $this->_modlogActive) {
            // don't fetch deleted objects
            $select->where($this->_db->quoteIdentifier($this->_tableName . '.is_deleted') . ' = 0');
        }
        
        return $select;
    }
    
    /**
     * Gets total count of search with $_filter
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return int
     */
    public function searchCount(Tinebase_Model_Filter_FilterGroup $_filter)
    {
        $select = $this->_getSelect(array('count' => 'COUNT(*)'));
        $this->_addFilter($select, $_filter);

        $result = $this->_db->fetchOne($select);
        
        return $result;
    }
    
    /**
     * Updates existing entry
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_Record_Validation|Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_Interface Record|NULL
     */
    public function update(Tinebase_Record_Interface $_record) 
    {
        if ($_record->rrule) {
            $_record->rrule = (string) $_record->rrule;
        }
        
        if ($_record->container_id instanceof Tinebase_Model_Container) {
            $_record->container_id = $_record->container_id->getId();
        }
        
        $_record->rrule   = !empty($_record->rrule)   ? $_record->rrule   : NULL;
        $_record->recurid = !empty($_record->recurid) ? $_record->recurid : NULL;

        $_record->rrule_constraints = $_record->rrule_constraints instanceof Calendar_Model_EventFilter ?
            json_encode($_record->rrule_constraints->toArray()) : NULL;
        
        $event = parent::update($_record);
        $this->_saveExdates($_record);
        
        return $this->get($event->getId(), TRUE);
    }

    /**
     * get the basic select object to fetch records from the database
     *  
     * @param array|string|Zend_Db_Expr $_cols columns to get, * per default
     * @param boolean $_getDeleted get deleted records (if modlog is active)
     * @return Zend_Db_Select
     */
    protected function _getSelect($_cols = '*', $_getDeleted = FALSE)
    {
        $select = $this->_getSelectSimple();

        $this->_appendEffectiveGrantCalculationSql($select);
        
        $select->joinLeft(
            /* table  */ array('exdate' => $this->_tablePrefix . 'cal_exdate'), 
            /* on     */ $this->_db->quoteIdentifier('exdate.cal_event_id') . ' = ' . $this->_db->quoteIdentifier($this->_tableName . '.id'),
            /* select */ array('exdate' => $this->_dbCommand->getAggregate('exdate.exdate')));
        
        $select->group($this->_tableName . '.' . 'id');
        
        return $select;
    }
    
    /**
     * appends effective grant calculation to select object
     *
     * @param Zend_Db_Select $_select
     */
    protected function _appendEffectiveGrantCalculationSql($_select, $_attendeeFilters = NULL)
    {
        // groupmemberships of current user, needed to compute phys and inherited grants
        $_select->joinLeft(
            /* table  */ array('groupmemberships' => $this->_tablePrefix . 'group_members'), 
            /* on     */ $this->_db->quoteInto($this->_db->quoteIdentifier('groupmemberships.account_id') . ' = ?' , Tinebase_Core::getUser()->getId()),
            /* select */ array());

        $_select->joinLeft(
            /* table  */ array('rolememberships' => $this->_tablePrefix . 'role_accounts'),
            /* on     */ $this->_db->quoteInto($this->_db->quoteIdentifier('rolememberships.account_id') . ' = ?' , Tinebase_Core::getUser()->getId())
                        . ' AND ' . $this->_db->quoteInto($this->_db->quoteIdentifier('rolememberships.account_type') . ' = ?', Tinebase_Acl_Rights::ACCOUNT_TYPE_USER),
            /* select */ array());
        
        // attendee joins the attendee we need to compute the curr users effective grants
        // NOTE: 2010-04 the behaviour changed. Now, only the attendee the client filters for are 
        //       taken into account for grants calculation 
        $attendeeWhere = FALSE;
        if (is_array($_attendeeFilters) && !empty($_attendeeFilters)) {
            $attendeeSelect = $this->_db->select();
            foreach ((array) $_attendeeFilters as $attendeeFilter) {
                if ($attendeeFilter instanceof Calendar_Model_AttenderFilter) {
                    $attendeeFilter->appendFilterSql($attendeeSelect, $this);
                }
            }
            
            $whereArray = $attendeeSelect->getPart(Zend_Db_Select::SQL_WHERE);
            if (! empty($whereArray)) {
                $attendeeWhere = ' AND ' . Tinebase_Helper::array_value(0, $whereArray);
            }
        }
        
        $_select->joinLeft(
            /* table  */ array('attendee' => $this->_tablePrefix . 'cal_attendee'),
            /* on     */ $this->_db->quoteIdentifier('attendee.cal_event_id') . ' = ' . $this->_db->quoteIdentifier('cal_events.id') . 
                            $attendeeWhere,
            /* select */ array());
        

            
        $_select->joinLeft(
            /* table  */ array('attendeeaccounts' => $this->_tablePrefix . 'accounts'), 
            /* on     */ $this->_db->quoteIdentifier('attendeeaccounts.contact_id') . ' = ' . $this->_db->quoteIdentifier('attendee.user_id') . ' AND (' . 
                            $this->_db->quoteInto($this->_db->quoteIdentifier('attendee.user_type') . '= ?', Calendar_Model_Attender::USERTYPE_USER) . ' OR ' .
                            $this->_db->quoteInto($this->_db->quoteIdentifier('attendee.user_type') . '= ?', Calendar_Model_Attender::USERTYPE_GROUPMEMBER) . 
                        ')',
            /* select */ array());
        
        $_select->joinLeft(
            /* table  */ array('attendeegroupmemberships' => $this->_tablePrefix . 'group_members'), 
            /* on     */ $this->_db->quoteIdentifier('attendeegroupmemberships.account_id') . ' = ' . $this->_db->quoteIdentifier('attendeeaccounts.contact_id'),
            /* select */ array());

        $_select->joinLeft(
        /* table  */ array('attendeerolememberships' => $this->_tablePrefix . 'role_accounts'),
            /* on     */ $this->_db->quoteIdentifier('attendeerolememberships.account_id') . ' = ' . $this->_db->quoteIdentifier('attendeeaccounts.id')
                         . ' AND ' . $this->_db->quoteInto($this->_db->quoteIdentifier('attendeerolememberships.account_type') . ' = ?', Tinebase_Acl_Rights::ACCOUNT_TYPE_USER),
            /* select */ array());

        
        $_select->joinLeft(
            /* table  */ array('dispgrants' => $this->_tablePrefix . 'container_acl'), 
            /* on     */ $this->_db->quoteIdentifier('dispgrants.container_id') . ' = ' . $this->_db->quoteIdentifier('attendee.displaycontainer_id') . 
                           ' AND ' . $this->_getContainGrantCondition('dispgrants', 'groupmemberships', 'rolememberships'),
            /* select */ array());
        
        $_select->joinLeft(
            /* table  */ array('physgrants' => $this->_tablePrefix . 'container_acl'), 
            /* on     */ $this->_db->quoteIdentifier('physgrants.container_id') . ' = ' . $this->_db->quoteIdentifier('cal_events.container_id'),
            /* select */ array());
        
        $allGrants = Tinebase_Model_Grants::getAllGrants();
        
        foreach ($allGrants as $grant) {
            if (in_array($grant, $this->_recordBasedGrants)) {
                $_select->columns(array($grant => new Zend_Db_Expr("\n MAX( CASE WHEN ( \n" .
                    '  /* physgrant */' . $this->_getContainGrantCondition('physgrants', 'groupmemberships', 'rolememberships', $grant) . " OR \n" .
                    '  /* implicit  */' . $this->_getImplicitGrantCondition($grant) . " OR \n" .
                    '  /* inherited */' . $this->_getInheritedGrantCondition($grant) . " \n" .
                 ") THEN 1 ELSE 0 END ) ")));
            } else {
                $_select->columns(array($grant => new Zend_Db_Expr("\n MAX( CASE WHEN ( \n" .
                    '  /* physgrant */' . $this->_getContainGrantCondition('physgrants', 'groupmemberships', 'rolememberships', $grant) . "\n" .
                ") THEN 1 ELSE 0 END ) ")));
            }
        }
    }
    
    /**
     * returns SQL with container grant condition 
     *
     * @param  string                               $_aclTableName
     * @param  string                               $_groupMembersTableName
     * @param  string                               $_roleMembersTableName
     * @param  string|array                         $_requiredGrant (defaults none)
     * @param  Zend_Db_Expr|int|Tinebase_Model_User $_user (defaults current user)
     * @return string
     */
    protected function _getContainGrantCondition($_aclTableName, $_groupMembersTableName, $_roleMembersTableName, $_requiredGrant=NULL, $_user=NULL )
    {
        $quoteTypeIdentifier = $this->_db->quoteIdentifier($_aclTableName . '.account_type');
        $quoteIdIdentifier = $this->_db->quoteIdentifier($_aclTableName . '.account_id');
        
        if ($_user instanceof Zend_Db_Expr) {
            $userExpression = $_user;
        } else {
            $accountId = $_user ? Tinebase_Model_User::convertUserIdToInt($_user) : Tinebase_Core::getUser()->getId();
            $userExpression = new Zend_Db_Expr($this->_db->quote($accountId));
        }
        
        $sql = $this->_db->quoteInto(    "($quoteTypeIdentifier = ?", Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)  . " AND $quoteIdIdentifier = $userExpression)" .
               $this->_db->quoteInto(" OR ($quoteTypeIdentifier = ?", Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP) . ' AND ' . $this->_db->quoteIdentifier("$_groupMembersTableName.group_id") . " = $quoteIdIdentifier" . ')' .
               ($this->_db instanceof Zend_Db_Adapter_Pdo_Pgsql ?
               $this->_db->quoteInto(" OR ($quoteTypeIdentifier = ?", Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE) . ' AND CAST(' . $this->_db->quoteIdentifier("$_roleMembersTableName.role_id") . " AS text) = $quoteIdIdentifier" . ')' :
               $this->_db->quoteInto(" OR ($quoteTypeIdentifier = ?", Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE) . ' AND ' . $this->_db->quoteIdentifier("$_roleMembersTableName.role_id") . " = $quoteIdIdentifier" . ')') .
               $this->_db->quoteInto(" OR ($quoteTypeIdentifier = ?)", Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE);
        
        if ($_requiredGrant) {
            $sql = "($sql) AND " . $this->_db->quoteInto($this->_db->quoteIdentifier($_aclTableName . '.account_grant') . ' IN (?)', (array)$_requiredGrant);
            
        }
        
        return "($sql)";
    }
    
    /**
     * returns SQL condition for implicit grants
     *
     * @param  string               $_requiredGrant
     * @param  Tinebase_Model_User  $_user (defaults to current user)
     * @return string
     */
    protected function _getImplicitGrantCondition($_requiredGrant, $_user=NULL)
    {
        $accountId = $_user ? $_user->getId() : Tinebase_Core::getUser()->getId();
        $contactId = $_user ? $_user->contact_id : Tinebase_Core::getUser()->contact_id;
        
        // delte grant couldn't be gained implicitly
        if ($_requiredGrant == Tinebase_Model_Grants::GRANT_DELETE) {
            return '1=0';
        }
        
        // organizer gets all other grants implicitly
        $sql = $this->_db->quoteIdentifier('cal_events.organizer') . " = " . $this->_db->quote($contactId);
        
        // attendee get read, sync, export and private grants implicitly
        if (in_array($_requiredGrant, array(Tinebase_Model_Grants::GRANT_READ, Tinebase_Model_Grants::GRANT_SYNC, Tinebase_Model_Grants::GRANT_EXPORT,
            Calendar_Model_EventPersonalGrants::GRANT_PRIVATE
        ))) {
            $readCond = $this->_db->quoteIdentifier('attendeeaccounts.id') . ' = ' . $this->_db->quote($accountId) . ' AND (' .
                $this->_db->quoteInto($this->_db->quoteIdentifier('attendee.user_type') . ' = ?', Calendar_Model_Attender::USERTYPE_USER) . ' OR ' .
                $this->_db->quoteInto($this->_db->quoteIdentifier('attendee.user_type') . ' = ?', Calendar_Model_Attender::USERTYPE_GROUPMEMBER) .
            ')';
            
            $sql = "($sql) OR ($readCond)";
        }
        
        return "($sql)";
    }
    
    /**
     * returns SQL for inherited grants
     *
     * @param  string $_requiredGrant
     * @return string
     */
    protected function _getInheritedGrantCondition($_requiredGrant)
    {
        // current user needs to have grant on display calendar
        $sql = $this->_getContainGrantCondition('dispgrants', 'groupmemberships', 'rolememberships', $_requiredGrant);
        
        // _AND_ attender(admin) of display calendar needs to have grant on phys calendar
        // @todo include implicit inherited grants
        if (! in_array($_requiredGrant, array(Tinebase_Model_Grants::GRANT_READ,
            Calendar_Model_EventPersonalGrants::GRANT_FREEBUSY
        ))) {
            $userExpr = new Zend_Db_Expr($this->_db->quoteIdentifier('attendeeaccounts.id'));
            
            $attenderPhysGrantCond = $this->_getContainGrantCondition('physgrants', 'attendeegroupmemberships', 'attendeerolememberships', $_requiredGrant, $userExpr);
            // NOTE: this condition is weak! Not some attendee must have implicit grant.
            //       -> an attender we have reqired grants for his diplay cal must have implicit grants
            //$attenderImplicitGrantCond = $this->_getImplicitGrantCondition($_requiredGrant, $userExpr);
            
            //$sql = "($sql) AND ($attenderPhysGrantCond) OR ($attenderImplicitGrantCond)";
            $sql = "($sql) AND ($attenderPhysGrantCond)";
        }
        
        return "($sql)";
    }
    
    /**
     * converts raw data from adapter into a single record
     *
     * @param  array $_data
     * @return Tinebase_Record_Interface
     */
    protected function _rawDataToRecord(array &$_rawData) {
        $_rawData['rrule_constraints'] = Tinebase_Helper::is_json($_rawData['rrule_constraints']) ?
            json_decode($_rawData['rrule_constraints'], true) : NULL;

        $event = parent::_rawDataToRecord($_rawData);
        
        $this->appendForeignRecordSetToRecord($event, 'attendee', 'id', Calendar_Backend_Sql_Attendee::FOREIGNKEY_EVENT, $this->_attendeeBackend);
        
        return $event;
    }
    
    /**
     * converts raw data from adapter into a set of records
     *
     * @param  array $_rawData of arrays
     * @return Tinebase_Record_RecordSet
     */
    protected function _rawDataToRecordSet(array &$_rawData)
    {
        $events = new Tinebase_Record_RecordSet($this->_modelName);
        $events->addIndices(array('rrule', 'recurid'));
        
        foreach ($_rawData as $rawEvent) {
            $rawEvent['rrule_constraints'] = isset($rawEvent['rrule_constraints']) && Tinebase_Helper::is_json($rawEvent['rrule_constraints']) ?
                json_decode($rawEvent['rrule_constraints'], true) : NULL;

            $events->addRecord(new Calendar_Model_Event($rawEvent, true));
        }
        
        $this->appendForeignRecordSetToRecordSet($events, 'attendee', 'id', Calendar_Backend_Sql_Attendee::FOREIGNKEY_EVENT, $this->_attendeeBackend);
        
        return $events;
    }
    
    /**
     * saves exdates of an event
     *
     * @param Calendar_Model_Event $_event
     */
    protected function _saveExdates($_event)
    {
        $this->_db->delete($this->_tablePrefix . 'cal_exdate', $this->_db->quoteInto($this->_db->quoteIdentifier('cal_event_id') . '= ?', $_event->getId()));
        
        // only save exdates if its an recurring event
        if (! empty($_event->rrule)) {
            foreach ((array)$_event->exdate as $exdate) {
                if (is_object($exdate)) {
                    $this->_db->insert($this->_tablePrefix . 'cal_exdate', array(
                        'id'           => $_event->generateUID(),
                        'cal_event_id' => $_event->getId(),
                        'exdate'       => $exdate->get(Tinebase_Record_Abstract::ISO8601LONG)
                    ));
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                       . ' Exdate needs to be an object:' . var_export($exdate, TRUE));
                }
            }
        }
    }
    
    /**
     * saves attendee of given event
     * 
     * @param Calendar_Model_Evnet $_event
     *
    protected function _saveAttendee($_event)
    {
        $attendee = $_event->attendee instanceof Tinebase_Record_RecordSet ? 
            $_event->attendee : 
            new Tinebase_Record_RecordSet($this->_attendeeBackend->getModelName());
        $attendee->cal_event_id = $_event->getId();
            
        $currentAttendee = $this->_attendeeBackend->getMultipleByProperty($_event->getId(), Calendar_Backend_Sql_Attendee::FOREIGNKEY_EVENT);
        
        $diff = $currentAttendee->getMigration($attendee->getArrayOfIds());
        $this->_attendeeBackend->delete($diff['toDeleteIds']);
        
        foreach ($attendee as $attende) {
            $method = $attende->getId() ? 'update' : 'create';
            $this->_attendeeBackend->$method($attende);
        }
    }
    */
    
    /****************************** attendee functions ************************/
    
    /**
     * gets attendee of a given event
     *
     * @param Calendar_Model_Event $_event
     * @return Tinebase_Record_RecordSet
     */
    public function getEventAttendee(Calendar_Model_Event $_event)
    {
        $attendee = $this->_attendeeBackend->getMultipleByProperty($_event->getId(), Calendar_Backend_Sql_Attendee::FOREIGNKEY_EVENT);
        
        return $attendee;
    }
    
    /**
     * creates given attender in database
     *
     * @param Calendar_Model_Attender $_attendee
     * @return Calendar_Model_Attender
     */
    public function createAttendee(Calendar_Model_Attender $_attendee)
    {
        if ($_attendee->user_id instanceof Addressbook_Model_Contact) {
            $_attendee->user_id = $_attendee->user_id->getId();
        } else if ($_attendee->user_id instanceof Addressbook_Model_List) {
            $_attendee->user_id = $_attendee->user_id->group_id;
        }
        
        if ($_attendee->displaycontainer_id instanceof Tinebase_Model_Container) {
            $_attendee->displaycontainer_id = $_attendee->displaycontainer_id->getId();
        }
        
        return $this->_attendeeBackend->create($_attendee);
    }
    
    /**
     * updates given attender in database
     *
     * @param Calendar_Model_Attender $_attendee
     * @return Calendar_Model_Attender
     */
    public function updateAttendee(Calendar_Model_Attender $_attendee)
    {
        if ($_attendee->user_id instanceof Addressbook_Model_Contact) {
            $_attendee->user_id = $_attendee->user_id->getId();
        } else if ($_attendee->user_id instanceof Addressbook_Model_List) {
            $_attendee->user_id = $_attendee->user_id->group_id;
        }
        
        if ($_attendee->displaycontainer_id instanceof Tinebase_Model_Container) {
            $_attendee->displaycontainer_id = $_attendee->displaycontainer_id->getId();
        }
        
        return $this->_attendeeBackend->update($_attendee);
    }
    
    /**
     * deletes given attender in database
     *
     * @param Calendar_Model_Attender $_attendee
     * @return void
     */
    public function deleteAttendee(array $_ids)
    {
        return $this->_attendeeBackend->delete($_ids);
    }

    /**
     * delete duplicate events defined by an event filter
     * 
     * @param Calendar_Model_EventFilter $filter
     * @param boolean $dryrun
     * @return integer number of deleted events
     */
    public function deleteDuplicateEvents($filter, $dryrun = TRUE)
    {
        if ($dryrun && Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' - Running in dry run mode - using filter: ' . print_r($filter->toArray(), true));
        
        $duplicateFields = array('summary', 'dtstart', 'dtend');
        
        $select = $this->_db->select();
        $select->from(array($this->_tableName => $this->_tablePrefix . $this->_tableName), $duplicateFields);
        $select->where($this->_db->quoteIdentifier($this->_tableName . '.is_deleted') . ' = 0');
            
        $this->_addFilter($select, $filter);
        
        $select->group($duplicateFields)
               ->having('count(*) > 1');
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
           . ' ' . $select);
        
        $rows = $this->_fetch($select, self::FETCH_ALL);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
           . ' ' . print_r($rows, TRUE));
        
        $toDelete = array();
        foreach ($rows as $row) {
            $index = $row['summary'] . ' / ' . $row['dtstart'] . ' - ' . $row['dtend'];
            
            $filter = new Calendar_Model_EventFilter(array(array(
                'field'    => 'summary',
                'operator' => 'equals',
                'value'    => $row['summary'],
            ), array(
                'field'    => 'dtstart',
                'operator' => 'equals',
                'value'    => new Tinebase_DateTime($row['dtstart']),
            ), array(
                'field'    => 'dtend',
                'operator' => 'equals',
                'value'    => new Tinebase_DateTime($row['dtend']),
            )));
            $pagination = new Tinebase_Model_Pagination(array('sort' => array($this->_tableName . '.last_modified_time', $this->_tableName . '.creation_time'))); 
            
            $select = $this->_db->select();
            $select->from(array($this->_tableName => $this->_tablePrefix . $this->_tableName));
            $select->where($this->_db->quoteIdentifier($this->_tableName . '.is_deleted') . ' = 0');
            
            $this->_addFilter($select, $filter);
            $pagination->appendPaginationSql($select);
            
            $rows = $this->_fetch($select, self::FETCH_ALL);
            $events = $this->_rawDataToRecordSet($rows);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                . ' ' . print_r($events->toArray(), TRUE));
            
            $deleteIds = $events->getArrayOfIds();
            // keep the first
            array_shift($deleteIds);
            
            if (! empty($deleteIds)) {
                $deleteContainerIds = ($events->container_id);
                $origContainer = array_shift($deleteContainerIds);
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                    . ' Deleting ' . count($deleteIds) . ' duplicates of: ' . $index . ' in container_ids ' . implode(',', $deleteContainerIds) . ' (origin container: ' . $origContainer . ')');
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                    . ' ' . print_r($deleteIds, TRUE));
                
                $toDelete = array_merge($toDelete, $deleteIds);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                   . ' No duplicates found for ' . $index);
            }
        }
        
        if (empty($toDelete)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' No duplicates found.');
            $result = 0;
        } else {
            $result = ($dryrun) ? count($toDelete) : $this->delete($toDelete);
        }
        
        return $result;
    }
    
    /**
     * repair dangling attendee records (no displaycontainer_id)
     *
     * @see https://forge.tine20.org/mantisbt/view.php?id=8172
     */
    public function repairDanglingDisplaycontainerEvents()
    {
        $filter = new Tinebase_Model_Filter_FilterGroup();
        $filter->addFilter(new Tinebase_Model_Filter_Text(array(
            'field'     => 'user_type',
            'operator'  => 'in', 
            'value'     => array(
                Calendar_Model_Attender::USERTYPE_USER,
                Calendar_Model_Attender::USERTYPE_GROUPMEMBER,
                Calendar_Model_Attender::USERTYPE_RESOURCE
            )
        )));
        
        $filter->addFilter(new Tinebase_Model_Filter_Text(array(
            'field'     => 'displaycontainer_id',
            'operator'  => 'isnull',
            'value'     => null
        )));
        
        $danglingAttendee = $this->_attendeeBackend->search($filter);
        $danglingContactAttendee = $danglingAttendee->filter('user_type', '/'. Calendar_Model_Attender::USERTYPE_USER . '|'. Calendar_Model_Attender::USERTYPE_GROUPMEMBER .'/', TRUE);
        $danglingContactIds = array_unique($danglingContactAttendee->user_id);
        $danglingContacts = Addressbook_Controller_Contact::getInstance()->getMultiple($danglingContactIds, TRUE);
        $danglingResourceAttendee = $danglingAttendee->filter('user_type', Calendar_Model_Attender::USERTYPE_RESOURCE);
        $danglingResourceIds =  array_unique($danglingResourceAttendee->user_id);
        Calendar_Controller_Resource::getInstance()->doContainerACLChecks(false);
        $danglingResources = Calendar_Controller_Resource::getInstance()->getMultiple($danglingResourceIds, TRUE);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Processing ' . count($danglingContactIds) . ' dangling contact ids...');
        
        foreach ($danglingContactIds as $danglingContactId) {
            $danglingContact = $danglingContacts->getById($danglingContactId);
            if ($danglingContact && $danglingContact->account_id) {
                
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Get default display container for account ' . $danglingContact->account_id);
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                    . ' ' . print_r($danglingContact->toArray(), true));
                
                $displayCalId = Calendar_Controller_Event::getDefaultDisplayContainerId($danglingContact->account_id);
                if ($displayCalId) {
                    // finaly repair attendee records
                    $attendeeRecords = $danglingContactAttendee->filter('user_id', $danglingContactId);
                    $this->_attendeeBackend->updateMultiple($attendeeRecords->getId(), array('displaycontainer_id' => $displayCalId));
                    Tinebase_Core::getLogger()->NOTICE(__METHOD__ . '::' . __LINE__ . " repaired the following contact attendee " . print_r($attendeeRecords->toArray(), TRUE));
                }
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Processing ' . count($danglingResourceIds) . ' dangling resource ids...');
        
        foreach ($danglingResourceIds as $danglingResourceId) {
            $resource = $danglingResources->getById($danglingResourceId);
            if ($resource && $resource->container_id) {
                $displayCalId = $resource->container_id;
                $attendeeRecords = $danglingResourceAttendee->filter('user_id', $danglingResourceId);
                $this->_attendeeBackend->updateMultiple($attendeeRecords->getId(), array('displaycontainer_id' => $displayCalId));
                Tinebase_Core::getLogger()->NOTICE(__METHOD__ . '::' . __LINE__ . " repaired the following resource attendee " . print_r($attendeeRecords->toArray(), TRUE));
            }
        }
    }

    /**
     * @param Tinebase_Model_Container $sourceContainer
     * @param Tinebase_Model_Container $destinationContainer
     */
    public function moveEventsToContainer(Tinebase_Model_Container $sourceContainer, Tinebase_Model_Container $destinationContainer)
    {
        $this->_db->update($this->_tablePrefix . $this->_tableName, array('container_id' => $destinationContainer->getId()),
            $this->_db->quoteInto($this->_db->quoteIdentifier('container_id') . ' = ?', $sourceContainer->getId()));

        $attendeeBackend = new Calendar_Backend_Sql_Attendee();
        $attendeeBackend->moveEventsToContainer($sourceContainer, $destinationContainer);
    }

    /**
     * @param string $oldContactId
     * @param string $newContactId
     */
    public function replaceContactId($oldContactId, $newContactId)
    {
        $this->_db->update($this->_tablePrefix . $this->_tableName, array('organizer' => $newContactId),
            $this->_db->quoteInto($this->_db->quoteIdentifier('organizer') . ' = ?', $oldContactId));

        $attendeeBackend = new Calendar_Backend_Sql_Attendee();
        $attendeeBackend->replaceContactId($oldContactId, $newContactId);
    }

    /**
     * takes event ids, filters out recuring events and returns only the uids of the base events of those event ids.
     *
     * @param array $eventIds
     * @return array
     */
    public function getUidOfBaseEvents(array $eventIds)
    {
        if (count($eventIds) === 0) {
            return array();
        }

        array_walk($eventIds, function (&$val) { if (!is_string($val)) { $val = (string)$val; }});

        // we might want to return is_deleted = true here! so no condition to filter deleted events!
        $select = $this->_db->select()->distinct(true)
            ->from($this->_tablePrefix . $this->_tableName, 'uid')
            ->where($this->_db->quoteIdentifier('id') . ' IN (?)', $eventIds);

        $stmt = $this->_db->query($select);

        return $stmt->fetchAll(Zend_Db::FETCH_NUM);
    }

    /**
     * increases seq by one for all records for given container
     *
     * @param string $containerId
     * @return void
     */
    public function increaseSeqsForContainerId($containerId)
    {
        $stmt = $this->_db->query('SELECT ev.id FROM tine20_cal_events AS ev WHERE ev.is_deleted = 0 AND ' .
            'ev.recurid IS NULL AND (ev.container_id = ' . $this->_db->quote($containerId) . ' OR ev.id IN (
            SELECT cal_event_id FROM tine20_cal_attendee WHERE displaycontainer_id = ' . $this->_db->quote($containerId) . ')) GROUP BY ev.id');

        $result = $stmt->fetchAll();
        if (empty($result)) {
            return;
        }

        $seq = $this->_db->quoteIdentifier('seq');
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' IN (?)', $result);
        $this->_db->query("UPDATE {$this->_tablePrefix}{$this->_tableName} SET $seq = $seq +1 WHERE $where");
    }

    /**
     * returns the seq of one event
     *
     * @param string $eventId
     * @return string
     * @throws Tinebase_Exception_NotFound
     */
    public function getIdSeq($eventId, $containerId)
    {
        $select = $this->_db->select()
            ->from(array('ev' => $this->_tablePrefix . $this->_tableName), array('id', 'seq'))
            ->joinLeft(array('at' => $this->_tablePrefix . 'cal_attendee'), 'ev.id = at.cal_event_id', NULL)
            ->where($this->_db->quoteInto('(' . $this->_db->quoteIdentifier('ev.id') . ' = ? OR ', $eventId) .
                $this->_db->quoteInto($this->_db->quoteIdentifier('ev.uid') . ' = ? ) AND ev.is_deleted = 0 AND ' .
                    $this->_db->quoteIdentifier('ev.recurid') . ' IS NULL AND (', $eventId) .
                $this->_db->quoteIdentifier('ev.container_id') . ' = ? OR ' .
                $this->_db->quoteInto($this->_db->quoteIdentifier('at.displaycontainer_id') . ' = ? )', $containerId), $containerId);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " sql: " . $select->assemble());

        $stmt = $this->_db->query($select);

        if (($row = $stmt->fetch(Zend_Db::FETCH_NUM)) === false) {
            throw new Tinebase_Exception_NotFound('event not found');
        }

        return $row;
    }
}
