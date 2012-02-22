<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * contact controller for Addressbook
 *
 * @package     Addressbook
 * @subpackage  Controller
 */
class Addressbook_Controller_List extends Tinebase_Controller_Record_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Addressbook';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Addressbook_Model_List';

    
    /**
     * @todo why is this needed???
     */
    protected $_omitModLog = true;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_backend = new Addressbook_Backend_List();
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
     * holds the instance of the singleton
     *
     * @var Addressbook_Controller_List
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Addressbook_Controller_List
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Addressbook_Controller_List();
        }
        
        return self::$_instance;
    }
    
    /**
     * add new members to list
     * 
     * @param  mixed  $_listId
     * @param  mixed  $_newMembers
     * @return Addressbook_Model_List
     */
    public function addListMember($_listId, $_newMembers)
    {
        try {
            $list = $this->get($_listId);
        } catch (Tinebase_Exception_AccessDenied $tead) {
            $list = $this->_fixEmptyContainerId($_listId);
            $list = $this->get($_listId);
        }
        
        $this->_checkGrant($list, 'update', TRUE, 'No permission to add list member.');
        
        $list = $this->_backend->addListMember($_listId, $_newMembers);
        
        return $list;
    }
    
    /**
     * fixes empty container ids / perhaps this can be removed later as all lists should have a container id!
     * 
     * @param  mixed  $_listId
     * @return Addressbook_Model_List
     */
    protected function _fixEmptyContainerId($_listId)
    {
        $list = $this->_backend->get($_listId);
        
        if (empty($list->container_id)) {
            $list->container_id = $this->_getDefaultInternalAddressbook();
            $list = $this->_backend->update($list);
        }
        
        return $list;
    }
    
    /**
     * get default internal adb id
     * 
     * @return string
     */
    protected function _getDefaultInternalAddressbook()
    {
        $appConfigDefaults = Admin_Controller::getInstance()->getConfigSettings();
        return $appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK];
    }
    
    /**
     * remove members from list
     * 
     * @param  mixed  $_listId
     * @param  mixed  $_newMembers
     * @return Addressbook_Model_List
     */
    public function removeListMember($_listId, $_newMembers)
    {
        $list = $this->get($_listId);
        
        $this->_checkGrant($list, 'update', TRUE, 'No permission to remove list member.');
        
        $list = $this->_backend->removeListMember($_listId, $_newMembers);
        
        return $list;
    }
    
    /**
     * delete one record
     * - don't delete if it belongs to an user account
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Addressbook_Exception_AccessDenied
     */
    protected function _deleteRecord(Tinebase_Record_Interface $_record)
    {
        #if (!empty($_record->account_id)) {
        #    throw new Addressbook_Exception_AccessDenied('It is not allowed to delete a contact linked to an user account!');
        #}
        
        parent::_deleteRecord($_record);
    }
    
    /**
     * inspect creation of one record
     * 
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        if (isset($record->type) &&  $record->type == Addressbook_Model_List::LISTTYPE_GROUP) {
            throw new Addressbook_Exception_InvalidArgument('can not add list of type ' . Addressbook_Model_List::LISTTYPE_GROUP);
        }
    }
    
    /**
     * inspect update of one record
     * 
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        if (isset($record->type) &&  $record->type == Addressbook_Model_List::LISTTYPE_GROUP) {
            throw new Addressbook_Exception_InvalidArgument('can not add list of type ' . Addressbook_Model_List::LISTTYPE_GROUP);
        }
    }
    
    /**
     * create or update list in addressbook sql backend
     * 
     * @param  Tinebase_Model_Group  $group
     * @return Addressbook_Model_List
     */
    public function createOrUpdateByGroup(Tinebase_Model_Group $group)
    {
        if (empty($group->container_id)) {
            $group->container_id = $this->_getDefaultInternalAddressbook();
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($group->toArray(), TRUE));
        
        try {
            if (empty($group->list_id)) {
        
                $filter = new Addressbook_Model_ListFilter(array(
                    array('field' => 'name', 'operator' => 'equals', 'value' => $group->name),
                    array('field' => 'type', 'operator' => 'equals', 'value' => Addressbook_Model_List::LISTTYPE_GROUP)
                ));
        
                $existingLists = $this->_backend->search($filter);
        
                if (count($existingLists) == 0) {
                    // jump to catch block => no list_id provided and no existing list for group found
                    throw new Tinebase_Exception_NotFound('list_id is empty');
                }
                $group->list_id = $existingLists[0]->id;
            }
        
            $list = $this->_backend->get($group->list_id);
        
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Update list ' . $group->name);
        
            $list->name         = $group->name;
            $list->description  = $group->description;
            $list->email        = $group->email;
            $list->type         = Addressbook_Model_List::LISTTYPE_GROUP;
            $list->container_id = $group->container_id;
            $list->members      = $this->_getContactIds($group->members);
        
            // add modlog info
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($list, 'update');
        
            $list = $this->_backend->update($list);
        
        } catch (Tinebase_Exception_NotFound $tenf) {
            $list = new Addressbook_Model_List(array(
                'name'          => $group->name,
                'description'   => $group->description,
                'email'         => $group->email,
                'type'          => Addressbook_Model_List::LISTTYPE_GROUP,
                'container_id'  => $group->container_id,
                'members'       => $this->_getContactIds($group->members)
            ));
        
            // add modlog info
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($list, 'create');
        
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Add new list ' . $group->name);
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($list->toArray(), TRUE));
        
            $list = $this->_backend->create($list);
        }
        
        return $list;
    }
    
    /**
    * get contact_ids of users
    *
    * @param  array  $_userIds
    * @return array
    */
    protected function _getContactIds($_userIds)
    {
        $contactIds = array();
    
        if (empty($_userIds)) {
            return $contactIds;
        }
    
        foreach ($_userIds as $userId) {
            $fullUser = Tinebase_User::getInstance()->getUserById($userId, 'Tinebase_Model_FullUser');
            if (!empty($fullUser->contact_id)) {
                $contactIds[] = $fullUser->contact_id;
            }
        }
    
        return $contactIds;
    }
}
