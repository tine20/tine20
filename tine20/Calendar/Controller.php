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
 * main controller for Calendar
 *
 * @package     Calendar
 */
class Calendar_Controller extends Tinebase_Controller_Abstract implements Tinebase_Event_Interface, Tinebase_Container_Interface
{
    /**
     * holds the instance of the singleton
     *
     * @var Calendar_Controller
     */
    private static $_instance = NULL;

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
    public function handleEvents(Tinebase_Event_Abstract $_eventObject)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') handle event of type ' . get_class($_eventObject));
        
        switch(get_class($_eventObject)) {
            case 'Admin_Event_AddAccount':
                //$this->createPersonalFolder($_eventObject->account);
                Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $_eventObject->account->getId());
                break;
                
            case 'Admin_Event_DeleteAccount':
                // not a good idea, as it could be the originaters container for invitations
                // we need to move all contained events first
                //$this->deletePersonalFolder($_eventObject->account);
                break;
                
            case 'Admin_Event_UpdateGroup':
                Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') updated group ' . $_eventObject->group->name);
                Calendar_Controller_Event::getInstance()->onUpdateGroup($_eventObject->group->getId());
                break;
            case 'Admin_Event_AddGroupMember':
                Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') add groupmember ' . (string) $_eventObject->userId . ' to group ' . (string) $_eventObject->groupId);
                Calendar_Controller_Event::getInstance()->onUpdateGroup($_eventObject->groupId);
                break;
                
            case 'Admin_Event_RemoveGroupMember':
                Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') removed groupmember ' . (string) $_eventObject->userId . ' from group ' . (string) $_eventObject->groupId);
                Calendar_Controller_Event::getInstance()->onUpdateGroup($_eventObject->groupId);
                break;
        }
    }
    
    /**
     * creates the initial folder for new accounts
     *
     * @param mixed[int|Tinebase_Model_User] $_account   the accountd object
     * @return Tinebase_Record_RecordSet of subtype Tinebase_Model_Container
     */
    public function createPersonalFolder($_account)
    {
        $translation = Tinebase_Translation::getTranslation('Calendar');
        
        $accountId = Tinebase_Model_User::convertUserIdToInt($_account);
        $account = Tinebase_User::getInstance()->getUserById($accountId);
        $newContainer = new Tinebase_Model_Container(array(
            'name'              => sprintf($translation->_("%s's personal calendar"), $account->accountFullName),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId() 
        ));
        
        $personalContainer = Tinebase_Container::getInstance()->addContainer($newContainer, NULL, FALSE, $accountId);
        $container = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($personalContainer));
        
        return $container;
    }
    
    /**
     * delete all personal user folders and the contacts associated with these folders
     *
     * @param Tinebase_Model_User $_account the accountd object
     * @todo implement and write test
     */
    public function deletePersonalFolder($_account)
    {
        
    }
    
    /**
     * sends notifications for an event
     * 
     * @param array  $_eventData
     * @param array  $_updaterData
     * @param string $_action
     * @param array  $_oldEventData
     * @return void
     *
    public function sendEventNotifications($_eventData, $_updaterData, $_action, $_oldEventData=NULL)
    {
        $event = new Calendar_Model_Event($_eventData);
        $updater = new Tinebase_Model_FullUser($_updaterData);
        $oldEvent = new Calendar_Model_Event($_oldEventData);
        
        Calendar_Controller_EventNotifications::getInstance()->sendNotifications($event, $updater, $_action, $oldEvent);
    }
    */
    
    /**
     * send notifications 
     * 
     * @param Calendar_Model_Event       $_event
     * @param Tinebase_Model_FullAccount $_updater
     * @param Sting                      $_action
     * @param Calendar_Model_Event       $_oldEvent
     * @return void
     */
    public function sendEventNotifications($_event, $_updater, $_action, $_oldEvent=NULL)
    {
        Calendar_Controller_EventNotifications::getInstance()->sendNotifications($_event, $_updater, $_action, $_oldEvent);
    }
}
