<?php
/**
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
    protected static $_resolvedAttendeesCache = array(
        self::USERTYPE_USER        => array(),
        self::USERTYPE_GROUPMEMBER => array(),
        self::USERTYPE_GROUP       => array(),
        self::USERTYPE_LIST        => array(),
        self::USERTYPE_RESOURCE    => array(),
        Calendar_Model_AttenderFilter::USERTYPE_MEMBEROF => array()
    );
    
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
        'created_by'           => array('allowEmpty' => true          ),
        'creation_time'        => array('allowEmpty' => true          ),
        'last_modified_by'     => array('allowEmpty' => true          ),
        'last_modified_time'   => array('allowEmpty' => true          ),
        'is_deleted'           => array('allowEmpty' => true          ),
        'deleted_time'         => array('allowEmpty' => true          ),
        'deleted_by'           => array('allowEmpty' => true          ),
        'seq'                  => array('allowEmpty' => true,  'Int'  ),
        
        'cal_event_id'         => array('allowEmpty' => true/*,  'Alnum'*/),
        'user_id'              => array('allowEmpty' => false,        ),
        'user_type'            => array(
            'allowEmpty' => true,
            array('InArray', array(self::USERTYPE_USER, self::USERTYPE_GROUP, self::USERTYPE_GROUPMEMBER, self::USERTYPE_RESOURCE))
        ),
        'role'                 => array('allowEmpty' => true          ),
        'quantity'             => array('allowEmpty' => true, 'Int'   ),
        'status'               => array('allowEmpty' => true          ),
        'status_authkey'       => array('allowEmpty' => true, 'Alnum' ),
        'displaycontainer_id'  => array('allowEmpty' => true, 'Int'   ),
        'transp'               => array(
            'allowEmpty' => true,
            array('InArray', array(Calendar_Model_Event::TRANSP_TRANSP, Calendar_Model_Event::TRANSP_OPAQUE))
        ),
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
            $contact = Addressbook_Controller_Contact::getInstance()->get($this->user_id, null, false);
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
        
        if ($this->user_type === self::USERTYPE_RESOURCE) {
            $resource = $clone->user_id;
            // return pseudo contact with resource data
            $result = new Addressbook_Model_Contact(array(
                'n_family'  => $resource->name,
                'email'     => $resource->email,
                'id'        => $resource->getId(),
            ));
        } else {
            $result = $clone->user_id;
        }
        
        return $result;
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
     * returns if given attendee is the same as this attendee
     *
     * @param  Calendar_Model_Attender $compareTo
     * @return bool
     */
    public function isSame($compareTo)
    {
        $compareToSet = new Tinebase_Record_RecordSet('Calendar_Model_Attender', array($compareTo));
        return !!self::getAttendee($compareToSet, $this);
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
            if ((isset($_data['user_id']['accountId']) || array_key_exists('accountId', $_data['user_id']))) {
                // NOTE: we need to support accounts, cause the client might not have the contact, e.g. when the attender is generated from a container owner
                $_data['user_id'] = Addressbook_Controller_Contact::getInstance()->getContactByUserId($_data['user_id']['accountId'], TRUE)->getId();
            } elseif ((isset($_data['user_id']['group_id']) || array_key_exists('group_id', $_data['user_id']))) {
                $_data['user_id'] = is_array($_data['user_id']['group_id']) ? $_data['user_id']['group_id'][0] : $_data['user_id']['group_id'];
            } else if ((isset($_data['user_id']['id']) || array_key_exists('id', $_data['user_id']))) {
                $_data['user_id'] = $_data['user_id']['id'];
            }
        }
        
        if (empty($_data['quantity'])) {
            $_data['quantity'] = 1;
        }
        
        parent::setFromArray($_data);
    }
    
    /**
     * converts an array of emails to a recordSet of attendee for given record
     * 
     * @param  Calendar_Model_Event $_event
     * @param  iteratable           $_emails
     * @param  bool                 $_implicitAddMissingContacts
     */
    public static function emailsToAttendee(Calendar_Model_Event $_event, $_emails, $_implicitAddMissingContacts = TRUE)
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
        
        // collect emails of new attendees (skipping if no email present)
        $emailsOfNewAttendees = array();
        foreach ($_emails as $newAttendee) {
            if ($newAttendee['email']) {
                $emailsOfNewAttendees[$newAttendee['email']] = $newAttendee;
            }
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
        
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Model_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        
        // add attendee identified by their emailAdress
        foreach ($attendeesToAdd as $newAttendee) {
            $attendeeId = NULL;
            
            if ($newAttendee['userType'] == Calendar_Model_Attender::USERTYPE_USER) {
                // does a contact with this email address exist?
                if ($contact = self::resolveEmailToContact($newAttendee, false)) {
                    $attendeeId = $contact->getId();
                    
                }
                
                // does a resouce with this email address exist?
                if ( ! $attendeeId) {
                    $resources = Calendar_Controller_Resource::getInstance()->search(new Calendar_Model_ResourceFilter(array(
                        array('field' => 'email', 'operator' => 'equals', 'value' => $newAttendee['email']),
                    )));
                    
                    if(count($resources) > 0) {
                        $newAttendee['userType'] = Calendar_Model_Attender::USERTYPE_RESOURCE;
                        $attendeeId = $resources->getFirstRecord()->getId();
                    }
                }
                // does a list with this name exist?
                if ( ! $attendeeId &&
                    isset($smtpConfig['primarydomain']) && 
                    preg_match('/(?P<localName>.*)@' . preg_quote($smtpConfig['primarydomain']) . '$/', $newAttendee['email'], $matches)
                ) {
                    $lists = Addressbook_Controller_List::getInstance()->search(new Addressbook_Model_ListFilter(array(
                        array('field' => 'name',       'operator' => 'equals', 'value' => $matches['localName']),
                        array('field' => 'type',       'operator' => 'equals', 'value' => Addressbook_Model_List::LISTTYPE_GROUP),
                        array('field' => 'showHidden', 'operator' => 'equals', 'value' => TRUE),
                    )));
                    
                    if(count($lists) > 0) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found # of lists " . count($lists));
                    
                        $newAttendee['userType'] = Calendar_Model_Attender::USERTYPE_GROUP;
                        $attendeeId = $lists->getFirstRecord()->group_id;
                    }
                } 
                
                if (! $attendeeId) {
                    // autocreate a contact if allowed
                    $contact = self::resolveEmailToContact($newAttendee, $_implicitAddMissingContacts);
                    if ($contact) {
                        $attendeeId = $contact->getId();
                    }
                }
            } else if($newAttendee['userType'] == Calendar_Model_Attender::USERTYPE_GROUP) {
                $lists = Addressbook_Controller_List::getInstance()->search(new Addressbook_Model_ListFilter(array(
                    array('field' => 'name',       'operator' => 'equals', 'value' => $newAttendee['displayName']),
                    array('field' => 'type',       'operator' => 'equals', 'value' => Addressbook_Model_List::LISTTYPE_GROUP),
                    array('field' => 'showHidden', 'operator' => 'equals', 'value' => TRUE),
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
     * get attendee with user_id = email address and create contacts for them on the fly if they do not exist
     * 
     * @param Calendar_Model_Event $_event
     * @throws Tinebase_Exception_InvalidArgument
     */
    public static function resolveEmailOnlyAttendee(Calendar_Model_Event $_event)
    {
        if (! $_event->attendee instanceof Tinebase_Record_RecordSet) {
            $_event->attendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        }
        
        foreach ($_event->attendee as $currentAttendee) {
            if (is_string($currentAttendee->user_id) && preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $currentAttendee->user_id)) {
                if ($currentAttendee->user_type !== Calendar_Model_Attender::USERTYPE_USER) {
                    throw new Tinebase_Exception_InvalidArgument('it is only allowed to set contacts as email only attender');
                }
                $contact = self::resolveEmailToContact(array(
                    'email'     => $currentAttendee->user_id,
                ));
                $currentAttendee->user_id = $contact->getId();
            }
        }
    }
    
   /**
    * check if contact with given email exists in addressbook and creates it if not
    *
    * @param  array $_attenderData array with email, firstname and lastname (if available)
    * @param  boolean $_implicitAddMissingContacts
    * @return Addressbook_Model_Contact
    * 
    * @todo filter by fn if multiple matches
    */
    public static function resolveEmailToContact($_attenderData, $_implicitAddMissingContacts = TRUE)
    {
        if (! isset($_attenderData['email']) || empty($_attenderData['email'])) {
            throw new Tinebase_Exception_InvalidArgument('email address is needed to resolve contact');
        }
        
        $email = self::_sanitizeEmail($_attenderData['email']);
        
        $contacts = Addressbook_Controller_Contact::getInstance()->search(new Addressbook_Model_ContactFilter(array(
            array('condition' => 'OR', 'filters' => array(
                array('field' => 'email',      'operator'  => 'equals', 'value' => $email),
                array('field' => 'email_home', 'operator'  => 'equals', 'value' => $email)
            )),
        )), new Tinebase_Model_Pagination(array(
            'sort'    => 'type', // prefer user over contact
            'dir'     => 'DESC',
            'limit'   => 1
        )));
        
        if (count($contacts) > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . " Found # of contacts " . count($contacts));
            $result = $contacts->getFirstRecord();
        
        } else if ($_implicitAddMissingContacts === TRUE) {
            $translation = Tinebase_Translation::getTranslation('Calendar');
            $i18nNote = $translation->_('This contact has been automatically added by the system as an event attender');
            if ($email !== $_attenderData['email']) {
                $i18nNote .= "\n";
                $i18nNote .= $translation->_('The email address has been shortened: ') . $_attenderData['email'] . ' -> ' . $email;
            }
            $contactData = array(
                'note'        => $i18nNote,
                'email'       => $email,
                'n_family'    => (isset($_attenderData['lastName']) && ! empty($_attenderData['lastName'])) ? $_attenderData['lastName'] : $email,
                'n_given'     => (isset($_attenderData['firstName'])) ? $_attenderData['firstName'] : '',
            );
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . " Ádd new contact " . print_r($contactData, true));
            $contact = new Addressbook_Model_Contact($contactData);
            $result = Addressbook_Controller_Contact::getInstance()->create($contact, FALSE);
        } else {
            $result = NULL;
        }
        
        return $result;
    }
    
    /**
     * sanitize email address
     * 
     * @param string $email
     * @return string
     * @throws Tinebase_Exception_Record_Validation
     */
    protected static function _sanitizeEmail($email)
    {
        // TODO should be generalized OR increase size of email field(s)
        $result = $email;
        if (strlen($email) > 64) {
            // try to find '/' for splitting
            $lastSlash = strrpos($email, '/');
            if ($lastSlash !== false) {
                $result = substr($email, $lastSlash + 1);
            }
            
            if (strlen($result) > 64) {
                // try to find first valid email
                if (preg_match(Tinebase_Mail::EMAIL_ADDRESS_REGEXP, $result, $matches)) {
                    $result = $matches[0];
                }
                
                if (strlen($result) > 64) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                        . ' Email address could not be sanitized: ' . $email . '(length: ' . strlen($email) . ')');
                    throw new Tinebase_Exception_Record_Validation('email string too long');
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                    . ' Email address has been sanitized: ' . $email . ' -> ' . $result);
            }
        }
        
        return $result;
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
        $_attendee->addIndices(array('user_type'));
        
        // flatten user_ids (not groups for group/list handling bellow)
        foreach($_attendee as $attendee) {
            if ($attendee->user_type != Calendar_Model_Attender::USERTYPE_GROUP && $attendee->user_id instanceof Tinebase_Record_Abstract) {
                $attendee->user_id = $attendee->user_id->getId();
            }
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
            } else if ($groupAttender->user_id !== NULL) {
                $group = Tinebase_Group::getInstance()->getGroupById($groupAttender->user_id);
                if (!empty($group->list_id)) {
                    $listId = $group->list_id;
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                    . ' Group attender ID missing');
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' ' . print_r($groupAttender->toArray(), TRUE));
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
     * @param Tinebase_Record_RecordSet $_attendeesSet
     * @param Calendar_Model_Attender   $_attendee
     * @return Calendar_Model_Attender|NULL
     */
    public static function getAttendee($_attendeesSet, Calendar_Model_Attender $_attendee)
    {
        if (!$_attendeesSet instanceof Tinebase_Record_RecordSet) {
            return null;
        }
        
        $attendeeUserId = $_attendee->user_id instanceof Tinebase_Record_Abstract
            ? $_attendee->user_id->getId()
            : $_attendee->user_id;
        
        foreach ($_attendeesSet as $attendeeFromSet) {
            $attendeeFromSetUserId = $attendeeFromSet->user_id instanceof Tinebase_Record_Abstract 
                ? $attendeeFromSet->user_id->getId()
                : $attendeeFromSet->user_id;
            
            if ($attendeeFromSetUserId === $attendeeUserId) {
                if ($attendeeFromSet->user_type === $_attendee->user_type) {
                    // can stop here
                    return $attendeeFromSet;
                }
                
                if (   $_attendee->user_type       === Calendar_Model_Attender::USERTYPE_USER
                    && $attendeeFromSet->user_type === Calendar_Model_Attender::USERTYPE_GROUPMEMBER
                ) {
                    $foundGroupMember = $attendeeFromSet;
                    // continue searching for $_attendee->user_type
                    // @todo maybe we can also return in this case immediately
                }
            }
        }
        
        return isset($foundGroupMember) ? $foundGroupMember : null;
    }
    
    /**
     * returns migration of two attendee sets
     * 
     * @param  Tinebase_Record_RecordSet $_current
     * @param  Tinebase_Record_RecordSet $_update
     * @return array migrationKey => Tinebase_Record_RecordSet
     */
    public static function getMigration($_current, $_update)
    {
        $result = array(
            'toDelete' => new Tinebase_Record_RecordSet('Calendar_Model_Attender'),
            'toCreate' => clone $_update,
            'toUpdate' => new Tinebase_Record_RecordSet('Calendar_Model_Attender'),
        );
        
        foreach($_current as $currAttendee) {
            $updateAttendee = self::getAttendee($result['toCreate'], $currAttendee);
            if ($updateAttendee) {
                $result['toUpdate']->addRecord($updateAttendee);
                $result['toCreate']->removeRecord($updateAttendee);
            } else {
                $result['toDelete']->addRecord($currAttendee);
            }
        }
        
        return $result;
    }
    
    /**
     * fill resolved attendees class cache
     * 
     * @param  Tinebase_Record_RecordSet|array  $eventAttendees
     * @throws Calendar_Exception
     */
    public static function fillResolvedAttendeesCache($eventAttendees)
    {
        if (empty($eventAttendees)) {
            return;
        }
        
        $eventAttendees = $eventAttendees instanceof Tinebase_Record_RecordSet
            ? array($eventAttendees)
            : $eventAttendees;
        
        $typeMap = array(
            self::USERTYPE_USER        => array(),
            self::USERTYPE_GROUPMEMBER => array(),
            self::USERTYPE_GROUP       => array(),
            self::USERTYPE_LIST        => array(),
            self::USERTYPE_RESOURCE    => array(),
            Calendar_Model_AttenderFilter::USERTYPE_MEMBEROF => array()
        );
        
        // build type map 
        foreach ($eventAttendees as $eventAttendee) {
            foreach ($eventAttendee as $attendee) {
                $user     = $attendee->user_id;
                $userType = $attendee->user_type;
                $userId   = $user instanceof Tinebase_Record_Abstract
                    ? $user->getId()
                    : $user;
                
                if (isset(self::$_resolvedAttendeesCache[$userType][$userId])) {
                    // already in cache
                    continue;
                }
                
                if ($user instanceof Tinebase_Record_Abstract) {
                    // can fill cache with model from $attendee
                    self::$_resolvedAttendeesCache[$userType][$userId] = $user;
                    
                    continue;
                }
                
                // must be resolved
                $typeMap[$userType][] = $userId;
            }
        }
        
        // get all missing user_id entries
        foreach ($typeMap as $type => $ids) {
            $ids = array_unique($ids);
            
            if (empty($ids)) {
                continue;
            }
            
            switch ($type) {
                case self::USERTYPE_USER:
                case self::USERTYPE_GROUPMEMBER:
                    $resolveCf = Addressbook_Controller_Contact::getInstance()->resolveCustomfields(FALSE);
                    $contacts  = Addressbook_Controller_Contact::getInstance()->getMultiple($ids, TRUE);
                    Addressbook_Controller_Contact::getInstance()->resolveCustomfields($resolveCf);
                    
                    foreach ($contacts as $contact) {
                        self::$_resolvedAttendeesCache[$type][$contact->getId()] = $contact;
                    }
                    
                    break;
                    
                case self::USERTYPE_GROUP:
                case Calendar_Model_AttenderFilter::USERTYPE_MEMBEROF:
                    // first fetch the groups, then the lists identified by list_id
                    $groups = Tinebase_Group::getInstance()->getMultiple($ids);
                    $lists  = Addressbook_Controller_List::getInstance()->getMultiple($groups->list_id, true);
                    
                    foreach ($groups as $group) {
                        $list = $lists->getById($group->list_id);
                        if ($list) {
                            self::$_resolvedAttendeesCache[$type][$group->getId()] = $list;
                        }
                    }
                    
                    break;
                    
                case self::USERTYPE_RESOURCE:
                    $resources = Calendar_Controller_Resource::getInstance()->getMultiple($ids, true);
                    
                    foreach ($resources as $resource) {
                        self::$_resolvedAttendeesCache[$type][$resource->getId()] = $resource;
                    }
                    
                    break;
                    
                default:
                    throw new Calendar_Exception("type $type not supported");
                    
                    break;
            }
        }
    }
    
    /**
     * return list of resolved attendee for given record(set)
     * 
     * @param Tinebase_Record_RecordSet|array   $eventAttendees 
     * @param bool                              $resolveDisplayContainers
     */
    public static function getResolvedAttendees($eventAttendees, $resolveDisplayContainers = TRUE)
    {
        if (empty($eventAttendees)) {
            return;
        }
        
        self::fillResolvedAttendeesCache($eventAttendees);
        
        $eventAttendees = $eventAttendees instanceof Tinebase_Record_RecordSet
            ? array($eventAttendees)
            : $eventAttendees;
        
        $foundDisplayContainers = false;
        
        // set containing all attendee
        $allAttendees = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        
        // build type map 
        foreach ($eventAttendees as $eventAttendee) {
            foreach ($eventAttendee as $attendee) {
                if (   $resolveDisplayContainers
                    && ! $foundDisplayContainers
                    && is_string($attendee->displaycontainer_id)
                ) {
                        $foundDisplayContainers = true;
                }
                
                if ($attendee->user_id instanceof Tinebase_Record_Abstract) {
                    // already resolved
                    $allAttendees->addRecord($attendee);
                    
                    continue;
                }
                
                if (isset(self::$_resolvedAttendeesCache[$attendee->user_type][$attendee->user_id])) {
                    $clonedAttendee = clone $attendee;
                    
                    // resolveable from cache
                    $clonedAttendee->user_id = self::$_resolvedAttendeesCache[$attendee->user_type][$attendee->user_id];
                    
                    $allAttendees->addRecord($clonedAttendee);
                    
                    continue;
                }
                
                // not resolved => problem!!!
            }
        }
        
        // resolve display containers
        if ($resolveDisplayContainers && $foundDisplayContainers) {
            Tinebase_Container::getInstance()->getGrantsOfRecords($allAttendees, Tinebase_Core::getUser(), 'displaycontainer_id');
        }
        
        return $allAttendees;
    }
    
    /**
     * resolves given attendee for json representation
     * 
     * @todo move status_authkey cleanup elsewhere
     * @todo use self::getResolvedAttendees to avoid code duplication
     * 
     * @param Tinebase_Record_RecordSet|array   $eventAttendees 
     * @param bool                              $resolveDisplayContainers
     * @param Calendar_Model_Event|array        $_events
     */
    public static function resolveAttendee($eventAttendees, $resolveDisplayContainers = TRUE, $_events = NULL)
    {
        if (empty($eventAttendees)) {
            return;
        }
        
        $eventAttendee = $eventAttendees instanceof Tinebase_Record_RecordSet ? array($eventAttendees) : $eventAttendees;
        $events = $_events instanceof Tinebase_Record_Abstract ? array($_events) : $_events;
        
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
                } elseif ((isset(self::$_resolvedAttendeesCache[$attender->user_type]) || array_key_exists($attender->user_type, self::$_resolvedAttendeesCache)) && (isset(self::$_resolvedAttendeesCache[$attender->user_type][$attender->user_id]) || array_key_exists($attender->user_id, self::$_resolvedAttendeesCache[$attender->user_type]))){
                    // already in cache
                    $attender->user_id = self::$_resolvedAttendeesCache[$attender->user_type][$attender->user_id];
                } else {
                    if (! (isset($typeMap[$attender->user_type]) || array_key_exists($attender->user_type, $typeMap))) {
                        $typeMap[$attender->user_type] = array();
                    }
                    $typeMap[$attender->user_type][] = $attender->user_id;
                }
            }
        }
        
        // resolve display containers
        if ($resolveDisplayContainers) {
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
                    $resolveCf = Addressbook_Controller_Contact::getInstance()->resolveCustomfields(FALSE);
                    $typeMap[$type] = Addressbook_Controller_Contact::getInstance()->getMultiple(array_unique($ids), TRUE);
                    Addressbook_Controller_Contact::getInstance()->resolveCustomfields($resolveCf);
                    break;
                case self::USERTYPE_GROUP:
                case Calendar_Model_AttenderFilter::USERTYPE_MEMBEROF:
                    // first fetch the groups, then the lists identified by list_id
                    $typeMap[$type] = Tinebase_Group::getInstance()->getMultiple(array_unique($ids));
                    $typeMap[self::USERTYPE_LIST] = Addressbook_Controller_List::getInstance()->getMultiple($typeMap[$type]->list_id, true);
                    break;
                case self::USERTYPE_RESOURCE:
                    $typeMap[$type] = Calendar_Controller_Resource::getInstance()->getMultiple(array_unique($ids), true);
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
                    self::$_resolvedAttendeesCache[$attender->user_type][$attender->user_id] = $attendeeTypeSet[$idx];
                    
                    $attender->user_id = $attendeeTypeSet[$idx];
                }
            }
        }
        
        
        foreach ($eventAttendee as $idx => $attendee) {
            $event = is_array($events) && (isset($events[$idx]) || array_key_exists($idx, $events)) ? $events[$idx] : NULL;
            
            foreach ($attendee as $attender) {
                // keep authkey if user has editGrant to displaycontainer
                if (isset($attender['displaycontainer_id']) && !is_scalar($attender['displaycontainer_id']) && (isset($attender['displaycontainer_id']['account_grants'][Tinebase_Model_Grants::GRANT_EDIT]) || array_key_exists(Tinebase_Model_Grants::GRANT_EDIT, $attender['displaycontainer_id']['account_grants'])) &&  $attender['displaycontainer_id']['account_grants'][Tinebase_Model_Grants::GRANT_EDIT]) {
                    continue;
                }
                
                // keep authkey if attender is a contact (no account) and user has editGrant for event
                if ($attender->user_type == self::USERTYPE_USER
                    && $attender->user_id instanceof Tinebase_Record_Abstract
                    && (!$attender->user_id->has('account_id') || !$attender->user_id->account_id)
                    && (!$event || $event->{Tinebase_Model_Grants::GRANT_EDIT})
                ) {
                    continue;
                }
                
                $attender->status_authkey = NULL;
            }
        }
    }
    
    /**
     * checks if given alarm should be send to given attendee
     * 
     * @param  Calendar_Model_Attender $_attendee
     * @param  Tinebase_Model_Alarm    $_alarm
     * @return bool
     */
    public static function isAlarmForAttendee($_attendee, $_alarm, $_event=NULL)
    {
        // attendee: array with one user_type/id if alarm is for one attendee only
        $attendeeOption = $_alarm->getOption('attendee');
        
        // skip: array of array of user_type/id with attendees this alarm is to skip for
        $skipOption = $_alarm->getOption('skip');
        
        if ($attendeeOption) {
            return (bool) self::getAttendee(new Tinebase_Record_RecordSet('Calendar_Model_Attender', array($_attendee)), new Calendar_Model_Attender($attendeeOption));
        }
        
        if (is_array($skipOption)) {
            $skipAttendees = new Tinebase_Record_RecordSet('Calendar_Model_Attender', $skipOption);
            if(self::getAttendee($skipAttendees, $_attendee)) {
                return false;
            }
        }
        
        $isOrganizerCondition = $_event ? $_event->isOrganizer($_attendee) : TRUE;
        $isAttendeeCondition = $_event && $_event->attendee instanceof Tinebase_Record_RecordSet ? self::getAttendee($_event->attendee, $_attendee) : TRUE;
        return ($isAttendeeCondition || $isOrganizerCondition)&& $_attendee->status != Calendar_Model_Attender::STATUS_DECLINED;
    }
}
