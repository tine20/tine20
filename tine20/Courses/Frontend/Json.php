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
        
        if (! isset(Tinebase_Core::getConfig()->courses)) {
            Tinebase_Core::getConfig()->courses = new Zend_Config(array());
        }
        
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
        
        // course type
        $recordArray['type'] = array(
            'value' => $recordArray['type'],
            'records' => $this->searchCourseTypes(NULL, NULL)
        );
        return array_merge($groupData, $recordArray);
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
        
        $knownTypes = Tinebase_Core::getConfig()->courses->get('course_types', array())->toArray();
        
        // get groups and merge data
        foreach ($result as &$course) {
            $group = Tinebase_Group::getInstance()->getGroupById($course['group_id'])->toArray();
            unset($group['id']);
            $course = array_merge($group, $course);
            
            $course['type'] = array_key_exists($course['type'], $knownTypes) ? 
                array('id' => $course['type'], 'name' => $knownTypes[$course['type']]) : 
                array('id' => $course['type'], 'name' => $course['type']);
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
    
    /**
     * add or remove members from internet group
     *
     * @param array $_members array of member ids
     * @param boolean $_internet yes/no
     */
    protected function _manageInternetGroup(array $_members, $_internet)
    {
        if (!isset(Tinebase_Core::getConfig()->courses) || !isset(Tinebase_Core::getConfig()->courses->internet_group)) {
            return;
        }

        $inetGroupId = Tinebase_Core::getConfig()->courses->internet_group;
        $groupController = Admin_Controller_Group::getInstance();
        
        // add or remove members to or from internet group (defined in config.inc.php)
        foreach ($_members as $memberId) {
            if ($_internet) {
                $groupController->addGroupMember($inetGroupId, $memberId);
            } else {
                $groupController->removeGroupMember($inetGroupId, $memberId);
            }
        }
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
     * @todo move non api specific stuff to controller!
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
        
        $i18n = Tinebase_Translation::getTranslation('Courses');
        $groupNamePrefix = $i18n->_('Course') . '-';
        $group->name = $groupNamePrefix . $course->name;
        
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($group->toArray(), true));
        
        if (empty($group->id)) {
            $savedGroup = $this->_groupController->create($group);
            $course->group_id = $savedGroup->getId();
            $savedRecord = $this->_controller->create($course);
        } else {
            $currentMembers = $this->_groupController->getGroupMembers($course->group_id);
            $addedMembers = array_diff((array)$group->members, $currentMembers);
            $removedMembers = array_diff($currentMembers, (array)$group->members);
            
            $savedRecord = $this->_controller->update($course);
            $group->setId($course->group_id);
            $this->_groupController->update($group);
            
            // add new members to students group
            if (isset(Tinebase_Core::getConfig()->courses->students_group) && !empty(Tinebase_Core::getConfig()->courses->students_group)) {
                foreach ($addedMembers as $member) {
                    $this->_groupController->addGroupMember(Tinebase_Core::getConfig()->courses->students_group, $member['id']);
                }
            }
            
            // delte members wich got removed from course
            Admin_Controller_User::getInstance()->delete($removedMembers);
        }
        
        // add/remove members to/from internet group
        if (! empty($group->members)) {
            $this->_manageInternetGroup($group->members, $savedRecord->internet);
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
    public function importMembers($tempFileId, $groupId, $courseId)
    {
        $tempFileBackend = new Tinebase_TempFile();
        $tempFile = $tempFileBackend->getTempFile($tempFileId);
        
        $course = $this->_controller->get($courseId);
        
        // get definition and start import with admin user import csv plugin
        $definitionBackend = new Tinebase_ImportExportDefinition();
        
        $definition = $definitionBackend->getByProperty(Tinebase_Core::getConfig()->courses->get('import_definition', 'admin_user_import_csv'));
        $importer = new $definition->plugin(
            $definition, 
            Admin_Controller_User::getInstance(),
            array(
                'group_id'                  => $groupId,
                'accountLoginNamePrefix'    => $course->name . '-',
                'password'                  => $course->name,
                'course'                    => $course,
            )
        );
        $importer->import($tempFile->path);
        
        // return members to update members grid and add to student group
        $members = $this->_getCourseMembers($groupId);
        
        // add to student group if available
        if (isset(Tinebase_Core::getConfig()->courses->students_group) && !empty(Tinebase_Core::getConfig()->courses->students_group)) {
            $groupController = Admin_Controller_Group::getInstance(); 
            foreach ($members as $member) {
                $groupController->addGroupMember(Tinebase_Core::getConfig()->courses->students_group, $member['id']);
            }
        }
        
        return array(
            'results'   => $members,
            'status'    => 'success'
        );
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param string $filter json encoded
     * @param string $paging json encoded
     * @return array
     */
    public function searchCourseTypes($filter, $paging)
    {
        $result = array();
        foreach (Tinebase_Core::getConfig()->courses->get('course_types', array()) as $id => $name) {
            array_push($result, array(
                'id'   => $id,
                'name' => $name
            ));
        }
        
        return array(
            'results'       => $result,
            'totalcount'    => count($result),
            'filter'        => $filter,
        );
    }
}
