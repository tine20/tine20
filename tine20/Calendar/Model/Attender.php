<?php
/**
 * Sql Calendar 
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Model of an attendee
 *
 * @package Calendar
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
        
        'cal_event_id'         => array('allowEmpty' => true,  'Alnum'),
        'user_id'              => array('allowEmpty' => false,        ),
        'user_type'            => array('allowEmpty' => true,  'InArray' => array(self::USERTYPE_USER, self::USERTYPE_GROUP, self::USERTYPE_GROUPMEMBER, self::USERTYPE_RESOURCE)),
        'role'                 => array('allowEmpty' => true,  'InArray' => array(self::ROLE_OPTIONAL, self::ROLE_REQUIRED)),
        'quantity'             => array('allowEmpty' => true, 'Int'   ),
        'status'               => array('allowEmpty' => true,  'InArray' => array(self::STATUS_NEEDSACTION, self::STATUS_TENTATIVE, self::STATUS_ACCEPTED, self::STATUS_DECLINED)),
        'status_authkey'       => array('allowEmpty' => true, 'Alnum' ),
        'displaycontainer_id'  => array('allowEmpty' => true, 'Int'   ),
    );
    
    /**
     * datetime fields
     *
     * @var array
     */
    protected $_datetimeFields = array();
    
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
        
        $contact = Addressbook_Controller_Contact::getInstance()->get($this->user_id);
        return $contact->account_id ? $contact->account_id : NULL;
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
                $_data['user_id'] = Addressbook_Controller_Contact::getInstance()->getContactByUserId($_data['user_id']['accountId'])->getId();
            } else if (array_key_exists('bday', $_data['user_id'])) {
                $_data['user_id'] = $_data['user_id']['id'];
            }
        }
        
        parent::setFromArray($_data);
    }
    
    /**
     * returns email address of given attender
     * 
     * @param Calendar_Model_Attender $_attender
     * @return string
     */
    public static function getAttenderEmail(Calendar_Model_Attender $_attender)
    {
    	try {
	    	switch ($_attender->type) {
	    		case self::USERTYPE_USER:
	    			$contact = $_attender->user_id instanceof Addressbook_Model_Contact ?
	    			    $_attender->user_id :
	    			    Addressbook_Controller_Contact::getInstance()->get($_attender->user_id);
	    			    
    			    return $contact->getPreferedEmailAddress();
	    			break;
	    		default:
	    			throw new Exception("type $type not yet supported");
	                break;
	    	}
    	} catch (Exeption $e) {
    		return NULL;
    	}
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
    	$currentAttendee = $event->getId() ? 
    	   Calendar_Controller_Event::getInstance()->get($event->getId())->attendee :
    	   new Tinebase_Record_RecordSet('Calendar_Model_Attender');
       
        // resolve current attendee
        self::resolveAttendee($currentAttendee);
        
        // build currentMailMap
        // NOTE: non resolvable attendee will be discarded in the map
        //       this is _important_ for the calculation of migration as it
        //       saves us from deleting attendee out of current users scope
        $currentEmailMap = array();
        foreach ($currentAttendee as $currentAttender) {
        	$currentAttenderEmailAdress = self::getAttenderEmail($currentAttender);
        	if ($currentAttenderEmailAdress) {
        	    $currentEmailMap[$currentAttenderEmailAdress] = $currentAttender->getId();
        	}
        }
        
        // initialize convertEmailMap
        $convertEmailMap = array();
        foreach ($_emails as $email) {
            $convertEmailMap[$email] = '';
        }
        
        // calculate migration
        $toAdd    = array_diff_key($convertEmailMap, $currentEmailMap);
        $toDelete = array_diff_key($currentEmailMap, $convertEmailMap);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " attendee to add " . print_r($toAdd, true));
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " attendee to delete " . print_r($toAdd, true));
        
        // delete attendee from set
        foreach ($toDelete as $email => $attenderId) {
        	unset($currentAttendee[$attenderId]);
        }
        
        // add attendee identified by their emailAdress
        foreach (array_keys($toAdd) as $email) {
        	$contacts = $addressbook->search(new Addressbook_Model_ContactFilter(array(
        	    array('field' => 'containerType', 'operator' => 'equals', 'value' => 'all'),
                array('condition' => 'OR', 'filters' => array(
                    array('field' => 'email',      'operator'  => 'equals', 'value' => (string) $email),
                    array('field' => 'email_home', 'operator'  => 'equals', 'value' => (string) $email),
                )),
        	)));
        	
            if(count($contacts) > 0) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found # of contacts " . count($contacts));
                
                $contactId = NULL;
                $accountIdMap = $contacts->account_id;
                
                // prefer account over contact
                foreach ($accountIdMap as $contactMapId => $accountMapId) {
                    $contactId = $accountMapId;
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " taking contact with account with id " . $accountMapId);
                }
                
                if (! $contactId) {
                	$contactId = $contacts->getFirstRecord()->getId();
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " taking contact with id " . $contactId);
                    
                }
                
                
                $contactId = $contacts->getFirstRecord()->getId();
                
            } else if ($_ImplicitAddMissingContacts) {
            	$translation = Tinebase_Translation::getTranslation('Calendar');
            	$i18nNote = $translation->_('This contact has been automatically added by the system as an event attender');
                $contactData = array(
                    'note'        => $i18nNote,
                    'email'       => (string) $email,
                    'n_family'    => array_value(0, explode('@', (string) $email)),
                );
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " add new contact " . print_r($contactData, true));
                $contact = new Addressbook_Model_Contact($contactData);
                
                $contactId = $addressbook->create($contact)->getId();
            } else {
            	Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " discarding attender " . $email);
            	
            	$contactId = NULL;
            }
            
            // finally add to attendee
            $currentAttendee->addRecord(new Calendar_Model_Attender(array(
                'user_id'   => $contactId,
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
            )));
        }
        
        return $currentAttendee;
    }
    
    /**
     * resolves given attendee for json representation
     *
     * @param array|Tinebase_Record_RecordSet $_attendee 
     * @param unknown_type $_idProperty
     * @param unknown_type $_typeProperty
     */
    public static function resolveAttendee($_eventAttendee, $_idProperty='user_id', $_typeProperty='user_type') {
        $eventAttendee = $_eventAttendee instanceof Tinebase_Record_RecordSet ? array($_eventAttendee) : $_eventAttendee;
        
        // build type map 
        $typeMap = array();
        
        foreach ($eventAttendee as $attendee) {
            // resolve displaycontainers
            Tinebase_Container::getInstance()->getGrantsOfRecords($attendee, Tinebase_Core::getUser(), 'displaycontainer_id');
            
            foreach ($attendee as $attender) {
                $type = $attender->$_typeProperty;
                if (! array_key_exists($type, $typeMap)) {
                    $typeMap[$type] = array();
                }
                $typeMap[$type][] = $attender->$_idProperty;
                
                // remove status_authkey when editGrant for displaycontainer_id is missing
                if (! is_array($attender->displaycontainer_id) || ! (bool) $attender['displaycontainer_id']['account_grants']['editGrant']) {
                    $attender->status_authkey = NULL;
                }
            }
        }
        
        // get all $_idProperty entries
        foreach ($typeMap as $type => $ids) {
            switch ($type) {
                case self::USERTYPE_USER:
                    //Tinebase_Core::getLogger()->debug(print_r(array_unique($ids), true));
                    $typeMap[$type] = Addressbook_Controller_Contact::getInstance()->getMultiple(array_unique($ids));
                    break;
                case 'group':
                //    Tinebase_Group::getInstance()->getM
                default:
                    throw new Exception("type $type not yet supported");
                    break;
            }
        }
        
        // sort entries in
        foreach ($eventAttendee as $attendee) {
            foreach ($attendee as $attender) {
                $attendeeTypeSet = $typeMap[$attender->$_typeProperty];
                $idx = $attendeeTypeSet->getIndexById($attender->$_idProperty);
                if ($idx !== false) {
                    $attender->$_idProperty = $attendeeTypeSet[$idx];
                }
            }
        }
    }
}