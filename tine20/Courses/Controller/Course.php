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
            if (count($newCourseMembers) > 0) {
                $this->addCourseMembers($course, $newCourseMembers);
            }
        
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
        $teacherAccount = $this->_addNewTeacherAccount($_createdRecord);
        
        // add to teacher group if available
        if (isset($this->_config->teacher_group) && !empty($this->_config->teacher_group)) {
            $this->_groupController->addGroupMember($this->_config->teacher_group, $teacherAccount->getId());
        }
        
        // add to students group if available
        if (isset($this->_config->students_group) && !empty($this->_config->students_group)) {
            $this->_groupController->addGroupMember($this->_config->students_group, $teacherAccount->getId());
        }
    }
    
    /**
     * add new teacher account to course
     * 
     * @param Courses_Model_Course $course
     * @return Tinebase_Model_FullUser
     */
    protected function _addNewTeacherAccount($course)
    {
        $i18n = Tinebase_Translation::getTranslation('Courses');
        
        $courseName = strtolower($course->name);
        $loginName  = $this->_getTeacherLoginName($course, $i18n);
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
        
        $password = $this->_config->get('teacher_password', $account->accountLoginName);
        $account = $this->_userController->create($account, $password, $password);
        $this->_groupController->addGroupMember(Tinebase_Group::getInstance()->getDefaultGroup()->getId(), $account->getId());
        
        $this->_createDefaultFilterForTeacher($account, $course);
        
        return $account;
    }
    
    /**
     * create new teacher login name from course name
     * 
     * @param Courses_Model_Course $course
     * @param Zend_Translate $i18n
     * @return string
     */
    protected function _getTeacherLoginName($course, $i18n = NULL)
    {
        if ($i18n === NULL) {
            $i18n = Tinebase_Translation::getTranslation('Courses');
        }
        
        $loginname = $i18n->_('Teacher') . Courses_Config::getInstance()->get('teacher_login_name_delimiter', '-') . $course->name;
        return strtolower($loginname);
    }
    
    /**
     * create default favorite for teacher
     * 
     * @param Tinebase_Model_FullUser $account
     * @param Courses_Model_Course $course
     */
    protected function _createDefaultFilterForTeacher($account, $course)
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        $filter = $pfe->create(new Tinebase_Model_PersistentFilter(array(
            'account_id'        => $account->getId(),
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Courses')->getId(),
            'model'             => 'Courses_Model_CourseFilter',
            'name'              => "My course", // _("My course")
            'description'       => "My course",
            'filters'           => array(
                array(
                    'field'     => 'is_deleted',
                    'operator'  => 'equals',
                    'value'     => '0'
                ),
                array(
                    'field'     => 'name',
                    'operator'  => 'equals',
                    'value'     => $course->name
                ),
            ),
        )));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Created default filter for teacher '
            . $account->accountLoginName . ': ' . print_r($filter->toArray(), true));
        
        // set as default
        $pref = new Courses_Preference();
        $pref->setValueForUser(Courses_Preference::DEFAULTPERSISTENTFILTER, $filter->getId(), $account->getId(), TRUE);
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
     * add existings accounts to course
     * 
     * @param  string  $_courseId
     * @param  array   $_members
     * 
     * @todo use $user->applyOptionsAndGeneratePassword($this->_getNewUserConfig($course));
     */
    public function addCourseMembers($_courseId, array $_members = array())
    {
        $this->checkRight(Courses_Acl_Rights::ADD_EXISTING_USER);
        
        $course = $_courseId instanceof Courses_Model_Course ? $_courseId : $this->get($_courseId);
        
        $tinebaseUser  = Tinebase_User::getInstance();
        $tinebaseGroup = Tinebase_Group::getInstance();
        
        $courseName = strtolower($course->name);
        $schoolName = strtolower(Tinebase_Department::getInstance()->get($course->type)->name);
        
        foreach ($_members as $userId) {
            $userId = (is_array($userId)) ? $userId['id'] : $userId;
            $user = $tinebaseUser->getFullUserById($userId);
            
            $tinebaseGroup->removeGroupMember($user->accountPrimaryGroup, $user);
            
            if ($this->_config->get(Courses_Config::STUDENT_LOGINNAME_PREFIX, FALSE) && ($position = strrpos($user->accountLoginName, '-')) !== false) {
                $user->accountLoginName = $courseName . '-' . substr($user->accountLoginName, $position + 1);
            }
            
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
            $this->_addToStudentGroup(array($user->getId()));
        }
    }
    
    /**
     * add user ids to student group (if configured)
     * 
     * @param array $userIds
     */
    protected function _addToStudentGroup($userIds)
    {
        if (isset($this->_config->students_group) && !empty($this->_config->students_group)) {
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Adding ' . print_r($userIds, TRUE) . ' to students group (id ' . $this->_config->students_group . ')');
            
            foreach ($userIds as $id) {
                $this->_groupController->addGroupMember($this->_config->students_group, $id);
            }
        }
    }
    
    /**
    * import course members
    *
    * @param string $tempFileId
    * @param string $courseId
    * @return array
    */
    public function importMembers($tempFileId, $courseId)
    {
        $this->checkRight(Courses_Acl_Rights::ADD_NEW_USER);
        
        $tempFile = Tinebase_TempFile::getInstance()->getTempFile($tempFileId);
    
        // get definition and start import with admin user import csv plugin
        $definitionName = $this->_config->get(Courses_Config::STUDENTS_IMPORT_DEFINITION, 'admin_user_import_csv');
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Using import definition: ' . $definitionName);
        
        $definition = Tinebase_ImportExportDefinition::getInstance()->getByName($definitionName);
        
        $course = $this->get($courseId);
        // check if group exists, too
        $group = $this->_groupController->get($course->group_id);
        
        $importer = Admin_Import_Csv::createFromDefinition($definition, $this->_getNewUserConfig($course));
        $result = $importer->importFile($tempFile->path);
        
        $groupMembers = $this->_groupController->getGroupMembers($course->group_id);
        $this->_manageAccessGroups($groupMembers, $course->internet);
        $this->_addToStudentGroup($groupMembers);
        
        return $result;
    }
    
    /**
     * returns config for new users
     * 
     * @param Courses_Model_Course $course
     * @return array
     */
    protected function _getNewUserConfig(Courses_Model_Course $course)
    {
        $schoolName = strtolower(Tinebase_Department::getInstance()->get($course->type)->name);
        
        return array(
            'accountLoginNamePrefix'        => ($this->_config->get(Courses_Config::STUDENT_LOGINNAME_PREFIX, FALSE)) ? $course->name . '-' : '',
            'group_id'                      => $course->group_id,
            'accountEmailDomain'            => (isset($this->_config->domain)) ? $this->_config->domain : '',
            'accountHomeDirectoryPrefix'    => (isset($this->_config->basehomedir)) ? $this->_config->basehomedir . $schoolName . '/'. $course->name . '/' : '',
            'password'                      => strtolower($course->name),
            'course'                        => $course,
            'accountLoginShell'             => '/bin/false',
            'samba'                         => (isset($this->_config->samba)) ? array(
                'homePath'      => $this->_config->samba->basehomepath,
                'homeDrive'     => $this->_config->samba->homedrive,
                'logonScript'   => $course->name . $this->_config->samba->logonscript_postfix_member,
                'profilePath'   => $this->_config->samba->baseprofilepath . $schoolName . '\\' . $course->name . '\\',
                'pwdCanChange'  => new Tinebase_DateTime('@1'),
                'pwdMustChange' => new Tinebase_DateTime('@1')
            ) : array(),
        );
    }
    
    /**
     * add new member to course
     * 
     * @param Courses_Model_Course $course
     * @param Tinebase_Model_FullUser $user
     * @return Tinebase_Model_FullUser
     * 
     * @todo use importMembers() here to avoid duplication
     */
    public function createNewMember(Courses_Model_Course $course, Tinebase_Model_FullUser $user)
    {
        $this->checkRight(Courses_Acl_Rights::ADD_NEW_USER);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Creating new member for ' . $course->name);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' ' . print_r($course->toArray(), TRUE));
        
        $password = $user->applyOptionsAndGeneratePassword($this->_getNewUserConfig($course));
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' ' . print_r($user->toArray(), TRUE));
        $newMember = $this->_userController->create($user, $password, $password);
        
        // add to default group and manage access group for user
        $this->_groupController->addGroupMember(Tinebase_Group::getInstance()->getDefaultGroup()->getId(), $newMember->getId());
        $this->_manageAccessGroups(array($newMember->getId()), $course->internet);
        
        return $newMember;
    }
}
