<?php
/**
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Admin csv import groups class
 * 
 * @package     Admin
 * @subpackage  Import
 * 
 */
class Admin_Import_Group_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * @var Tinebase_Record_RecordSet $_userRecords
     */
    protected $_userRecords = NULL;
    
    /**
     * import single record (create password if in data)
     *
     * @param Tinebase_Record_Interface $_record
     * @param string $_resolveStrategy
     * @param array $_recordData
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_Record_Validation
     * @deprecated this needs rework, it better should not be used
     */
    protected function _importRecord($_record, $_resolveStrategy = NULL, $_recordData = array())
    {
        $admCfg = Tinebase_Core::getConfig()->get('Admin');
        $excludeGroups = array();
        
        $be = new Tinebase_Group_Sql();
        $members = explode(' ', $_record->members);
        
        $_record->members = null;
        unset($_record->members);
        
        $this->_setController();
        
        try {
            $group = $be->getGroupByName($_record->name);
        } catch (Tinebase_Exception_Record_NotDefined $e) {
            $group = NULL;
            parent::_importRecord($_record, $_resolveStrategy, $_recordData);
        }

        if ($group) {
            $this->_handleGroupMemberShip($group, $members);
        } else {
            $group = Admin_Controller_Group::getInstance()->get($_record->getId());
            /*$list = */Addressbook_Controller_List::getInstance()->createOrUpdateByGroup($group);
            $group->visibility = Tinebase_Model_Group::VISIBILITY_DISPLAYED;
            
            $be->updateGroupInSqlBackend($group);
            
            $memberUids = array();
            
            if (!empty($members) && $members[0] != "") {
                $users = $this->_resolveUsers($members);
                foreach($users as $userId) {
                    try {
                        $be->addGroupMember($_record->getId(), $userId);
                    } catch (Exception $e) {
                    }
                }
            }
        }
        return $group;
    }
    
    /**
     * resolves an array with usernames to an array of user ids
     * 
     * @param array $users
     */
    protected function _resolveUsers($users)
    {
        if (! $this->_userRecords) {
            $this->_userRecords = new Tinebase_Record_RecordSet('Tinebase_Model_User');
        }
        
        $resolved = array();
        
        if (is_array($users) && ! empty($users)) {
            foreach($users as $userName) {
                $user = $this->_userRecords->filter('name', $userName)->getFirstRecord();
                
                if (! $user) {
                    try {
                        $user = Tinebase_User::getInstance()->getUserByLoginName($userName);
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                                . ' Skipping user ' . $userName);
                        Tinebase_Exception::log($tenf);
                        continue;
                    }
                    $this->_userRecords->addRecord($user);
                }
                
                $resolved[] = $user->getId();
            }
        }
        
        return $resolved;
    }
    
    /**
     * 
     * @param Tinebase_Record_Interface $record
     * @param array $members
     */
    protected function _handleGroupMemberShip($record, $members)
    {
        $be = new Tinebase_Group_Sql();
        $group = $be->getGroupByName($record->name);
        
        $oldMembers = $be->getGroupMembers($group->getId());
        $newMembers = $this->_resolveUsers($members);
        
        foreach($oldMembers as $oldMember) {
            if (! in_array($oldMember, $newMembers)) {
                $be->removeGroupMember($record->getId(), $oldMember);
            }
        }
        
        foreach($newMembers as $newMember) {
            if (! in_array($newMember, $oldMembers)) {
                $be->addGroupMember($record->getId(), $newMember);
            }
        }
    }
    
    /**
     * overwrite (non-PHPdoc)
     * @see Tinebase_Import_Abstract::_handleTags()
     */
    protected function _handleTags($_record, $_resolveStrategy = NULL)
    {}
    
    /**
     * set controller
     */
    protected function _setController()
    {
        $this->_controller = Tinebase_Group::getInstance();
    }
}
