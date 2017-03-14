<?php
/**
 * Tine 2.0
 * 
 * MAIN controller for filemanager, does event and container handling
 *
 * @package     Filemanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * main controller for Filemanager
 *
 * @package     Filemanager
 * @subpackage  Controller
 */
class Filemanager_Controller extends Tinebase_Controller_Event implements Tinebase_Application_Container_Interface
{
    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'Filemanager_Model_Node';

    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Filemanager';
    
    /**
     * holds the instance of the singleton
     *
     * @var Filemanager_Controller
     */
    private static $_instance = NULL;

    /**
     * constructor (get current user)
     */
    private function __construct() {
    }
    
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
     * @return Addressbook_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Filemanager_Controller;
        }
        
        return self::$_instance;
    }

    /**
     * event handler function
     * 
     * all events get routed through this function
     *
     * @param Tinebase_Event_Abstract $_eventObject the eventObject
     * 
     * @todo    write test
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' (' . __LINE__ . ') handle event of type ' . get_class($_eventObject));
        
        switch (get_class($_eventObject)) {
            case 'Admin_Event_AddAccount':
                $this->createPersonalFolder($_eventObject->account);
                break;
            case 'Tinebase_Event_User_DeleteAccount':
                /**
                 * @var Tinebase_Event_User_DeleteAccount $_eventObject
                 */
                if ($_eventObject->deletePersonalContainers()) {
                    $this->deletePersonalFolder($_eventObject->account);
                }
                break;
        }
    }
        
    /**
     * creates the initial folder for new accounts
     *
     * @param mixed[int|Tinebase_Model_User] $_account   the accountd object
     * @return Tinebase_Record_RecordSet of subtype Tinebase_Model_Tree_Node
     */
    public function createPersonalFolder($_account)
    {
         $account = (! $_account instanceof Tinebase_Model_User)
            ? Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $_account)
            : $_account;
        $translation = Tinebase_Translation::getTranslation('Filemanager');
        $nodeName = sprintf($translation->_("%s's personal files"), $account->accountFullName);
        $path = '/Filemanager/folders/personal/' . $account->getId() . '/' . $nodeName;
        $personalNode = Tinebase_FileSystem::getInstance()->createAclNode($path);

        $container = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array($personalNode));

        return $container;
    }
}
