<?php
/**
 * Course controller for Courses application
 * 
 * @package     Courses
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Course controller class for Courses application
 * 
 * @package     Courses
 * @subpackage  Controller
 */
class Courses_Controller_Course extends Tinebase_Controller_Record_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Courses';
    
    /**
     * Model name
     *
     * @var string
     * 
     * @todo perhaps we can remove that and build model name from name of the class (replace 'Controller' with 'Model')
     */
    protected $_modelName = 'Courses_Model_Course';
    
    /**
    * the groups controller
    *
    * @var Admin_Controller_Group
    */
    protected $_groupController = NULL;
    
    /**
    * the groups controller
    *
    * @var Admin_Controller_User
    */
    protected $_userController = NULL;
    
    /**
     * config of courses
     *
     * @var Zend_Config
     */
    protected $_config = NULL;
    
    /**
     * delete or just set is_delete=1 if record is going to be deleted
     * - legacy code -> remove that when all backends/applications are using the history logging
     *
     * @var boolean
     */
    protected $_purgeRecords = TRUE;
    
    /**
     * check for container ACLs?
     *
     * @var boolean
     */
    protected $_doContainerACLChecks = false;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_backend = new Courses_Backend_Course();
        $this->_currentAccount = Tinebase_Core::getUser();
        $this->_config = Courses_Config::getInstance();
        $this->_groupController = Admin_Controller_Group::getInstance();
        $this->_userController = Admin_Controller_User::getInstance();
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var Courses_Controller_Course
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Courses_Controller_Course
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Courses_Controller_Course();
        }
        
        return self::$_instance;
    }        

    /****************************** overwritten functions ************************/    
    
    /**
     * save course with corresponding group
     * 
     * @param Courses_Model_Course $course
     * @param Tinebase_Model_Group $group
     * @return Courses_Model_Course
     * 
     * @todo this should be moved to normal create/update (inspection) functions
     */
    public function saveCourseAndGroup(Courses_Model_Course $course, Tinebase_Model_Group $group)
    {
        $i18n = Tinebase_Translation::getTranslation('Courses');
        $groupNamePrefix = $i18n->_('Course');
        
        $groupNamePrefix = is_array($groupNamePrefix) ? $groupNamePrefix[0] : $groupNamePrefix;
        $group->name = $groupNamePrefix . '-' . $course->name;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Saving course ' . $course->name . ' with group ' . $group->name);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($group->toArray(), true));
        
        if (empty($group->id)) {
            $savedGroup         = $this->_groupController->create($group);
            $course->group_id   = $savedGroup->getId();
            $savedRecord        = $this->create($course);
        } else {
            $savedRecord      = $this->update($course);
            $currentMembers   = $this->_groupController->getGroupMembers($course->group_id);
            $newCourseMembers = array_diff((array)$group->members, $currentMembers);
            $this->addCourseMembers($course, $newCourseMembers);
        
            $deletedAccounts  = array_diff($currentMembers, (array)$group->members);
            // delete members which got removed from course
            $this->_userController->delete($deletedAccounts);
        }
        
        $groupMembers = Tinebase_Group::getInstance()->getGroupMembers($course->group_id);
        // add/remove members to/from internet/fileserver group
        if (! empty($groupMembers)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Found ' . count($groupMembers) . ' group members');
            $this->_manageAccessGroups($groupMembers, $savedRecord->internet);
            // $this->_manageAccessGroups($group->members, $savedRecord->fileserver, 'fileserver');
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No group members found.');
        }
        
        return $savedRecord;
    }
    
    /**
    * add or remove members from internet/fileserver groups
    *
    * @param array $_members array of member ids
    * @param boolean $_access yes/no
    * @param string $_type
    * 
    * @todo should be moved to inspectAfter*
    * @todo allow fileserver group management, too
    */
    protected function _manageAccessGroups(array $_members, $_access, $_type = 'internet')
    {
        $configField = $_type . '_group';
        $secondConfigField = $configField;
        if ($_access === 'FILTERED') {
            $configField .= '_filtered';
        } else {
            $secondConfigField .= '_filtered';
        }
    
        if (! isset($this->_config->{$configField})) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No config found for ' . $configField);
            return;
        }
    
        $groupId = $this->_config->{$configField};
        $secondGroupId = ($_type === 'internet' && isset($this->_config->{$secondConfigField})) ? $this->_config->{$secondConfigField} : NULL;
    
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Setting $_type to $_access for " . print_r($_members, true));
    
        // add or remove members to or from internet/fileserver groups
        foreach ($_members as $memberId) {
            if ($_access === 'ON' || $_access === 'FILTERED') {
                $this->_groupController->addGroupMember($groupId, $memberId);
                if ($secondGroupId) {
                    $this->_groupController->removeGroupMember($secondGroupId, $memberId);
                }
            } else if ($_access === 'OFF') {
                $this->_groupController->removeGroupMember($groupId, $memberId);
                if ($secondGroupId) {
                    $this->_groupController->removeGroupMember($secondGroupId, $memberId);
                }
            }
        }
    }
    
    /**
    * inspect creation of one record (after create)
    *
    * @param   Tinebase_Record_Interface $_createdRecord
    * @param   Tinebase_Record_Interface $_record
    * @return  void
    */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        $course = $_createdRecord;
        
        // add teacher account
        $i18n = Tinebase_Translation::getTranslation('Courses');
        
        $courseName = strtolower($course->name);
        $loginName  = strtolower($i18n->_('teacher') . '-' . $course->name);
        $schoolName = strtolower(Tinebase_Department::getInstance()->get($course->type)->name);
        
        $account = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => $loginName,
            'accountLoginShell'     => '/bin/false',
            'accountStatus'         => 'enabled',
            'accountPrimaryGroup'   => $course->group_id,
            'accountLastName'       => $i18n->_('Teacher'),
            'accountDisplayName'    => $course->name . ' ' .  $i18n->_('Teacher Account'),
            'accountFirstName'      => $course->name,
            'accountExpires'        => NULL,
            'accountEmailAddress'   => (isset($this->_config->domain) && !empty($this->_config->domain)) ? $loginName . '@' . $this->_config->domain : '',
            'accountHomeDirectory'  => (isset($this->_config->basehomedir)) ? $this->_config->basehomedir . $schoolName . '/'. $courseName . '/' . $loginName : '',
        ));
        
        if (isset($this->_config->samba)) {
            $samUser = new Tinebase_Model_SAMUser(array(
                'homePath'    => $this->_config->samba->basehomepath . $loginName,
                'homeDrive'   => $this->_config->samba->homedrive,
                'logonScript' => $courseName . $this->_config->samba->logonscript_postfix_teacher,
                'profilePath' => $this->_config->samba->baseprofilepath . $schoolName . '\\' . $courseName . '\\' . $loginName
            ));
        
            $account->sambaSAM = $samUser;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Created teacher account for course '
        . $course->name . ': ' . print_r($account->toArray(), true));
        
        #$event = new Courses_Event_BeforeAddTeacher($account, $course);
        #Tinebase_Event::fireEvent($event);
        
        $password = $this->_config->get('teacher_password', $account->accountLoginName);
            $account = $this->_userController->create($account, $password, $password);
        
        // add to teacher group if available
        if (isset($this->_config->teacher_group) && !empty($this->_config->teacher_group)) {
            $this->_groupController->addGroupMember($this->_config->teacher_group, $account->getId());
        }
        
        // add to students group if available
        if (isset($this->_config->students_group) && !empty($this->_config->students_group)) {
            $this->_groupController->addGroupMember($this->_config->students_group, $account->getId());
        }
    }
    
    /**
     * Deletes a set of records.
     * 
     * If one of the records could not be deleted, no record is deleted
     * 
     * @param   array array of record identifiers
     * @return  void
     * @throws Tinebase_Exception_NotFound|Tinebase_Exception
     */
    public function delete($_ids)
    {
        $courses = $this->getMultiple($_ids);
        
        $groupsToDelete = array();
        $usersToDelete = array();
        
        foreach ($courses as $course) {
            $groupsToDelete[] = $course->group_id;
            $usersToDelete = array_merge($usersToDelete, $this->_groupController->getGroupMembers($course->group_id));
        }
        
        Courses_Controller::getInstance()->suspendEvents();
        
        $this->_userController->delete(array_unique($usersToDelete));
        $this->_groupController->delete(array_unique($groupsToDelete));
        
        Courses_Controller::getInstance()->resumeEvents();
        
        parent::delete($_ids);
    }
    
    /**
     * add exitings accounts to course
     * 
     * @param  string  $_courseId
     * @param  array   $_members
     */
    public function addCourseMembers($_courseId, array $_members = array())
    {
        $course = $_courseId instanceof Courses_Model_Course ? $_courseId : $this->get($_courseId);
        
        $tinebaseUser  = Tinebase_User::getInstance();
        $tinebaseGroup = Tinebase_Group::getInstance();
        
        $courseName = strtolower($course->name);
        $schoolName = strtolower(Tinebase_Department::getInstance()->get($course->type)->name);
        
        foreach ($_members as $userId) {
            $userId = (is_array($userId)) ? $userId['id'] : $userId;
            $user = $tinebaseUser->getFullUserById($userId);
            
            $tinebaseGroup->removeGroupMember($user->accountPrimaryGroup, $user);
            
            $user->accountPrimaryGroup  = $course->group_id;
            $user->accountHomeDirectory = (isset($this->_config->basehomedir)) ? $this->_config->basehomedir . $schoolName . '/'. $courseName . '/' . $user->accountLoginName : '';
            
            if (isset($user->sambaSAM)) {
                $sambaSAM = $user->sambaSAM;
                
                $sambaSAM->homePath    = $this->_config->samba->basehomepath . $user->accountLoginName;
                $sambaSAM->logonScript = $courseName . $this->_config->samba->logonscript_postfix_member;
                $sambaSAM->profilePath = $this->_config->samba->baseprofilepath . $schoolName . '\\' . $courseName . '\\' . $user->accountLoginName;
                
                $user->sambaSAM = $sambaSAM;
            }
            
            $tinebaseUser->updateUser($user);
            
            $tinebaseGroup->addGroupMember($user->accountPrimaryGroup, $user);
            
            if (isset($this->_config->students_group) && !empty($this->_config->students_group)) {
                $tinebaseGroup->addGroupMember($this->_config->students_group, $user);
            }
        }
    }
}
