<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * syncable group backend interface
 *
 * @package     Tinebase
 * @subpackage  Group
 */
interface Tinebase_Group_Interface_SyncAble
{
    /**
     * resolve groupid(for example ldap gidnumber) to uuid(for example ldap entryuuid)
     *
     * @param   string  $_groupId
     * 
     * @return  string  the uuid for groupid
     */
    public function resolveSyncAbleGidToUUid($_groupId);
    
    /**
     * get syncable group by id from sync backend
     * 
     * @param  mixed  $_groupId  the groupid
     * 
     * @return Tinebase_Model_Group
     */
    public function getGroupByIdFromSyncBackend($_groupId);

    /**
     * create a new group in sync backend
     *
     * @param  Tinebase_Model_Group  $_group
     * 
     * @return Tinebase_Model_Group
     */
    public function addGroupInSyncBackend(Tinebase_Model_Group $_group);
     
    /**
     * get groupmemberships of user from sync backend
     * 
     * @param   Tinebase_Model_User  $_user
     * 
     * @return  array  list of group ids
     */
    public function getGroupMembershipsFromSyncBackend($_userId);
        
    /**
     * get list of groups from syncbackend
     *
     * @param  string  $_filter
     * @param  string  $_sort
     * @param  string  $_dir
     * @param  int     $_start
     * @param  int     $_limit
     * 
     * @return Tinebase_Record_RecordSet with record class Tinebase_Model_Group
     */
    public function getGroupsFromSyncBackend($_filter = NULL, $_sort = 'name', $_dir = 'ASC', $_start = NULL, $_limit = NULL);
    
    /**
     * replace all current groupmembers with the new groupmembers list in sync backend
     *
     * @param  string  $_groupId
     * @param  array   $_groupMembers array of ids
     */
    public function setGroupMembersInSyncBackend($_groupId, $_groupMembers);
     
    /**
     * add a new groupmember to group in sync backend
     *
     * @param  mixed  $_groupId
     * @param  mixed  $_accountId string or user object
     */
    public function addGroupMemberInSyncBackend($_groupId, $_accountId);

    /**
     * remove one member from the group in sync backend
     *
     * @param  mixed  $_groupId
     * @param  mixed  $_accountId
     */
    public function removeGroupMemberInSyncBackend($_groupId, $_accountId);
    
    /**
     * updates an existing group in sync backend
     *
     * @param  Tinebase_Model_Group  $_group
     * 
     * @return Tinebase_Model_Group
     */
    public function updateGroupInSyncBackend(Tinebase_Model_Group $_group);
    
    /**
     * delete one or more groups in sync backend
     *
     * @param  mixed   $_groupId
     */
    public function deleteGroupsInSyncBackend($_groupId);
    
    /**
     * replace all current groupmemberships of user in sync backend
     *
     * @param  mixed  $_userId
     * @param  mixed  $_groupIds
     * 
     * @return array
     */
    public function setGroupMembershipsInSyncBackend($_userId, $_groupIds);
    
    /**
     * merges missing properties from existing sql group into group fetchted from sync backend
     * 
     * @param Tinebase_Model_Group $syncGroup
     * @param Tinebase_Model_Group $sqlGroup
     */
    public static function mergeMissingProperties($syncGroup, $sqlGroup);
}
