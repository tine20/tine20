<?php
/**
 * Course controller for Courses application
 * 
 * @package     Courses
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Category.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
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
    private function __construct() {        
        $this->_backend = new Courses_Backend_Course();
        $this->_currentAccount = Tinebase_Core::getUser();   
        $this->_config = isset(Tinebase_Core::getConfig()->courses) ? Tinebase_Core::getConfig()->courses : new Zend_Config(array());
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
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        $course = parent::create($_record);
        
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
        $account = Admin_Controller_User::getInstance()->create($account, $password, $password);
        
        // add to teacher group if available
        if (isset($this->_config->teacher_group) && !empty($this->_config->teacher_group)) {
            Admin_Controller_Group::getInstance()->addGroupMember($this->_config->teacher_group, $account->getId());
        }
        
        // add to students group if available
        if (isset($this->_config->students_group) && !empty($this->_config->students_group)) {
            Admin_Controller_Group::getInstance()->addGroupMember($this->_config->students_group, $account->getId());
        }
        
        return $course;
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
        
        $groupController = Admin_Controller_Group::getInstance();
        $userController = Admin_Controller_User::getInstance();
        
        $groupsToDelete = array();
        $usersToDelete = array();
        
        foreach ($courses as $course) {
            $groupsToDelete[] = $course->group_id;
            $usersToDelete = array_merge($usersToDelete, $groupController->getGroupMembers($course->group_id));
        }
        
        Courses_Controller::getInstance()->suspendEvents();
        
        $userController->delete(array_unique($usersToDelete));
        $groupController->delete(array_unique($groupsToDelete));
        
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
