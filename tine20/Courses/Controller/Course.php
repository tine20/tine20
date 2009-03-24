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
class Courses_Controller_Course extends Tinebase_Application_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {        
        $this->_applicationName = 'Courses';
        $this->_backend = new Courses_Backend_Course();
        $this->_modelName = 'Courses_Model_Course';
        $this->_currentAccount = Tinebase_Core::getUser();   
        $this->_purgeRecords = FALSE;
        $this->_doContainerACLChecks = FALSE;
    }    
    
    /**
     * holdes the instance of the singleton
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
        if (! isset(Tinebase_Core::getConfig()->courses)) {
            Tinebase_Core::getConfig()->courses = new Zend_Config(array());
        }
        
        $record = parent::create($_record);
        
        // add teacher account
        $i18n = Tinebase_Translation::getTranslation('Courses');
        $loginname = $record->name . '-' . $i18n->_('teacher');
        
        $account = new Tinebase_Model_FullUser(array(
            'accountLoginName'      => $loginname,
            'accountStatus'         => 'enabled',
            'accountPrimaryGroup'   => $record->group_id,
            'accountLastName'       => $i18n->_('Teacher'),
            'accountDisplayName'    => $record->name . ' ' .  $i18n->_('Teacher Account'),
            'accountFirstName'      => $record->name,
            'accountExpires'        => NULL,
            'accountEmailAddress'   => NULL,
        ));
        
        $event = new Courses_Event_BeforeAddTeacher($account, $record);
        Tinebase_Events::fireEvent($event);
        
        $password = Tinebase_Core::getConfig()->courses->get('teacher_password', $account->accountLoginName);
        $account = Admin_Controller_User::getInstance()->create($account, $password, $password);
        
        // add to teacher group if available
        if (isset(Tinebase_Core::getConfig()->courses->teacher_group) && !empty(Tinebase_Core::getConfig()->courses->teacher_group)) {
            Admin_Controller_Group::getInstance()->addGroupMember(Tinebase_Core::getConfig()->courses->teacher_group, $account->getId());
        }
        
        return $record;
    }
}
