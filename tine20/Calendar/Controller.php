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
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') updated group ' . $_eventObject->group->name);
                Calendar_Controller_Event::getInstance()->onUpdateGroup($_eventObject->group->getId());
                break;
            case 'Admin_Event_AddGroupMember':
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') add groupmember ' . (string) $_eventObject->userId . ' to group ' . (string) $_eventObject->groupId);
                Calendar_Controller_Event::getInstance()->onUpdateGroup($_eventObject->groupId);
                break;
                
            case 'Admin_Event_RemoveGroupMember':
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') removed groupmember ' . (string) $_eventObject->userId . ' from group ' . (string) $_eventObject->groupId);
                Calendar_Controller_Event::getInstance()->onUpdateGroup($_eventObject->groupId);
                break;
                
            case 'Tinebase_Event_Container_BeforeCreate':
                $this->_handleContainerBeforeCreateEvent($_eventObject);
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
        
        $account = Tinebase_User::getInstance()->getUserById($_account);
        
        $newContainer = new Tinebase_Model_Container(array(
            'name'              => sprintf($translation->_("%s's personal calendar"), $account->accountFullName),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'owner_id'          => $_account,
            'backend'           => 'Sql',
            'color'             => '#FF6600',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId() 
        ));
        
        $personalContainer = Tinebase_Container::getInstance()->addContainer($newContainer);
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
            
            $grants->addRecord(new Tinebase_Model_Grants(array(
                'account_id'      => '0',
                'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                Tinebase_Model_Grants::GRANT_FREEBUSY  => true
            ), TRUE));
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
    public function sendEventNotifications($_event, $_updater, $_action, $_oldEvent=NULL)
    {
        Calendar_Controller_EventNotifications::getInstance()->doSendNotifications($_event, $_updater, $_action, $_oldEvent);
    }
}
