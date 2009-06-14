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
class Calendar_Controller extends Tinebase_Controller_Abstract implements Tinebase_Events_Interface, Tinebase_Container_Interface
{
    /**
     * holdes the instance of the singleton
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
     * @param Tinebase_Events_Abstract $_eventObject the eventObject
     */
    public function handleEvents(Tinebase_Events_Abstract $_eventObject)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') handle event of type ' . get_class($_eventObject));
        
        switch(get_class($_eventObject)) {
            case 'Admin_Event_AddAccount':
                $this->createPersonalFolder($_eventObject->account);
                break;
            case 'Admin_Event_DeleteAccount':
                $this->deletePersonalFolder($_eventObject->account);
                break;
        }
    }
    
    /**
     * returns the defualt display calender for given account
     *
     * @todo add preference and use this
     *  -> auto created container should be the prefered
     * 
     * @param  mixed[int|Tinebase_Model_User] $_account   the accountd object
     * @return Tinebase_Model_Container
     */
    public function getDefaultDisplayCalendar($_account)
    {
        $calendars = Tinebase_Container::getInstance()->getPersonalContainer($_account, 'Calendar', $_account, 0, true);
        return $calendars->getFirstRecord();
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
        $personalContainer['account_grants'] = Tinebase_Model_Container::GRANT_ANY;
        
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
    
}
