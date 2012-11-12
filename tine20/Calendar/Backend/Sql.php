<?php
/**
 * Sql Calendar 
 * 
 * @package     Calendar
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
        Tinebase_Model_Grants::GRANT_FREEBUSY,
        Tinebase_Model_Grants::GRANT_READ, 
        Tinebase_Model_Grants::GRANT_SYNC, 
        Tinebase_Model_Grants::GRANT_EXPORT, 
        Tinebase_Model_Grants::GRANT_EDIT, 
        Tinebase_Model_Grants::GRANT_DELETE, 
        Tinebase_Model_Grants::GRANT_PRIVATE,
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
        
        // for get operations we need to take all attendee into account
        $filters->addFilter($filters->createFilter('attender', 'specialNode', 'all'));
        
        $resultSet = $this->search($filters, NULL, FALSE, $_getDeleted);
        
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
     * 2. get all related container grants (via resolveing)
     * 3. compute effective grants in PHP and only keep events 
     *    user has required grant for
     * 
     * @TODO rethink if an outer container filter could help
     *
     * @param  Tinebase_Model_Filter_FilterGroup    $_filter
     * @param  Tinebase_Model_Pagination            $_pagination
     * @param  boolean                              $_onlyIds
     * @param  bool   $_getDeleted
     * @return Tinebase_Record_RecordSet|array
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Model_Pagination $_pagination = NULL, $_onlyIds = FALSE, $_getDeleted = FALSE)    
    {
        if ($_pagination === NULL) {
            $_pagination = new Tinebase_Model_Pagination();
        }
        
        $select = parent::_getSelect('*', $_getDeleted);
        
        $select->joinLeft(
            /* table  */ array('exdate' => $this->_tablePrefix . 'cal_exdate'),
            /* on     */ $this->_db->quoteIdentifier('exdate.cal_event_id') . ' = ' . $this->_db->quoteIdentifier($this->_tableName . '.id'),
            /* select */ array('exdate' => $this->_dbCommand->getAggregate('exdate.exdate')));
        
        $select->joinLeft(
            /* table  */ array('attendee' => $this->_tablePrefix . 'cal_attendee'),
            /* on     */ $this->_db->quoteIdentifier('attendee.cal_event_id') . ' = ' . $this->_db->quoteIdentifier('cal_events.id'),
            /* select */ array());
        
        if (! $_getDeleted) {
            $select->joinLeft(
                /* table  */ array('dispcontainer' => $this->_tablePrefix . 'container'), 
                /* on     */ $this->_db->quoteIdentifier('dispcontainer.id') . ' = ' . $this->_db->quoteIdentifier('attendee.displaycontainer_id'),
                /* select */ array());
            
            $select->where($this->_db->quoteIdentifier('dispcontainer.is_deleted') . ' = 0 OR ' . $this->_db->quoteIdentifier('dispcontainer.is_deleted') . 'IS NULL');
        }
        
        // remove grantsfilter here as we do grants computation in PHP
        $grantsFilter = $_filter->getFilter('grants');
        if ($grantsFilter) {
            $_filter->removeFilter('grants');
        }
        
        $this->_addFilter($select, $_filter);
        $_pagination->appendPaginationSql($select);
        
        $select->group($this->_tableName . '.' . 'id');
        Tinebase_Backend_Sql_Abstract::traitGroup($select);
        
        $stmt = $this->_db->query($select);
        $rows = (array)$stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        $result = $this->_rawDataToRecordSet($rows);
        $clones = clone $result;
        
        Tinebase_Container::getInstance()->getGrantsOfRecords($clones, Tinebase_Core::getUser());
        Calendar_Model_Attender::resolveAttendee($clones->attendee, TRUE, $clones);
        
        $me = Tinebase_Core::getUser()->contact_id;
        $inheritableGrants = array(
            Tinebase_Model_Grants::GRANT_FREEBUSY,
            Tinebase_Model_Grants::GRANT_READ, 
            Tinebase_Model_Grants::GRANT_SYNC, 
            Tinebase_Model_Grants::GRANT_EXPORT, 
            Tinebase_Model_Grants::GRANT_PRIVATE,
        );
        $toRemove = array();
        
        foreach($result as $event) {
            $clone = $clones->getById($event->getId());
            if ($event->organizer == $me) {
                foreach($this->_recordBasedGrants as $grant) {
                    $event->{$grant}     = TRUE;
                }
            } else {
                // grants to original container
                if ($clone->container_id instanceof Tinebase_Model_Container && $clone->container_id->account_grants) {
                    foreach($this->_recordBasedGrants as $grant) {
                        $event->{$grant} =     $clone->container_id->account_grants[$grant] 
                                            || $clone->container_id->account_grants[Tinebase_Model_Grants::GRANT_ADMIN];
                    }
                }
                
                // check grant inheritance
                foreach($inheritableGrants as $grant) {
                    if (! $event->{$grant} && $clone->attendee instanceof Tinebase_Record_RecordSet) {
                        foreach($clone->attendee as $attendee) {
                            if (   $attendee->displaycontainer_id instanceof Tinebase_Model_Container
                                && $attendee->displaycontainer_id->account_grants 
                                && (    $attendee->displaycontainer_id->account_grants[$grant]
                                     || $attendee->displaycontainer_id->account_grants[Tinebase_Model_Grants::GRANT_ADMIN]
                                   )
                            ){
                                $event->{$grant} = TRUE;
                                break;
                            }
                        }
                    }
                }
                
                $requiredGrants = $grantsFilter ? $grantsFilter->getRequiredGrants() : array(
                    Tinebase_Model_Grants::GRANT_FREEBUSY,
                    Tinebase_Model_Grants::GRANT_READ,
                    Tinebase_Model_Grants::GRANT_ADMIN,
                );
                
                $requiredGrants = array_intersect($requiredGrants, $this->_recordBasedGrants);
                
                $hasGrant = FALSE;
                foreach($requiredGrants as $requiredGrant) {
                    if ($event->{$requiredGrant}) {
                        $hasGrant |= $event->{$requiredGrant};
                    }
                }
                
                if (! $hasGrant) {
                    $toRemove[] = $event;
                }
            }
        }
        
        foreach($toRemove as $event) {
            $result->removeRecord($event);
        }
        
        return $_onlyIds ? $result->{is_bool($_onlyIds) ? $this->_getRecordIdentifier() : $_onlyIds} : $result;
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
        
        $event = parent::update($_record);
        $this->_saveExdates($_record);
        //$this->_saveAttendee($_record);
        
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
                $attendeeWhere = ' AND ' . array_value(0, $whereArray);
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
            /* table  */ array('dispgrants' => $this->_tablePrefix . 'container_acl'), 
            /* on     */ $this->_db->quoteIdentifier('dispgrants.container_id') . ' = ' . $this->_db->quoteIdentifier('attendee.displaycontainer_id') . 
                           ' AND ' . $this->_getContainGrantCondition('dispgrants', 'groupmemberships'),
            /* select */ array());
        
        $_select->joinLeft(
            /* table  */ array('physgrants' => $this->_tablePrefix . 'container_acl'), 
            /* on     */ $this->_db->quoteIdentifier('physgrants.container_id') . ' = ' . $this->_db->quoteIdentifier('cal_events.container_id'),
            /* select */ array());
        
        $allGrants = Tinebase_Model_Grants::getAllGrants();
        
        foreach ($allGrants as $grant) {
            if (in_array($grant, $this->_recordBasedGrants)) {
                $_select->columns(array($grant => "\n MAX( CASE WHEN ( \n" .
                    '  /* physgrant */' . $this->_getContainGrantCondition('physgrants', 'groupmemberships', $grant) . " OR \n" . 
                    '  /* implicit  */' . $this->_getImplicitGrantCondition($grant) . " OR \n" .
                    '  /* inherited */' . $this->_getInheritedGrantCondition($grant) . " \n" .
                 ") THEN 1 ELSE 0 END ) "));
            } else {
                $_select->columns(array($grant => "\n MAX( CASE WHEN ( \n" .
                    '  /* physgrant */' . $this->_getContainGrantCondition('physgrants', 'groupmemberships', $grant) . "\n" .
                ") THEN 1 ELSE 0 END ) "));
            }
        }
    }
    
    /**
     * returns SQL with container grant condition 
     *
     * @param  string                               $_aclTableName
     * @param  string                               $_groupMembersTableName
     * @param  string|array                         $_requiredGrant (defaults none)
     * @param  Zend_Db_Expr|int|Tinebase_Model_User $_user (defaults current user)
     * @return string
     */
    protected function _getContainGrantCondition($_aclTableName, $_groupMembersTableName, $_requiredGrant=NULL, $_user=NULL )
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
        $contactId = $_user ? $user->contact_id : Tinebase_Core::getUser()->contact_id;
        
        // delte grant couldn't be gained implicitly
        if ($_requiredGrant == Tinebase_Model_Grants::GRANT_DELETE) {
            return '1=0';
        }
        
        // organizer gets all other grants implicitly
        $sql = $this->_db->quoteIdentifier('cal_events.organizer') . " = " . $this->_db->quote($contactId);
        
        // attendee get read, sync, export and private grants implicitly
        if (in_array($_requiredGrant, array(Tinebase_Model_Grants::GRANT_READ, Tinebase_Model_Grants::GRANT_SYNC, Tinebase_Model_Grants::GRANT_EXPORT, Tinebase_Model_Grants::GRANT_PRIVATE))) {
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
        $sql = $this->_getContainGrantCondition('dispgrants', 'groupmemberships', $_requiredGrant);
        
        // _AND_ attender(admin) of display calendar needs to have grant on phys calendar
        // @todo include implicit inherited grants
        if (! in_array($_requiredGrant, array(Tinebase_Model_Grants::GRANT_READ, Tinebase_Model_Grants::GRANT_FREEBUSY))) {
            $userExpr = new Zend_Db_Expr($this->_db->quoteIdentifier('attendeeaccounts.id'));
            
            $attenderPhysGrantCond = $this->_getContainGrantCondition('physgrants', 'attendeegroupmemberships', $_requiredGrant, $userExpr);
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
     * @return Tinebase_Record_Abstract
     */
    protected function _rawDataToRecord(array $_rawData) {
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
    protected function _rawDataToRecordSet(array $_rawData)
    {
        $events = new Tinebase_Record_RecordSet($this->_modelName);
        $events->addIndices(array('rrule', 'recurid'));
        
        foreach ($_rawData as $rawEvent) {
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
}
