<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Tasks Controller (composite)
 * 
 * The Tasks 2.0 Controller manages access (acl) to the different backends and supports
 * a common interface to the servers/views
 * 
 * @package Tasks
 * @subpackage  Controller
 */
class Tasks_Controller extends Tinebase_Controller_Abstract implements Tinebase_Event_Interface, Tinebase_Container_Interface
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Tasks';
        $this->_currentAccount = Tinebase_Core::getUser();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
    
    /**
     * holds self
     * @var Tasks_Controller
     */
    private static $_instance = NULL;
    
    /**
     * singleton
     *
     * @return Tasks_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tasks_Controller();
        }
        return self::$_instance;
    }
    
    /**
     * temporaray function to get a default container]
     * 
     * @param string $_referingApplication
     * @return Tinebase_Model_Container container
     * 
     * @todo replace this by Tinebase_Container::getDefaultContainer
     */
    public function getDefaultContainer($_referingApplication = 'tasks')
    {
        $taskConfig = Tinebase_Core::getConfig()->tasks;
        $configString = 'defaultcontainer_' . ( empty($_referingApplication) ? 'tasks' : $_referingApplication );
        
        if (isset($taskConfig->$configString)) {
            $defaultContainer = Tinebase_Container::getInstance()->getContainerById((int)$taskConfig->$configString);
        } else {
            $defaultContainer = Tinebase_Container::getInstance()->getDefaultContainer($this->_currentAccount->accountId, 'Tasks');
        }
        
        return $defaultContainer;
    }
        
    /**
     * creates the initial folder for new accounts
     *
     * @param mixed[int|Tinebase_Model_User] $_account   the accountd object
     * @return Tinebase_Record_RecordSet                            of subtype Tinebase_Model_Container
     */
    public function createPersonalFolder($_accountId)
    {
        $translation = Tinebase_Translation::getTranslation('Tasks');
        
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $account = Tinebase_User::getInstance()->getUserById($accountId);
        $newContainer = new Tinebase_Model_Container(array(
            'name'              => sprintf($translation->_("%s's personal tasks"), $account->accountFullName),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId() 
        ));
        
        $personalContainer = Tinebase_Container::getInstance()->addContainer($newContainer, NULL, FALSE, $accountId);
        $container = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($personalContainer));
        
        return $container;
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
                $this->createPersonalFolder($_eventObject->account);
                break;
            case 'Admin_Event_DeleteAccount':
                #$this->deletePersonalFolder($_eventObject->account);
                break;
        }
    }
}
