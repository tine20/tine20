<?php
/**
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Model of an attendee
 *
 * @package Calendar
 * @property Tinebase_DateTime alarm_ack_time
 * @property Tinebase_DateTime alarm_snooze_time
 * @property string transp
 */
class Calendar_Model_Attender extends Tinebase_Record_Abstract
{
    /**
     * supported user types
     */
    const USERTYPE_USER        = 'user';
    const USERTYPE_GROUP       = 'group';
    const USERTYPE_GROUPMEMBER = 'groupmember';
    const USERTYPE_RESOURCE    = 'resource';
    const USERTYPE_LIST        = 'list';
    
    /**
     * supported roles
     */
    const ROLE_REQUIRED        = 'REQ';
    const ROLE_OPTIONAL        = 'OPT';
    
    /**
     * supported status
     */
    const STATUS_NEEDSACTION   = 'NEEDS-ACTION';
    const STATUS_ACCEPTED      = 'ACCEPTED';
    const STATUS_DECLINED      = 'DECLINED';
    const STATUS_TENTATIVE     = 'TENTATIVE';
    
    /**
     * cache for already resolved attendee
     * 
     * @var array type => array of id => object
     */
    protected static $_resovedAttendeeCache = array();
    
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Calendar';
    
    /**
     * validators
     *
     * @var array
     */
    protected $_validators = array(
        // tine record fields
        'id'                   => array('allowEmpty' => true,  'Alnum'),
        /*
        'created_by'           => array('allowEmpty' => true,  'Int'  ),
        'creation_time'        => array('allowEmpty' => true          ),
        'last_modified_by'     => array('allowEmpty' => true          ),
        'last_modified_time'   => array('allowEmpty' => true          ),
        'is_deleted'           => array('allowEmpty' => true          ),
        'deleted_time'         => array('allowEmpty' => true          ),
        'deleted_by'           => array('allowEmpty' => true          ),
        'seq'                  => array('allowEmpty' => true,  'Int'  ),
        */
        
        'cal_event_id'         => array('allowEmpty' => true/*,  'Alnum'*/),
        'user_id'              => array('allowEmpty' => false,        ),
        'user_type'            => array('allowEmpty' => true,  'InArray' => array(self::USERTYPE_USER, self::USERTYPE_GROUP, self::USERTYPE_GROUPMEMBER, self::USERTYPE_RESOURCE)),
        'role'                 => array('allowEmpty' => true,  'InArray' => array(self::ROLE_OPTIONAL, self::ROLE_REQUIRED)),
        'quantity'             => array('allowEmpty' => true, 'Int'   ),
        'status'               => array('allowEmpty' => true,  'InArray' => array(self::STATUS_NEEDSACTION, self::STATUS_TENTATIVE, self::STATUS_ACCEPTED, self::STATUS_DECLINED)),
        'status_authkey'       => array('allowEmpty' => true, 'Alnum' ),
        'displaycontainer_id'  => array('allowEmpty' => true, 'Int'   ),
        'alarm_ack_time'       => array('allowEmpty' => true),
    	'alarm_snooze_time'    => array('allowEmpty' => true),
    	'transp'               => array('allowEmpty' => true,  'InArray' => array(Calendar_Model_Event::TRANSP_TRANSP, Calendar_Model_Event::TRANSP_OPAQUE)),
    );
    
    /**
     * datetime fields
     *
     * @var array
     */
    protected $_datetimeFields = array(
        'alarm_ack_time',
    	'alarm_snoze_time'
    );
    
    /**
     * returns accountId of this attender if present
     * 
     * @return string
     */
    public function getUserAccountId()
    {
        if (! in_array($this->user_type, array(self::USERTYPE_USER, self::USERTYPE_GROUPMEMBER))) {
            return NULL;
        }
        
        try {
	        $contact = Addressbook_Controller_Contact::getInstance()->get($this->user_id);
	        return $contact->account_id ? $contact->account_id : NULL;
        } catch (Exception $e) {
        	return NULL;
        }
    }
    
    /**
     * get email of attender if exists
     * 
     * @return string
     */
    public function getEmail()
    {
        $resolvedUser = $this->getResolvedUser();
        if (! $resolvedUser instanceof Tinebase_Record_Abstract) {
            return '';
        }
        
        switch ($this->user_type) {
            case self::USERTYPE_USER:
            case self::USERTYPE_GROUPMEMBER:
                return $resolvedUser->getPreferedEmailAddress();
                break;
            case self::USERTYPE_GROUP:
                return $resolvedUser->getId();
                break;
            case self::USERTYPE_RESOURCE:
                return $resolvedUser->email;
                break;
            default:
                throw new Exception("type $type not yet supported");
                break;
        }
    }
    
    /**
     * get name of attender
     * 
     * @return string
     */
    public function getName()
    {
        $resolvedUser = $this->getResolvedUser();
        if (! $resolvedUser instanceof Tinebase_Record_Abstract) {
            Tinebase_Translation::getTranslation('Calendar');
            return Tinebase_Translation::getTranslation('Calendar')->_('unknown');
        }
        
        switch ($this->user_type) {
            case self::USERTYPE_USER:
            case self::USERTYPE_GROUPMEMBER:
                return $resolvedUser->n_fileas;
                break;
            case self::USERTYPE_GROUP:
            case self::USERTYPE_RESOURCE:
                return $resolvedUser->name;
                break;
            default:
                throw new Exception("type $type not yet supported");
                break;
        }
    }
    
    /**
     * returns the resolved user_id
     * 
     * @return Tinebase_Record_Abstract
     */
    public function getResolvedUser()
    {
        $clone = clone $this;
        $resolvable = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array($clone));
        self::resolveAttendee($resolvable);
        
        return $clone->user_id;
    }
    
    public function getStatusString()
    {
        $statusConfig = Calendar_Config::getInstance()->attendeeStatus;
        $statusRecord = $statusConfig && $statusConfig->records instanceof Tinebase_Record_RecordSet ? $statusConfig->records->getById($this->status) : false;
        
        return $statusRecord ? $statusRecord->value : $this->status;
    }
    
    public function getRoleString()
    {
        $rolesConfig = Calendar_Config::getInstance()->attendeeRoles;
        $rolesRecord = $rolesConfig && $rolesConfig->records instanceof Tinebase_Record_RecordSet ? $rolesConfig->records->getById($this->role) : false;
        
        return $rolesRecord? $rolesRecord->value : $this->role;
    }
    
    /**
     * sets the record related properties from user generated input.
     * 
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data            the new data to set
     * @throws Tinebase_Exception_Record_Validation when content contains invalid or missing data
     */
    public function setFromArray(array $_data)
    {
        if (isset($_data['displaycontainer_id']) && is_array($_data['displaycontainer_id'])) {
            $_data['displaycontainer_id'] = $_data['displaycontainer_id']['id'];
        }
        
        if (isset($_data['user_id']) && is_array($_data['user_id'])) {
            if (array_key_exists('accountId', $_data['user_id'])) {
            	// NOTE: we need to support accounts, cause the client might not have the contact, e.g. when the attender is generated from a container owner
                $_data['user_id'] = Addressbook_Controller_Contact::getInstance()->getContactByUserId($_data['user_id']['accountId'], TRUE)->getId();
            } elseif (array_key_exists('group_id', $_data['user_id'])) {
                $_data['user_id'] = is_array($_data['user_id']['group_id']) ? $_data['user_id']['group_id'][0] : $_data['user_id']['group_id'];
            } else if (array_key_exists('id', $_data['user_id'])) {
                $_data['user_id'] = $_data['user_id']['id'];
            }
        }
        
        parent::setFromArray($_data);
    }
    
    /**
     * converts an array of emails to a recordSet of attendee for given record
     * 
     * @param  Calendar_Model_Event $_event
     * @param  iteratable           $_emails
     * @param  bool                 $_ImplicitAddMissingContacts
     * @return Tinebase_Record_RecordSet
     */
    public static function emailsToAttendee(Calendar_Model_Event $_event, $_emails, $_ImplicitAddMissingContacts = TRUE)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " list of new attendees " . print_r($_emails, true));
        
        if (! $_event->attendee instanceof Tinebase_Record_RecordSet) {
            $_event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        }
                
        // resolve current attendee
        self::resolveAttendee($_event->attendee);
        
        // build currentMailMap
        // NOTE: non resolvable attendee will be discarded in the map
        //       this is _important_ for the calculation of migration as it
        //       saves us from deleting attendee out of current users scope
        $emailsOfCurrentAttendees = array();
        foreach ($_event->attendee as $currentAttendee) {
        	if ($currentAttendeeEmailAddress = $currentAttendee->getEmail()) {
        	    $emailsOfCurrentAttendees[$currentAttendeeEmailAddress] = $currentAttendee;
        	}
        }
        
        // collect emails of new attendees
        $emailsOfNewAttendees = array();
        foreach ($_emails as $newAttendee) {
            $emailsOfNewAttendees[$newAttendee['email']] = $newAttendee;
        }
        
        // attendees to remove
        $attendeesToDelete = array_diff_key($emailsOfCurrentAttendees, $emailsOfNewAttendees);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " attendees to delete " . print_r(array_keys($attendeesToDelete), true));
        
        // delete attendees no longer attending from recordset
        foreach ($attendeesToDelete as $attendeeToDelete) {
            $_event->attendee->removeRecord($attendeeToDelete);
        }
        
        
        // attendees to keep and update
        $attendeesToKeep   = array_diff_key($emailsOfCurrentAttendees, $attendeesToDelete);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " attendees to keep " . print_r(array_keys($attendeesToKeep), true));
        //var_dump($attendeesToKeep);
        foreach($attendeesToKeep as $emailAddress => $attendeeToKeep) {
            $newSettings = $emailsOfNewAttendees[$emailAddress];

            // update object by reference
            $attendeeToKeep->status = isset($newSettings['partStat']) ? $newSettings['partStat'] : $attendeeToKeep->status;
            $attendeeToKeep->role   = $newSettings['role'];
        }
        

        // new attendess to add to event
        $attendeesToAdd    = array_diff_key($emailsOfNewAttendees,     $emailsOfCurrentAttendees);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " attendees to add " . print_r(array_keys($attendeesToAdd), true));
        
        // add attendee identified by their emailAdress
        foreach ($attendeesToAdd as $newAttendee) {
            $attendeeId = NULL;
            
            if ($newAttendee['userType'] == Calendar_Model_Attender::USERTYPE_USER) {
            	$contacts = Addressbook_Controller_Contact::getInstance()->search(new Addressbook_Model_ContactFilter(array(
            	    array('field' => 'containerType', 'operator' => 'equals', 'value' => 'all'),
                    array('condition' => 'OR', 'filters' => array(
                        array('field' => 'email',      'operator'  => 'equals', 'value' => $newAttendee['email']),
                        array('field' => 'email_home', 'operator'  => 'equals', 'value' => $newAttendee['email'])
                    )),
            	)));
                
            	if(count($contacts) > 0) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found # of contacts " . count($contacts));
    
                    $attendeeId = $contacts->getFirstRecord()->getId();
                    
                } else if ($_ImplicitAddMissingContacts == true) {
                	$translation = Tinebase_Translation::getTranslation('Calendar');
                	$i18nNote = $translation->_('This contact has been automatically added by the system as an event attender');
                    $contactData = array(
                        'note'        => $i18nNote,
                        'email'       => $newAttendee['email'],
                        'n_family'    => $newAttendee['lastName'],
                        'n_given'     => $newAttendee['firstName'],
                    );
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " add new contact " . print_r($contactData, true));
                    $contact = new Addressbook_Model_Contact($contactData);
                    
                    $attendeeId = Addressbook_Controller_Contact::getInstance()->create($contact, FALSE)->getId();
                }
    
            } else if($newAttendee['userType'] == Calendar_Model_Attender::USERTYPE_GROUP) {
                $lists = Addressbook_Controller_List::getInstance()->search(new Addressbook_Model_ListFilter(array(
                    array('field' => 'containerType', 'operator' => 'equals', 'value' => 'all'),
                    array('field' => 'name', 'operator' => 'equals', 'value' => $newAttendee['displayName']),
                    array('field' => 'type', 'operator' => 'equals', 'value' => Addressbook_Model_List::LISTTYPE_GROUP)
                )));
                
                if(count($lists) > 0) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found # of lists " . count($lists));
                
                    $attendeeId = $lists->getFirstRecord()->group_id;
                }
            }
        	
            if ($attendeeId !== NULL) {
                // finally add to attendee
                $_event->attendee->addRecord(new Calendar_Model_Attender(array(
                    'user_id'   => $attendeeId,
                    'user_type' => $newAttendee['userType'],
                    'status'    => isset($newAttendee['partStat']) ? $newAttendee['partStat'] : self::STATUS_NEEDSACTION,
                    'role'      => $newAttendee['role']
                )));
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " updated attendees list " . print_r($_event->attendee->toArray(), true));
    }
    
    /**
     * resolves group members and adds/removes them if nesesary
     * 
     * NOTE: If a user is listed as user and as groupmember, we supress the groupmember
     * 
     * NOTE: The role to assign to a new group member is not always clear, as multiple groups
     *       might be the 'source' of the group member. To deal with this, we take the role of
     *       the first group when we add new group members
     *       
     * @param Tinebase_Record_RecordSet $_attendee
     * @return void
     */
    public static function resolveGroupMembers($_attendee)
    {
        if (! $_attendee instanceof Tinebase_Record_RecordSet) {
            return;
        }
        
        $groupAttendee = $_attendee->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUP);
        
        $allCurrGroupMembers = $_attendee->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER);
        $allCurrGroupMembersContactIds = $allCurrGroupMembers->user_id;
        
        $allGroupMembersContactIds = array();
        foreach ($groupAttendee as $groupAttender) {
            #$groupAttenderMemberIds = Tinebase_Group::getInstance()->getGroupMembers($groupAttender->user_id);
            #$groupAttenderContactIds = Tinebase_User::getInstance()->getMultiple($groupAttenderMemberIds)->contact_id;
            #$allGroupMembersContactIds = array_merge($allGroupMembersContactIds, $groupAttenderContactIds);
            
            $listId = null;
        
            if ($groupAttender->user_id instanceof Addressbook_Model_List) {
                $listId = $groupAttender->user_id->getId();
            } else {
                $group = Tinebase_Group::getInstance()->getGroupById($groupAttender->user_id);
                if (!empty($group->list_id)) {
                    $listId = $group->list_id;
                }
            }
            
            if ($listId !== null) {
                $groupAttenderContactIds = Addressbook_Controller_List::getInstance()->get($listId)->members;
                $allGroupMembersContactIds = array_merge($allGroupMembersContactIds, $groupAttenderContactIds);
                
                $toAdd = array_diff($groupAttenderContactIds, $allCurrGroupMembersContactIds);
                
                foreach($toAdd as $userId) {
                    $_attendee->addRecord(new Calendar_Model_Attender(array(
                        'user_type' => Calendar_Model_Attender::USERTYPE_GROUPMEMBER,
                        'user_id'   => $userId,
                        'role'      => $groupAttender->role
                    )));
                }
            }
        }
        
        $toDel = array_diff($allCurrGroupMembersContactIds, $allGroupMembersContactIds);
        foreach ($toDel as $idx => $contactId) {
            $attender = $allCurrGroupMembers->find('user_id', $contactId);
            $_attendee->removeRecord($attender);
        }
        
        // calculate double members (groupmember + user)
        $groupmembers = $_attendee->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER);
        $users        = $_attendee->filter('user_type', Calendar_Model_Attender::USERTYPE_USER);
        $doublicates = array_intersect($users->user_id, $groupmembers->user_id);
        foreach ($doublicates as $user_id) {
            $attender = $groupmembers->find('user_id', $user_id);
            $_attendee->removeRecord($attender);
        }
    }
    
    /**
     * get own attender
     * 
     * @param Tinebase_Record_RecordSet $_attendee
     * @return Calendar_Model_Attender|NULL
     */
    public static function getOwnAttender($_attendee)
    {
        return self::getAttendee($_attendee, new Calendar_Model_Attender(array(
            'user_id'   => Tinebase_Core::getUser()->contact_id,
            'user_type' => Calendar_Model_Attender::USERTYPE_USER
        )));
    }
    
    /**
     * get a single attendee from set of attendee
     * 
     * @param Tinebase_Record_RecordSet $_attendee
     * @return Calendar_Model_Attender|NULL
     */
    public static function getAttendee($_attendeeSet, $_attendee)
    {
        $attendeeSet  = $_attendeeSet instanceof Tinebase_Record_RecordSet ? clone $_attendeeSet : new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        
        // transform id to string
        foreach($attendeeSet as $attendee) {
            $attendee->user_id  = $attendee->user_id instanceof Tinebase_Record_Abstract ? $attendee->user_id->getId() : $attendee->user_id;
        }
        
        $attendeeUserId = $_attendee->user_id instanceof Tinebase_Record_Abstract ? $_attendee->user_id->getId() : $_attendee->user_id;
        
        $foundAttendee = $attendeeSet
            ->filter('user_type', $_attendee->user_type)
            ->filter('user_id', $attendeeUserId)
            ->getFirstRecord();
        
        // search for groupmember if no user got found
        if ($foundAttendee === null && $_attendee->user_type == Calendar_Model_Attender::USERTYPE_USER) {
            $foundAttendee = $attendeeSet
                ->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER)
                ->filter('user_id', $attendeeUserId)
                ->getFirstRecord();
        }
            
        return $foundAttendee ? $_attendeeSet[$attendeeSet->indexOf($foundAttendee)] : NULL;
        
    }
    
    /**
     * resolves given attendee for json representation
     *
     * @param Tinebase_Record_RecordSet|array   $_eventAttendee 
     * @param bool                              $_resolveDisplayContainers
     */
    public static function resolveAttendee($_eventAttendee, $_resolveDisplayContainers = TRUE) {
        $eventAttendee = $_eventAttendee instanceof Tinebase_Record_RecordSet ? array($_eventAttendee) : $_eventAttendee;
        
        // set containing all attendee
        $allAttendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        $typeMap = array();
        
        // build type map 
        foreach ($eventAttendee as $attendee) {
            foreach ($attendee as $attender) {
                $allAttendee->addRecord($attender);
            
                if ($attender->user_id instanceof Tinebase_Record_Abstract) {
                    // already resolved
                    continue;
                } elseif (array_key_exists($attender->user_type, self::$_resovedAttendeeCache) && array_key_exists($attender->user_id, self::$_resovedAttendeeCache[$attender->user_type])){
                    // already in cache
                    $attender->user_id = self::$_resovedAttendeeCache[$attender->user_type][$attender->user_id];
                } else {
                    if (! array_key_exists($attender->user_type, $typeMap)) {
                        $typeMap[$attender->user_type] = array();
                    }
                    $typeMap[$attender->user_type][] = $attender->user_id;
                }
            }
        }
        
        // resolve display containers
        if ($_resolveDisplayContainers) {
            $displaycontainerIds = array_diff($allAttendee->displaycontainer_id, array(''));
            if (! empty($displaycontainerIds)) {
                Tinebase_Container::getInstance()->getGrantsOfRecords($allAttendee, Tinebase_Core::getUser(), 'displaycontainer_id');
            }
        }
        
        // get all user_id entries
        foreach ($typeMap as $type => $ids) {
            switch ($type) {
                case self::USERTYPE_USER:
                case self::USERTYPE_GROUPMEMBER:
                    //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(print_r(array_unique($ids), true));
                    $typeMap[$type] = Addressbook_Controller_Contact::getInstance()->getMultiple(array_unique($ids), TRUE);
                    break;
                case self::USERTYPE_GROUP:
                case Calendar_Model_AttenderFilter::USERTYPE_MEMBEROF:
                    // first fetch the groups, then the lists identified by list_id
                    $typeMap[$type] = Tinebase_Group::getInstance()->getMultiple(array_unique($ids));
                    $typeMap[self::USERTYPE_LIST] = Addressbook_Controller_List::getInstance()->getMultiple($typeMap[$type]->list_id, true);
                	break;
                case self::USERTYPE_RESOURCE:
                	$typeMap[$type] = Calendar_Controller_Resource::getInstance()->getMultiple(array_unique($ids));
                    break;
                default:
                    throw new Exception("type $type not supported");
                    break;
            }
        }
        
        // sort entries in
        foreach ($eventAttendee as $attendee) {
            foreach ($attendee as $attender) {
                if ($attender->user_id instanceof Tinebase_Record_Abstract) {
                    // allready resolved from cache
                    continue;
                }

                $idx = false;
                
                if ($attender->user_type == self::USERTYPE_GROUP) {
                    $attendeeTypeSet = $typeMap[$attender->user_type];
                    $idx = $attendeeTypeSet->getIndexById($attender->user_id);
                    if ($idx !== false) {
                        $group = $attendeeTypeSet[$idx];
                        
                        $idx = false;
                        
                        $attendeeTypeSet = $typeMap[self::USERTYPE_LIST];
                        $idx = $attendeeTypeSet->getIndexById($group->list_id);
                    } 
                } else {
                    $attendeeTypeSet = $typeMap[$attender->user_type];
                    $idx = $attendeeTypeSet->getIndexById($attender->user_id);
                }
                if ($idx !== false) {
                    // copy to cache
                    if (! array_key_exists($attender->user_type, self::$_resovedAttendeeCache)) {
                        self::$_resovedAttendeeCache[$attender->user_type] = array();
                    }
                    self::$_resovedAttendeeCache[$attender->user_type][$attender->user_id] = $attendeeTypeSet[$idx];
                    
                    $attender->user_id = $attendeeTypeSet[$idx];
                }
            }
        }
        
        
        foreach ($eventAttendee as $attendee) {
            foreach ($attendee as $attender) {
                // keep authkey if user has editGrant to displaycontainer
                if (isset($attender['displaycontainer_id']) && !is_scalar($attender['displaycontainer_id']) && array_key_exists(Tinebase_Model_Grants::GRANT_EDIT, $attender['displaycontainer_id']['account_grants']) &&  $attender['displaycontainer_id']['account_grants'][Tinebase_Model_Grants::GRANT_EDIT]) {
                    continue;
                }
                
                // keep authkey if attender is not an account and user has editGrant for event
                if ($attender->user_id instanceof Tinebase_Record_Abstract && (!$attender->user_id->has('account_id') || !$attender->user_id->account_id)) {
                    continue;
                }
                
                $attender->status_authkey = NULL;
            }
        }
    }
}
