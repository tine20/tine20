<?php
/**
 * Tine 2.0
 * @package     Courses
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 *
 * This class handles all Json requests for the Courses application
 *
 * @package     Courses
 * @subpackage  Frontend
 */
class Courses_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * the controller
     *
     * @var Courses_Controller_Course
     */
    protected $_controller = NULL;

    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'Courses';
        $this->_controller = Courses_Controller_Course::getInstance();
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
        $groupData = Admin_Controller_Group::getInstance()->get($_record->group_id)->toArray();
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
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * 
     * @return array data
     */
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records, $_filter = NULL, $_pagination = NULL)
    {
        $result = parent::_multipleRecordsToJson($_records, $_filter, $_pagination);
        
        // get groups + types (departments) and merge data
        $groupIds = $_records->group_id;
        $groups = Tinebase_Group::getInstance()->getMultiple(array_unique(array_values($groupIds)));
        $knownTypes = Tinebase_Department::getInstance()->search(new Tinebase_Model_DepartmentFilter());
        
        foreach ($result as &$course) {
            
            $groupIdx = $groups->getIndexById($course['group_id']);
            if ($groupIdx !== FALSE) {
                $group = $groups[$groupIdx]->toArray();
                unset($group['id']);
                $course = array_merge($group, $course);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Group with ID ' . $course['group_id'] . ' does not exist.');
            }
            
            $typeIdx = $knownTypes->getIndexById($course['type']);
            if ($typeIdx !== FALSE) {
                //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($knownTypes[$typeIdx]->toArray(), true));
                $course['type'] = $knownTypes[$typeIdx]->toArray();
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Department with ID ' . $course['type'] . ' does not exist.');
                $course['type'] = array(
                    'id' => $course['type'], 
                    'name' => $course['type']
                );
            }
        }
        
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
            
            // get full user for login name
            $fullUser = Tinebase_User::getInstance()->getFullUserById($member['id']);
            
            $result[] = array(
                'id'    => $member['id'],
                'name'  => $member['name'],
                'data'  => $fullUser->accountLoginName,
                'type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
            );
        }
        
        return $result;
    }
    
    /************************************** public API **************************************/
    
    /**
     * Returns registry data of the application.
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        $courseTypes = Tinebase_Department::getInstance()->search(new Tinebase_Model_DepartmentFilter());
        
        $defaultType = count($courseTypes) > 0 ? $courseTypes[0]->getId() : '';
        
        $result = array(
            'defaultType' => array(
                'value' => $defaultType,
                'records' => $this->searchCourseTypes(NULL, NULL)
            )
        );
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' result: ' . print_r($result, true));
        
        return $result;
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
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
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveCourse($recordData)
    {
        // create course and group from json data
        $course = new Courses_Model_Course(array(), TRUE);
        $course->setFromJsonInUsersTimezone($recordData);
        $group = new Tinebase_Model_Group(array(), TRUE);
        $group->setFromJsonInUsersTimezone($recordData);
        
        $savedRecord = $this->_controller->saveCourseAndGroup($course, $group);

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
        return $this->_delete($ids, $this->_controller);
    }

    /**
     * import course members
     *
     * @param string $tempFileId
     * @param string $groupId
     * @param string $courseName
     * 
     * @todo remove obsolete $groupId param
     */
    public function importMembers($tempFileId, $groupId, $courseId)
    {
        $this->_controller->importMembers($tempFileId, $courseId);
        
        // return members to update members grid
        return array(
            'results'   => $this->_getCourseMembers($groupId),
            'status'    => 'success'
        );
    }
    
    /**
     * add new member to course
     * 
     * @param array $userData
     * @param array $courseData
     * @return array
     * 
     * @todo generalize type (value) sanitizing
     */
    public function addNewMember($userData, $courseData)
    {
        $course = new Courses_Model_Course(array(), TRUE);
        if (isset($courseData['type']['value'])) {
            $courseData['type'] = $courseData['type']['value'];
        }
        $course->setFromJsonInUsersTimezone($courseData);
        $user = new Tinebase_Model_FullUser(array(
            'accountFirstName' => $userData['accountFirstName'],
            'accountLastName' => $userData['accountLastName'],
        ), TRUE);
        
        $this->_controller->createNewMember($course, $user);

        // return members to update members grid
        return array(
            'results'   => $this->_getCourseMembers($course->group_id),
            'status'    => 'success'
        );
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchCourseTypes($filter, $paging)
    {
        $result = Tinebase_Department::getInstance()->search(new Tinebase_Model_DepartmentFilter())->toArray();
        
        return array(
            'results'       => $result,
            'totalcount'    => count($result),
            'filter'        => $filter,
        );
    }
    
    /**
     * reset password for given account
     * - call Admin_Frontend_Json::resetPassword()
     *
     * @param  array   $account data of Tinebase_Model_FullUser or account id
     * @param  string  $password the new password
     * @param  bool    $mustChange
     * @return array
     */
    public function resetPassword($account, $password, $mustChange)
    {
        $adminJson = new Admin_Frontend_Json();
        return $adminJson->resetPassword($account, $password, (bool)$mustChange);
    }
}
