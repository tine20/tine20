<?php
/**
 * Sql Calendar 
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * main controller for Calendar
 *
 * @package     Calendar
 */
class Calendar_Controller extends Tinebase_Controller_Event implements Tinebase_Container_Interface
{
    /**
     * holds the instance of the singleton
     *
     * @var Calendar_Controller
     */
    private static $_instance = NULL;

    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'Calendar_Model_Event';
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * the singleton pattern
     *
     * @return Calendar_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Calendar_Controller();
        }
        
        return self::$_instance;
    }

    /**
     * event handler function
     * 
     * all events get routed through this function
     *
     * @param Tinebase_Event_Abstract $_eventObject the eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' (' . __LINE__ . ') handle event of type ' . get_class($_eventObject));
        
        switch (get_class($_eventObject)) {
            case 'Admin_Event_AddAccount':
                //$this->createPersonalFolder($_eventObject->account);
                Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $_eventObject->account->getId());
                break;
                
            case 'Tinebase_Event_User_DeleteAccount':
                /**
                 * @var Tinebase_Event_User_DeleteAccount $_eventObject
                 */
                $this->_handleDeleteUserEvent($_eventObject);

                // let event bubble
                parent::_handleEvent($_eventObject);
                break;
                
            case 'Admin_Event_UpdateGroup':
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') updated group ' . $_eventObject->group->name);
                Tinebase_ActionQueue::getInstance()->queueAction('Calendar.onUpdateGroup', $_eventObject->group->getId());
                break;
            case 'Admin_Event_AddGroupMember':
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') add groupmember ' . (string) $_eventObject->userId . ' to group ' . (string) $_eventObject->groupId);
                Tinebase_ActionQueue::getInstance()->queueAction('Calendar.onUpdateGroup', $_eventObject->groupId);
                break;
                
            case 'Admin_Event_RemoveGroupMember':
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') removed groupmember ' . (string) $_eventObject->userId . ' from group ' . (string) $_eventObject->groupId);
                Tinebase_ActionQueue::getInstance()->queueAction('Calendar.onUpdateGroup', $_eventObject->groupId);
                break;
                
            case 'Tinebase_Event_Container_BeforeCreate':
                $this->_handleContainerBeforeCreateEvent($_eventObject);
                break;
        }
    }

    protected function _handleDeleteUserEvent($_eventObject)
    {
        if ($_eventObject->keepOrganizerEvents()) {
            $this->_keepOrganizerEvents($_eventObject);
        }

        if ($_eventObject->deletePersonalContainers()) {
            $this->deletePersonalFolder($_eventObject->account);
        }
    }

    protected function _keepOrganizerEvents($_eventObject)
    {
        $accountId = $_eventObject->account->getId();
        $contactId = $_eventObject->account->contact_id;

        $contact = null;
        $newContact = null;
        $contactEmail = null;
        if ($_eventObject->keepAsContact()) {
            try {
                $contact = Addressbook_Controller_Contact::getInstance()->get($contactId);
                $contactEmail = $contact->getPreferedEmailAddress();
            } catch (Tinebase_Exception_NotFound $tenf) {
                // ignore
                $contactEmail = $_eventObject->account->accountEmailAddress;
            }
        } else {
            $contactEmail = $_eventObject->account->accountEmailAddress;
        }

        if (null === $contact) {
            $newContact = Calendar_Model_Attender::resolveEmailToContact(array(
                'email' => $contactEmail,
            ));
        }

        $eventController = Calendar_Controller_Event::getInstance();
        $oldState = $eventController->doContainerACLChecks(false);
        // delete all events where our deletee is organizer and that are private (no matter if they have attendees or not)
        /*$filter = new Calendar_Model_EventFilter(array(
            array('field' => 'class', 'operator' => 'equals', 'value' => Calendar_Model_Event::CLASS_PRIVATE),
            array('field' => 'organizer', 'operator' => 'equals', 'value' => $contactId),
        ));
        $eventController->deleteByFilter($filter);*/

        // delete all events where our deletee is organizer and that dont have any additional attenders except the organizer / deletee himself
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'organizer', 'operator' => 'equals', 'value' => $contactId),
            array('field' => 'attender', 'operator' => 'notHasSomeExcept', 'value' => array(
                'user_type' => Calendar_Model_Attender::USERTYPE_USER,
                'user_id' => $contactId,
            )),
        ));
        $eventController->deleteByFilter($filter);

        $eventController->doContainerACLChecks($oldState);

        // get all personal containers
        $containers = Tinebase_Container::getInstance()->getPersonalContainer($accountId, $this->getDefaultModel(), $accountId, '*', true);
        if ($containers->count() > 0) {
            // take the first one and make it an invitation container
            $container = $containers->getByIndex(0);
            $this->convertToInvitationContainer($container, $contactEmail);

            // if there are more than 1 container, move contents to invitation container, then delete them
            $i = 1;
            while ($containers->count() > 1) {
                $moveContainer = $containers->getByIndex($i);
                $containers->offsetUnset($i++);

                //move $moveContainer content to $container
                $eventController->getBackend()->moveEventsToContainer($moveContainer, $container);
                //delete $moveContainer
                Tinebase_Container::getInstance()->deleteContainer($moveContainer, true);
            }
        }

        // replace old contactId with newContact->getId()
        if (null !== $newContact) {
            $eventController->getBackend()->replaceContactId($contactId, $newContact->getId());
        }
    }

    /**
     * Converts the calendar to be a calendar for external organizer
     *
     * @param Tinebase_Model_Container $container
     */
    public function convertToInvitationContainer(Tinebase_Model_Container $container, $emailAddress)
    {
        if ($container->model !== 'Calendar_Model_Event') {
            Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . ' container provided needs to have the model Calendar_Model_Event instead of ' . $container->model);
            throw Tinebase_Exception_UnexpectedValue('container provided needs to have the model Calendar_Model_Event instead of ' . $container->model);
        }

        $tbc = Tinebase_Container::getInstance();
        try {
            $oldContainer = $tbc->getContainerByName('Calendar', $emailAddress, Tinebase_Model_Container::TYPE_SHARED);

            // TODO fix me!
            // bad, we should move the events from $oldContainer to $container

            $tbc->deleteContainer($oldContainer, true);
        } catch (Tinebase_Exception_NotFound $tenf) {
            //good, ignore
        }

        $container->name = $emailAddress;
        $container->color = '#333399';
        $container->type = Tinebase_Model_Container::TYPE_SHARED;
        $tbc->update($container);

        $grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(
            array(
                'account_id'      => '0',
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Tinebase_Model_Grants::GRANT_ADD         => true,
                Tinebase_Model_Grants::GRANT_EDIT        => true,
                Tinebase_Model_Grants::GRANT_DELETE      => true,
            )
        ));
        $tbc->setGrants($container->getId(), $grants, true, false);
    }

    /**
     * Get/Create Calendar for external organizer
     * 
     * @param  Addressbook_Model_Contact $organizer organizer id
     * @param  string $emailAddress
     * @return Tinebase_Model_Container  container id
     */
    public function getInvitationContainer($organizer, $emailAddress = null)
    {
        if (null!==$organizer) {
            $containerName = $organizer->getPreferedEmailAddress();
        } else {
            $containerName = $emailAddress;
        }
        
        try {
            $container = Tinebase_Container::getInstance()->getContainerByName('Calendar', $containerName, Tinebase_Model_Container::TYPE_SHARED);
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' No invitation container found. Creating a new one for organizer ' . $containerName);
            
            $container = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
                'name'              => $containerName,
                'color'             => '#333399',
                'type'              => Tinebase_Model_Container::TYPE_SHARED,
                'backend'           => Tinebase_User::SQL,
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
                'model'             => 'Calendar_Model_Event'
            )), NULL, TRUE);
            
            $grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(
                array(
                    'account_id'      => '0',
                    'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                    Tinebase_Model_Grants::GRANT_ADD         => true,
                    Tinebase_Model_Grants::GRANT_EDIT        => true,
                    Tinebase_Model_Grants::GRANT_DELETE      => true,
                )
            ));
            Tinebase_Container::getInstance()->setGrants($container->getId(), $grants, true, false);
        }
         
        return $container;
    }
    
    /**
     * creates the initial folder for new accounts
     *
     * @param mixed[int|Tinebase_Model_User] $_account   the accountd object
     * @return Tinebase_Record_RecordSet of subtype Tinebase_Model_Container
     * 
     * @todo use Tinebase_Container::getDefaultContainer
     */
    public function createPersonalFolder($_account)
    {
        $translation = Tinebase_Translation::getTranslation('Calendar');
        
        $account = Tinebase_User::getInstance()->getUserById($_account);
        
        $newContainer = new Tinebase_Model_Container(array(
            'name'              => sprintf($translation->_("%s's personal calendar"), $account->accountFullName),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'owner_id'          => $_account,
            'backend'           => Tinebase_User::SQL,
            'color'             => '#FF6600',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'             => static::$_defaultModel
        ));
        
        $personalContainer = Tinebase_Container::getInstance()->addContainer($newContainer);
        $container = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($personalContainer));
        
        return $container;
    }
    
    /**
     * handler for Tinebase_Event_Container_BeforeCreate
     * - give owner of personal container all grants
     * - give freebusy grants to anyone for personal container
     * 
     * @param Tinebase_Event_Container_BeforeCreate $_eventObject
     */
    protected function _handleContainerBeforeCreateEvent(Tinebase_Event_Container_BeforeCreate $_eventObject)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->INFO(__METHOD__ . ' (' . __LINE__ . ') about to handle Tinebase_Event_Container_BeforeCreate' );
        
        if ($_eventObject->container && 
            $_eventObject->container->type === Tinebase_Model_Container::TYPE_PERSONAL &&
            $_eventObject->container->application_id === Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId() &&
            $_eventObject->grants instanceof Tinebase_Record_RecordSet
            ) {
            // get owner from initial initial grants
            $grants = $_eventObject->grants;
            $grants->removeAll();
            
            $grants->addRecord(new Tinebase_Model_Grants(array(
                'account_id'     => $_eventObject->accountId,
                'account_type'   => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Tinebase_Model_Grants::GRANT_READ      => true,
                Tinebase_Model_Grants::GRANT_ADD       => true,
                Tinebase_Model_Grants::GRANT_EDIT      => true,
                Tinebase_Model_Grants::GRANT_DELETE    => true,
                Tinebase_Model_Grants::GRANT_EXPORT    => true,
                Tinebase_Model_Grants::GRANT_SYNC      => true,
                Tinebase_Model_Grants::GRANT_ADMIN     => true,
                Tinebase_Model_Grants::GRANT_FREEBUSY  => true,
                Tinebase_Model_Grants::GRANT_PRIVATE   => true,
            ), TRUE));
            
            if (! Tinebase_Config::getInstance()->get(Tinebase_Config::ANYONE_ACCOUNT_DISABLED)) {
                $grants->addRecord(new Tinebase_Model_Grants(array(
                    'account_id'      => '0',
                    'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                    Tinebase_Model_Grants::GRANT_FREEBUSY  => true
                ), TRUE));
            }
        }
    }
    
    /**
     * send notifications 
     * 
     * @param Calendar_Model_Event       $_event
     * @param Tinebase_Model_FullAccount $_updater
     * @param Sting                      $_action
     * @param Calendar_Model_Event       $_oldEvent
     * @return void
     */
    public function sendEventNotifications($_event, $_updater, $_action, $_oldEvent = NULL)
    {
        Calendar_Controller_EventNotifications::getInstance()->doSendNotifications($_event, $_updater, $_action, $_oldEvent);
    }
    
    /**
     * update group events
     * 
     * @param string $_groupId
     * @return void
     */
    public function onUpdateGroup($_groupId)
    {
        Calendar_Controller_Event::getInstance()->onUpdateGroup($_groupId);
    }
}
