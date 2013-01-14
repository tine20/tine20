<?php
/**
 * Tine 2.0
 * 
 * @package     Inventory
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Inventory Controller (composite)
 * 
 * The Inventory 2.0 Controller manages access (acl) to the different backends and supports
 * a common interface to the servers/views
 * 
 * @package Inventory
 * @subpackage  Controller
 */
class Inventory_Controller extends Tinebase_Controller_Event implements Tinebase_Container_Interface
{

    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'Inventory_Model_InventoryItem';
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_applicationName = 'Inventory';
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
     * @var Inventory_Controller
     */
    private static $_instance = NULL;
    
    /**
     * singleton
     *
     * @return Inventory_Controller
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Inventory_Controller();
        }
        return self::$_instance;
    }
    
    /**
     * creates the initial folder for new accounts
     *
     * @param mixed[int|Tinebase_Model_User] $_account   the accountd object
     * @return Tinebase_Record_RecordSet                            of subtype Tinebase_Model_Container
     */
    public function createPersonalFolder($_accountId)
    {
        $translation = Tinebase_Translation::getTranslation('Inventory');
        $account = Tinebase_User::getInstance()->getUserById($_accountId);
        
        $newContainer = new Tinebase_Model_Container(array(
            'name'              => sprintf($translation->_("%s's personal inventory items"), $account->accountFullName),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'owner_id'          => $_accountId,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Inventory')->getId(),
            'model'             => static::$_defaultModel
        ));
        
        $personalContainer = Tinebase_Container::getInstance()->addContainer($newContainer);
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
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' (' . __LINE__ . ') handle event of type ' . get_class($_eventObject));
        
        switch(get_class($_eventObject)) {
            case 'Admin_Event_AddAccount':
                $this->createPersonalFolder($_eventObject->account);
                break;
            /* 
             * deletePersonalFolder does not exists
             * *case 'Admin_Event_DeleteAccount':
                #$this->deletePersonalFolder($_eventObject->account);
                break;*/
        }
    }
}
