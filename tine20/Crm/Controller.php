<?php
/**
 * MAIN controller for CRM application
 * 
 * the main logic of the CRM application
 *
 * @package     Crm
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * leads controller class for CRM application
 * 
 * @package     Crm
 * @subpackage  Controller
 */
class Crm_Controller extends Tinebase_Application_Controller_Abstract implements Tinebase_Events_Interface, Tinebase_Container_Interface
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Crm';
    
    /**
     * holdes the instance of the singleton
     *
     * @var Crm_Controller
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Crm_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Crm_Controller;
        }
        
        return self::$_instance;
    }    
        
    /********************* event handler and personal folder ***************************/
    
    /**
     * event handler function
     * 
     * all events get routed through this function
     *
     * @param Tinebase_Events_Abstract $_eventObject the eventObject
     */
    public function handleEvents(Tinebase_Events_Abstract $_eventObject)
    {
        Zend_Registry::get('logger')->debug(__METHOD__ . ' (' . __LINE__ . ') handle event of type ' . get_class($_eventObject));
        
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
     * creates the initial folder for new accounts
     *
     * @param mixed[int|Tinebase_Model_User] $_account   the accountd object
     * @return Tinebase_Record_RecordSet                            of subtype Tinebase_Model_Container
     */
    public function createPersonalFolder($_accountId)
    {
        $translation = Tinebase_Translation::getTranslation('Crm');
        
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $account = Tinebase_User::getInstance()->getUserById($accountId);
        $newContainer = new Tinebase_Model_Container(array(
            'name'              => sprintf($translation->_("%s's personal leads"), $account->accountFullName),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Crm')->getId() 
        ));
        
        $personalContainer = Tinebase_Container::getInstance()->addContainer($newContainer, NULL, FALSE, $accountId);
        $personalContainer->account_grants = Tinebase_Model_Container::GRANT_ANY;
        
        $container = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($personalContainer));
        
        return $container;
    }

    /**
     * delets the personal folder for deleted accounts
     *
     * @param mixed[int|Tinebase_Model_User] $_account   the accountd object
     * @return void
     * 
     * @todo    implement
     */
    public function deletePersonalFolder($_accountId)
    {
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        
        // delete personal folder here
    } 
}
