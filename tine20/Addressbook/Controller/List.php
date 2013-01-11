<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
    private function __construct()
    {
        $this->_backend = new Addressbook_Backend_List();
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
     * (non-PHPdoc)
     * @see Tinebase_Controller_Record_Abstract::get()
     */
    public function get($_id, $_containerId = NULL)
    {
        $result = new Tinebase_Record_RecordSet('Addressbook_Model_List', array(parent::get($_id, $_containerId)));
        $this->_removeHiddenListMembers($result);
        return $result->getFirstRecord();
    }
    
    /**
     * use contact search to remove hidden list members
     * 
     * @param Tinebase_Record_RecordSet $lists
     */
    protected function _removeHiddenListMembers($lists)
    {
        if (count($lists) === 0) {
            return;
        }
        
        $allMemberIds = array();
        foreach ($lists as $list) {
            $allMemberIds = array_merge($list->members, $allMemberIds);
        }
        $allMemberIds = array_unique($allMemberIds);
        
        if (empty($allMemberIds)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' No members found.');
            return;
        }
        
        $allVisibleMemberIds = Addressbook_Controller_Contact::getInstance()->search(new Addressbook_Model_ContactFilter(array(array(
            'field'    => 'id',
            'operator' => 'in',
            'value'    => $allMemberIds
        ))), NULL, FALSE, TRUE);
        
        $hiddenMemberids = array_diff($allMemberIds, $allVisibleMemberIds);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Found ' . count($hiddenMemberids) . ' hidden members, removing them');
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . print_r($hiddenMemberids, TRUE));
        
        foreach ($lists as $list) {
            $list->members = array_diff($list->members, $hiddenMemberids);
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Controller_Record_Abstract::search()
     */
    public function search(Tinebase_Model_Filter_FilterGroup $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL, $_getRelations = FALSE, $_onlyIds = FALSE, $_action = 'get')
    {
        $result = parent::search($_filter, $_pagination, $_getRelations, $_onlyIds, $_action);
        $this->_removeHiddenListMembers($result);
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Controller_Record_Abstract::getMultiple()
     */
    public function getMultiple($_ids, $_ignoreACL = FALSE)
    {
        $result = parent::getMultiple($_ids, $_ignoreACL);
        $this->_removeHiddenListMembers($result);
        return $result;
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
        
        return $this->get($list->getId());
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
        $result = (isset($appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK])) ? $appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK] : NULL;
        
        if (empty($result)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                . ' Default internal addressbook not found. Creating new config setting.');
            $result = Addressbook_Setup_Initialize::setDefaultInternalAddressbook()->getId();
        }
        return $result;
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
        
        return $this->get($list->getId());
    }
    
    /**
     * inspect creation of one record
     * 
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        if (isset($record->type) && $record->type == Addressbook_Model_List::LISTTYPE_GROUP) {
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
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($group->toArray(), TRUE));
        
        try {
            if (empty($group->list_id)) {
                $list = $this->_backend->getByGroupName($group->name);
                if (! $list) {
                    // jump to catch block => no list_id provided and no existing list for group found
                    throw new Tinebase_Exception_NotFound('list_id is empty');
                }
                $group->list_id = $list->getId();
            } else {
                $list = $this->_backend->get($group->list_id);
            }
        
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Update list ' . $group->name);
        
            $list->name         = $group->name;
            $list->description  = $group->description;
            $list->email        = $group->email;
            $list->type         = Addressbook_Model_List::LISTTYPE_GROUP;
            $list->container_id = (empty($group->container_id)) ? $this->_getDefaultInternalAddressbook() : $group->container_id;
            $list->members      = (isset($group->members)) ? $this->_getContactIds($group->members) : array();
        
            // add modlog info
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($list, 'update');
        
            $list = $this->_backend->update($list);
            $list = $this->get($list->getId());
        
        } catch (Tinebase_Exception_NotFound $tenf) {
            $list = $this->createByGroup($group);
        }
        
        return $list;
    }
    
    /**
     * create new list by group
     * 
     * @param Tinebase_Model_Group $group
     * @return Addressbook_Model_List
     */
    public function createByGroup($group)
    {
        $list = new Addressbook_Model_List(array(
            'name'          => $group->name,
            'description'   => $group->description,
            'email'         => $group->email,
            'type'          => Addressbook_Model_List::LISTTYPE_GROUP,
            'container_id'  => (empty($group->container_id)) ? $this->_getDefaultInternalAddressbook() : $group->container_id,
            'members'       => (isset($group->members)) ? $this->_getContactIds($group->members) : array(),
        ));
        
        // add modlog info
        Tinebase_Timemachine_ModificationLog::setRecordMetaData($list, 'create');
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Add new list ' . $group->name);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
            . ' ' . print_r($list->toArray(), TRUE));
        
        $list = $this->_backend->create($list);
        
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
    
    /**
    * you can define default filters here
    *
    * @param Tinebase_Model_Filter_FilterGroup $_filter
    */
    protected function _addDefaultFilter(Tinebase_Model_Filter_FilterGroup $_filter = NULL)
    {
        if (! $_filter->isFilterSet('showHidden')) {
            $hiddenFilter = $_filter->createFilter('showHidden', 'equals', FALSE);
            $hiddenFilter->setIsImplicit(TRUE);
            $_filter->addFilter($hiddenFilter);
        }
    }
}
