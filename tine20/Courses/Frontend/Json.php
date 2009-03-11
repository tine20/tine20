<?php
/**
 * Tine 2.0
 * @package     Courses
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Json.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 */

/**
 *
 * This class handles all Json requests for the Courses application
 *
 * @package     Courses
 * @subpackage  Frontend
 */
class Courses_Frontend_Json extends Tinebase_Application_Frontend_Json_Abstract
{
    /**
     * the controller
     *
     * @var Courses_Controller_Course
     */
    protected $_controller = NULL;

    /**
     * the groups controller
     *
     * @var Admin_Controller_Group
     */
    protected $_groupController = NULL;
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'Courses';
        $this->_controller = Courses_Controller_Course::getInstance();
        $this->_groupController = Admin_Controller_Group::getInstance();
    }
    
    /************************************** protected helper functions **********************/
    
    /**
     * returns task prepared for json transport
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     */
    protected function _recordToJson($_record)
    {
        $recordArray = parent::_recordToJson($_record);
        
        // group data
        $groupData = $this->_groupController->get($_record->group_id)->toArray();
        unset($groupData['id']);
        $groupData['members'] = $this->_getCourseMembers($_record->group_id);
        
        return array_merge($recordArray, $groupData);
    }
    
    /**
     * returns multiple records prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_records
     * @return array data
     * 
     * @todo add getMultiple to Group backends
     */
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records)
    {
        $result = parent::_multipleRecordsToJson($_records);
        
        // get groups and merge data
        foreach ($result as &$course) {
            $group = Tinebase_Group::getInstance()->getGroupById($course['group_id'])->toArray();
            unset($group['id']);
            $course = array_merge($group, $course);
        }
        
        // use this when get multiple is implemented
        /*
        $groupIds = $_records->group_id;
        $groups = Tinebase_Group::getInstance()->getMultiple(array_unique(array_values($groupIds)));
        foreach ($result as &$course) {
            $group = $groups[$groups->getIndexById($course['group_id'])]->toArray();
            unset($group['id']);
            $course = array_merge($group, $course);
        }
        */
        
        return $result;
    }
    
    /**
     * get course members
     *
     * @param int $_groupId
     * @return array
     */
    protected function _getCourseMembers($_groupId)
    {
        $adminJson = new Admin_Frontend_Json();
        $members = $adminJson->getGroupMembers($_groupId);
        
        $result = array();
        foreach ($members['results'] as $member) {
            $result[] = array(
                'id'    => $member['accountId'],
                'name'  => $member['accountDisplayName'],
                'type'  => 'user',
            );
        }
        
        return $result;
    }
    
    /************************************** public API **************************************/
    
    /**
     * Search for records matching given arguments
     *
     * @param string $filter json encoded
     * @param string $paging json encoded
     * @return array
     */
    public function searchCourses($filter, $paging)
    {
        return $this->_search($filter, $paging, $this->_controller, 'Courses_Model_CourseFilter');
    }     
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getCourse($id)
    {
        return $this->_get($id, $this->_controller);
    }

    /**
     * creates/updates a record
     *
     * @param  string $recordData
     * @return array created/updated record
     */
    public function saveCourse($recordData)
    {
        // create course and group from json data
        $course = new Courses_Model_Course(array(), TRUE);
        $course->setFromJsonInUsersTimezone($recordData);
        $group = new Tinebase_Model_Group(array(), TRUE);
        $group->setFromJsonInUsersTimezone($recordData);
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($group->toArray(), true));
        
        if (empty($group->id)) {
            $savedGroup = $this->_groupController->create($group);
            $course->group_id = $savedGroup->getId();
            $savedRecord = $this->_controller->create($course);
        } else {
            $savedRecord = $this->_controller->update($course);
            $group->setId($course->group_id);
            $this->_groupController->update($group);
        }

        return $this->_recordToJson($savedRecord);
    }
    
    /**
     * deletes existing records
     *
     * @param string $ids 
     * @return string
     */
    public function deleteCourses($ids)
    {
        $this->_delete($ids, $this->_controller);
    }    

    /**
     * import course members
     *
     * @param string $tempFileId
     * @param string $groupId
     * @param string $courseName
     */
    public function importMembers($tempFileId, $groupId, $courseName)
    {
        $tempFileBackend = new Tinebase_TempFile();
        $tempFile = $tempFileBackend->getTempFile($tempFileId);
                
        $definitionBackend = new Tinebase_ImportExportDefinition();
        $definition = $definitionBackend->getByProperty('admin_user_import_csv');
        $importer = new $definition->plugin(
            $definition, 
            Tinebase_User::factory(Tinebase_User::getConfiguredBackend()),
            array(
                'group_id'                  => $groupId,
                'accountLoginNamePrefix'    => $courseName . '_',
                'password'                  => $courseName
                //'encoding'                  => 'ISO8859-1'            
            )
        );
        $importer->import($tempFile->path);
        
        // return members to update members grid and add to student group
        $members = $this->_getCourseMembers($groupId);
        
        // add to student group if available
        if (isset(Tinebase_Core::getConfig()->courses)) {
            if (isset(Tinebase_Core::getConfig()->courses->students_group) && !empty(Tinebase_Core::getConfig()->courses->students_group)) {
                $groupBackend = Tinebase_Group::factory(Tinebase_User::getConfiguredBackend()); 
                foreach ($members as $member) {
                    $groupBackend->addGroupMember(Tinebase_Core::getConfig()->courses->students_group, $member['id']);
                }
            }
        }
        
        return array(
            'results'   => $members,
            'status'    => 'success'
        );
    }
}
