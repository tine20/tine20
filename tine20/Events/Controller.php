<?php
/**
 * Tine 2.0
 * 
 * @package     Events
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Events Controller (composite)
 * 
 * The Events 2.0 Controller manages access (acl) to the different backends and supports
 * a common interface to the servers/views
 * 
 * @package Events
 * @subpackage  Controller
 */
class Events_Controller extends Tinebase_Controller_Event implements Tinebase_Application_Container_Interface
{

    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'Events_Model_Event';
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'Events';
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
     * @var Events_Controller
     */
    private static $_instance = NULL;
    
    /**
     * singleton
     *
     * @return Events_Controller
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Events_Controller();
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
        $personalContainer = Tinebase_Container::getInstance()->createDefaultContainer(
            static::$_defaultModel,
            $this->_applicationName,
            $_accountId
        );
        $container = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($personalContainer));
        
        return $container;
    }
    
    /**
     * handler for Tinebase_Event_Container_BeforeCreate
     * - give owner of personal container all grants
     *
     * @param Tinebase_Event_Container_BeforeCreate $_eventObject
     */
    protected function _handleContainerBeforeCreateEvent(Tinebase_Event_Container_BeforeCreate $_eventObject)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->INFO(__METHOD__ . ' (' . __LINE__ . ') about to handle Tinebase_Event_Container_BeforeCreate' );
    
        if ($_eventObject->container &&
                $_eventObject->container->type === Tinebase_Model_Container::TYPE_PERSONAL &&
                $_eventObject->container->application_id === Tinebase_Application::getInstance()->getApplicationByName('Events')->getId() &&
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
                    Tinebase_Model_Grants::GRANT_ADMIN     => true
            ), TRUE));
        }
    }

    /**
     * creates a new Events container when department list is added/changed or removed
     *
     * @param Addressbook_Model_List $list
     * @param boolean $onlyDelete
     * @throws Exception
     */
    protected function _handleChangeListEvent(Addressbook_Model_List $list, $onlyDelete = false)
    {
        if ($list->type === Addressbook_Model_List::LISTTYPE_LIST) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " Only system groups are handled");
            return;
        }

        $foundRelation = false;
        $relations = Tinebase_Relations::getInstance()->getRelations('Addressbook_Model_List', 'Sql', $list->getId());

        foreach ($relations as $relation) {
            if ($relation->type == 'EVENTSCONTAINER') {
                $foundRelation = $relation;
            }
        }

        if ($onlyDelete || ! $this->_listIsDepartment($list)) {
            if ($foundRelation) {
                // check if old EVENTSCONTAINER grants needs to be removed
                $group = Tinebase_Group::getInstance()->getGroupById($list->group_id);
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . " Removing grants for group " . $group->name);

                $grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(
                    $this->_getAdminGroupGrants()
                ), TRUE);
                Tinebase_Container::getInstance()->setGrants($foundRelation->related_id, $grants);
            }
            return;
        }


        if (! $foundRelation) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . " Creating new events container for list ". $list->name);
            
            $group = Tinebase_Group::getInstance()->getGroupById($list->group_id);
            
            $grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array(
                array(
                    'account_id'      => $group->getId(),
                    'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
                    Tinebase_Model_Grants::GRANT_READ    => true,
                    Tinebase_Model_Grants::GRANT_ADD     => true,
                    Tinebase_Model_Grants::GRANT_EDIT    => true,
                    Tinebase_Model_Grants::GRANT_DELETE  => true,
                ),
                $this->_getAdminGroupGrants()
            ), TRUE);

            $container = Tinebase_Container::getInstance()->createSystemContainer('Events', $list->name, NULL, $grants);
            Tinebase_Relations::getInstance()->setRelations('Addressbook_Model_List', 'Sql', $list->getId(), array(array(
                'own_degree' => 'sibling',
                'related_degree' => 'sibling',
                'related_model' => 'Tinebase_Model_Container',
                'related_backend' => 'Sql',
                'related_id' => $container->getId(),
                'type' => 'EVENTSCONTAINER'
            )));
        }
    }

    protected function _getAdminGroupGrants()
    {
        return array(
            'account_id'      => Tinebase_Group::getInstance()->getDefaultAdminGroup()->getId(),
            'account_type'    => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP,
            Tinebase_Model_Grants::GRANT_READ    => true,
            Tinebase_Model_Grants::GRANT_ADD     => true,
            Tinebase_Model_Grants::GRANT_EDIT    => true,
            Tinebase_Model_Grants::GRANT_DELETE  => true,
            Tinebase_Model_Grants::GRANT_ADMIN   => true,
            Tinebase_Model_Grants::GRANT_EXPORT  => true,
            Tinebase_Model_Grants::GRANT_SYNC    => true,
        );
    }

    /**
     * @param Addressbook_Model_List $list
     * @return bool
     */
    protected function _listIsDepartment(Addressbook_Model_List $list)
    {
        return $list->list_type === 'DEPARTMENT';
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
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' ' . __LINE__
            . ' handle event of type ' . get_class($_eventObject));
        
        switch (get_class($_eventObject)) {
            case 'Admin_Event_AddAccount':
                $this->createPersonalFolder($_eventObject->account);
                break;
            case 'Tinebase_Event_Container_BeforeCreate':
                $this->_handleContainerBeforeCreateEvent($_eventObject);
                break;
            case 'Addressbook_Event_ChangeList':
                $this->_handleChangeListEvent($_eventObject->list);
                break;
            case 'Addressbook_Event_DeleteList':
                $this->_handleChangeListEvent($_eventObject->list, /* $onlyDelete */ true);
                break;
        }
    }
}
