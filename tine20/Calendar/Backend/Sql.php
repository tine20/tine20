<?php
/**
 * Sql Calendar 
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Native tine 2.0 events sql backend
 *
 * Events consists of the properties of Calendar_Model_Event except Tags and Notes 
 * which are as always handles by their controllers/backends
 * 
 * 
 * @package Calendar 
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
        Tinebase_Model_Grants::GRANT_READ, 
        Tinebase_Model_Grants::GRANT_EDIT, 
        Tinebase_Model_Grants::GRANT_DELETE, 
        Tinebase_Model_Grants::GRANT_PRIVATE
    );
    
    /**
     * the constructor
     *
     * @param Zend_Db_Adapter_Abstract $_db optional
     * @param string $_modelName
     * @param string $_tableName
     * @param string $_tablePrefix
     *
     */
    public function __construct ($_dbAdapter = NULL, $_modelName = NULL, $_tableName = NULL, $_tablePrefix = NULL)
    {
        parent::__construct($_dbAdapter, $_modelName, $_tableName, $_tablePrefix);
        
        $this->_attendeeBackend = new Calendar_Backend_Sql_Attendee($_dbAdapter);
    }
    
    /**
     * Creates new entry
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_InvalidArgument
     * @throws  Tinebase_Exception_UnexpectedValue
     * 
     * @todo    remove autoincremental ids later
     */
    public function create(Tinebase_Record_Interface $_record) 
    {
        
        $this->_setRruleUntil($_record);
        if ($_record->rrule) {
            $_record->rrule = (string) $_record->rrule;
        }
        $_record->recurid = !empty($_record->recurid) ? $_record->recurid : NULL;
        
        $event = parent::create($_record);
        $this->_saveExdates($_record);
        //$this->_saveAttendee($_record);
        
        return $this->get($event->getId());
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
        $this->_setRruleUntil($_record);
        if ($_record->rrule) {
            $_record->rrule = (string) $_record->rrule;
        }
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
        $select = parent::_getSelect($_cols, $_getDeleted);
        
        $this->_appendEffectiveGrantCalculationSql($select);
        
        $select->joinLeft(
            /* table  */ array('exdate' => $this->_tablePrefix . 'cal_exdate'), 
            /* on     */ $this->_db->quoteIdentifier('exdate.cal_event_id') . ' = ' . $this->_db->quoteIdentifier($this->_tableName . '.id'),
            /* select */ array('exdate' => 'GROUP_CONCAT( DISTINCT ' . $this->_db->quoteIdentifier('exdate.exdate') . ')'));
        
        /* do we realy need all cols to group?
         * id should be enough, as spechial chars could break grouping
        $groupByCols = array();
        foreach(array_keys($this->_schema) as $col) {
            $groupByCols[] = $this->_tableName . '.' . $col;
        }
        */
        $select->group($this->_tableName . '.' . 'id');
        
        return $select;
    }
    
    /**
     * appends effective grant calculation to select object
     *
     * @param Zend_Db_Select $_select
     */
    protected function _appendEffectiveGrantCalculationSql($_select)
    {
        $_select->joinLeft(
            /* table  */ array('groupmemberships' => $this->_tablePrefix . 'group_members'), 
            /* on     */ $this->_db->quoteInto($this->_db->quoteIdentifier('groupmemberships.account_id') . ' = ?' , Tinebase_Core::getUser()->getId()),
            /* select */ array());
        
        $_select->joinLeft(
            /* table  */ array('attendee' => $this->_tablePrefix . 'cal_attendee'),
            /* on     */ $this->_db->quoteIdentifier('attendee.cal_event_id') . ' = ' . $this->_db->quoteIdentifier('cal_events.id'),
            /* select */ array());
        
        $_select->joinLeft(
            /* table  */ array('attendeecontacts' => $this->_tablePrefix . 'addressbook'), 
            /* on     */ $this->_db->quoteIdentifier('attendeecontacts.id') . ' = ' . $this->_db->quoteIdentifier('attendee.user_id') . 
                            ' AND ' . $this->_db->quoteInto($this->_db->quoteIdentifier('attendee.user_type') . '= ?', Calendar_Model_Attender::USERTYPE_USER),
            /* select */ array());
        
        $_select->joinLeft(
            /* table  */ array('attendeegroupmemberships' => $this->_tablePrefix . 'group_members'), 
            /* on     */ $this->_db->quoteIdentifier('attendeegroupmemberships.account_id') . ' = ' . $this->_db->quoteIdentifier('attendeecontacts.account_id'),
            /* select */ array());
        
        $_select->joinLeft(
            /* table  */ array('dispgrants' => $this->_tablePrefix . 'container_acl'), 
            /* on     */ $this->_db->quoteIdentifier('dispgrants.container_id') . ' = ' . $this->_db->quoteIdentifier('attendee.displaycontainer_id') . 
                           ' AND ' . $this->_getContainGrantCondition('dispgrants', 'groupmemberships'),
            /* select */ array());
                
        $_select->joinLeft(
            /* table  */ array('physgrants' => $this->_tablePrefix . 'container_acl'), 
            /* on     */ $this->_db->quoteIdentifier('physgrants.container_id') . ' = ' . $this->_db->quoteIdentifier('cal_events.container_id'),
            /* select */ array(
                Tinebase_Model_Grants::GRANT_READ => "\n MAX( \n" .
                    '  /* physgrant */' . $this->_getContainGrantCondition('physgrants', 'groupmemberships', Tinebase_Model_Grants::GRANT_READ) . " OR \n" . 
                    '  /* implicit  */' . $this->_getImplicitGrantCondition(Tinebase_Model_Grants::GRANT_READ) . " OR \n" .
                    '  /* inherited */' . $this->_getInheritedGrantCondition(Tinebase_Model_Grants::GRANT_READ) . " \n" .
                 ")",
                Tinebase_Model_Grants::GRANT_EDIT => "\n MAX( \n" .
                    '  /* physgrant */' . $this->_getContainGrantCondition('physgrants', 'groupmemberships', Tinebase_Model_Grants::GRANT_EDIT) . " OR \n" . 
                    '  /* implicit  */' . $this->_getImplicitGrantCondition(Tinebase_Model_Grants::GRANT_EDIT) . " OR \n" .
                    '  /* inherited */' . $this->_getInheritedGrantCondition(Tinebase_Model_Grants::GRANT_EDIT) . " \n" .
                 ")",
                Tinebase_Model_Grants::GRANT_DELETE => "\n MAX( \n" .
                    '  /* physgrant */' . $this->_getContainGrantCondition('physgrants', 'groupmemberships', Tinebase_Model_Grants::GRANT_DELETE) . " OR \n" . 
                    '  /* implicit  */' . $this->_getImplicitGrantCondition(Tinebase_Model_Grants::GRANT_DELETE) . " OR \n" .
                    '  /* inherited */' . $this->_getInheritedGrantCondition(Tinebase_Model_Grants::GRANT_DELETE) . " \n" .
                 ")",
                Tinebase_Model_Grants::GRANT_PRIVATE => "\n MAX( \n" .
                    '  /* physgrant */' . $this->_getContainGrantCondition('physgrants', 'groupmemberships', Tinebase_Model_Grants::GRANT_PRIVATE) . " OR \n" . 
                    '  /* implicit  */' . $this->_getImplicitGrantCondition(Tinebase_Model_Grants::GRANT_PRIVATE) . " OR \n" .
                    '  /* inherited */' . $this->_getInheritedGrantCondition(Tinebase_Model_Grants::GRANT_PRIVATE) . " \n" .
                 ")",
            ));
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
        
        // attendee get read and private grants implicitly
        if (in_array($_requiredGrant, array(Tinebase_Model_Grants::GRANT_READ, Tinebase_Model_Grants::GRANT_PRIVATE))) {
            $readCond = $this->_db->quoteInto($this->_db->quoteIdentifier('attendee.user_type') . ' = ?', Calendar_Model_Attender::USERTYPE_USER) . 
                   ' AND ' .  $this->_db->quoteIdentifier('attendeecontacts.account_id') . ' = ' . $this->_db->quote($accountId);
            
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
        if ($_requiredGrant != Tinebase_Model_Grants::GRANT_READ) {
            $userExpr = new Zend_Db_Expr($this->_db->quoteIdentifier('attendeecontacts.account_id'));
            
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
                $this->_db->insert($this->_tablePrefix . 'cal_exdate', array(
                    'id'           => $_event->generateUID(),
                    'cal_event_id' => $_event->getId(),
                    'exdate'       => $exdate->get(Tinebase_Record_Abstract::ISO8601LONG)
                ));
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
    
    /**
     * sets rrule until field in event model
     *
     * @param  Calendar_Model_Event $_event
     * @return void
     */
    protected function _setRruleUntil(Calendar_Model_Event $_event)
    {
        if (empty($_event->rrule)) {
            $_event->rrule_until = NULL;
        } else {
            $rrule = $_event->rrule;
            if (! $_event->rrule instanceof Calendar_Model_Rrule) {
                $rrule = new Calendar_Model_Rrule(array());
                $rrule->setFromString($_event->rrule);
            }
            
            $_event->rrule_until = $rrule->until;
        }
    }
    
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