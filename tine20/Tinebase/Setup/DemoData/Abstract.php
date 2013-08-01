<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 *
 */

/**
 * Abstract class for DemoData
 *
 * @package     Tinebase
 * @subpackage  Setup
 */
abstract class Tinebase_Setup_DemoData_Abstract
{

    /**
     * the models to create demodata for
     * @var array
     */
    protected $_models = NULL;

    /**
     * the application name to work on
     * 
     * @var string
     */
    protected $_appName = NULL;
    
    /**
     * the name of the model currently worked on
     * 
     * @var string
     */
    protected $_modelName = NULL;
    /**
     * default ip for the fake session
     * @var string
     */
    protected $_defaultCliIp = '127.0.0.1';

    /**
     * holds the costcenter for "marketing"
     * 
     * @var Sales_Model_CostCenter
     */
    protected $_marketingCostCenter;
    
    /**
     * holds names of required applications to create demo data before this app
     * 
     * @var array
     */
    protected static $_requiredApplications;
    
    /**
     * holds the costcenter for "development"
     * 
     * @var Sales_Model_CostCenter
     */
    protected $_developmentCostCenter;
    
    /**
     * the personas to create demodata for
     * http://www.tine20.org/wiki/index.php/Personas
     * will be resolved to array of accounts
     * 
     * @var array
     */
    protected $_personas = array(
        'pwulf'    => 'Paul Wulf',
        'jsmith'   => 'John Smith',
        'sclever'  => 'Susan Clever',
        'jmcblack' => 'James McBlack',
        'rwright'  => 'Roberta Wright',
    );
    
    /**
     * the groups to create
     */
    protected $_groups = array(
        array(
            'groupData' => array(
                'visibility' => 'displayed', 'container_id' => 1, 'name' => 'Managers', 'description' => 'Managers of the company'
            ),
            'groupMembers' => array('pwulf')
        ),
        array(
            'groupData' => array(
                'visibility' => 'displayed', 'container_id' => 1, 'name' => 'HumanResources', 'description' => 'Human Resources Managment'
            ),
            'groupMembers' => 
                array('sclever', 'pwulf')
            ),
        array(
            'groupData' => array(
                'visibility' => 'displayed', 'container_id' => 1, 'name' => 'Secretary', 'description' => 'Secretarys of the company'
            ),
            'groupMembers' => array('sclever', 'pwulf')
        ),
        array(
            'groupData' => array(
                'visibility' => 'displayed', 'container_id' => 1, 'name' => 'Controllers', 'description' => 'Controllers of the company'
            ),
            'groupMembers' => array('rwright', 'pwulf')
        ),
    );

    /**
     * the roles to create for each group
     */
    protected $_roles = array(
        array(
            'roleData'    => array('name' => 'manager role'),
            'roleMembers' => array(
                array('type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, 'name' => 'Managers')
            ),
            'roleRights'  => array(
                'Admin' => array(
                    'view_accounts','run','view_apps','manage_shared_tags','view_access_log'
                ),
                'Addressbook' => array(
                    'manage_shared_contact_favorites','manage_shared_folders','run'
                ),
                'Tasks' => array(
                    'manage_shared_task_favorites','manage_shared_folders','run'
                ),
                'Crm' => array(
                    'manage_shared_lead_favorites','manage_shared_folders','manage_leads','run'
                ),
                'Filemanager' => array(
                    'manage_shared_folders','run'
                ),
                'Calendar' => array(
                    'manage_shared_event_favorites','manage_shared_folders','manage_resources','run'
                ),
                'Courses' => array(
                    'add_existing_user','add_new_user','run'
                ),
                'HumanResources' => array(
                    'edit_private','run'
                ),
                'Projects' => array(
                    'manage_shared_project_favorites','run'
                ),
                'Sales' => array(
                    'manage_products','run'
                ),
                'Sipgate' => array(
                    'manage_private_accounts','manage_shared_accounts','sync_lines','manage_accounts','run'
                ),
                'RequestTracker' => array(
                    'run'
                ),
                'Inventory' => array(
                    'run'
                ),
                'SimpleFAQ' => array(
                    'run'
                ),
                'ExampleApplication' => array(
                    'run'
                ),
                'Felamimail' => array(
                    'run'
                ),
                'ActiveSync' => array(
                    'run'
                ),
                'Tinebase' => array(
                    'run','manage_own_profile','check_version'
                ),
                'Timetracker' => array(
                    'manage_timeaccounts','add_timeaccounts','manage_shared_timeaccount_favorites','manage_shared_timesheet_favorites','run'
                ),
            )
        ),
        array(
            'roleData'    => array('name' => 'administrative management role'),
            'roleMembers' => array(
                array('type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, 'name' => 'HumanResources'),
                array('type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, 'name' => 'Controllers')
            ),
            'roleRights'  => array(
                'Tasks' => array(
                    'manage_shared_task_favorites','manage_shared_folders','run'
                ),
                'Crm' => array(
                    'manage_shared_lead_favorites','manage_shared_folders','run','manage_leads'
                ),
                'Calendar' => array(
                    'manage_shared_event_favorites','manage_shared_folders','run','manage_resources'
                ),
                'HumanResources' => array(
                    'run','edit_private'
                ),
                'Sipgate' => array(
                    'run','manage_private_accounts','manage_shared_accounts','manage_accounts','sync_lines'
                ),
                'Sales' => array(
                    'run','manage_products'
                ),
                'RequestTracker' => array(
                    'run'
                ),
                'Projects' => array(
                    'run','manage_shared_project_favorites'
                ),
                'Courses' => array(
                    'run','add_existing_user','add_new_user'
                ),
                'Inventory' => array(
                    'run'
                ),
                'SimpleFAQ' => array(
                    'run'
                ),
                'ExampleApplication' => array(
                    'run'
                ),
                'Felamimail' => array(
                    'run'
                ),
                'Filemanager' => array(
                    'run','manage_shared_folders'
                ),
                'Addressbook' => array(
                    'run','manage_shared_folders','manage_shared_contact_favorites'
                ),
                'ActiveSync' => array(
                    'run'
                ),
                'Tinebase' => array(
                    'run','report_bugs'
                ),
                'Timetracker' => array(
                    'manage_shared_timeaccount_favorites','manage_shared_timesheet_favorites','run','add_timeaccounts','manage_timeaccounts'
                ),
                'Admin' => array(
                    'manage_accounts','view_accounts','run'
                ),
            )
        ),
        array(
            'roleData'    => array('name' => 'secretary role'),
            'roleMembers' => array(
                array('type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, 'name' => 'Secretary'),
            ),
            'roleRights'  => array(
                'Addressbook' => array(
                    'manage_shared_contact_favorites','manage_shared_folders','run'
                ),
                'Tasks' => array(
                    'manage_shared_task_favorites','manage_shared_folders','run'
                ),
                'Crm' => array(
                    'manage_shared_lead_favorites','manage_shared_folders','run'
                ),
                'Calendar' => array(
                    'manage_shared_event_favorites','manage_shared_folders','run','manage_resources'
                ),
                'HumanResources' => array(
                    'run'
                ),
                'Sipgate' => array(
                    'run'
                ),
                'Sales' => array(
                    'run'
                ),
                'RequestTracker' => array(
                    'run'
                ),
                'Projects' => array(
                    'run'
                ),
                'Courses' => array(
                    'run','add_existing_user','add_new_user'
                ),
                'Inventory' => array(
                    'run'
                ),
                'SimpleFAQ' => array(
                    'run'
                ),
                'ExampleApplication' => array(
                    'run'
                ),
                'Felamimail' => array(
                    'run'
                ),
                'Filemanager' => array(
                    'run'
                ),
                'ActiveSync' => array(
                    'run'
                ),
                'Tinebase' => array(
                    'run'
                ),
                'Timetracker' => array(
                    'manage_shared_timeaccount_favorites','manage_shared_timesheet_favorites','run'
                ),
            )
        ),
    );
    // this week
    protected $_monday       = NULL;
    protected $_tuesday      = NULL;
    protected $_wednesday    = NULL;
    protected $_thursday     = NULL;
    protected $_friday       = NULL;
    protected $_saturday     = NULL;
    protected $_sunday       = NULL;

    // last week
    protected $_lastMonday   = NULL;
    protected $_lastFriday   = NULL;
    protected $_lastSaturday = NULL;
    protected $_lastSunday   = NULL;

    // next week
    protected $_nextMonday     = NULL;
    protected $_nextWednesday  = NULL;
    protected $_nextFriday     = NULL;
        
    protected $_wednesday2week = NULL;
    protected $_friday2week    = NULL;
    /**
     * shall shared data be created?
     * @var boolean
     */
    protected $_createShared = NULL;
    
    /**
     * shall user data be created?
     * @var boolean
     */
    protected $_createUsers = NULL;

    /**
     * the admin user
     */
    protected $_adminUser;

    /**
     * the contact of the admin user
     */
    protected $_adminUserContact;

    /**
     * Grants for Admin
     * @var array
     */
    protected $_adminGrants = array('readGrant','addGrant','editGrant','deleteGrant','privateGrant','exportGrant','syncGrant','adminGrant','freebusyGrant');
    /**
     * Grants for Secretary on private calendars
     * @var array
     */
    protected $_secretaryGrants = array('readGrant','freebusyGrant','addGrant');
    /**
     * Grants for Controller
     * @var array
     */
    protected $_controllerGrants = array('readGrant','exportGrant');
    /**
     * Grants for Users
     * @var array
     */
    protected $_userGrants = array('readGrant','addGrant','editGrant','deleteGrant');

    /**
     * RecordSet with all sales.costcenter, loaded by this._loadCostCenters
     *
     * @var Tinebase_Record_RecordSet
     */
    protected $_costCenters;
    
    /**
     * the ids of all costcenters in an array, loaded by this._loadCostCenters
     *
     * @var array
     */
    protected $_costCenterKeys;
    
    /**
     * the locale, the demodata should created with
     * @var string
     */
    protected static $_locale = 'en';
    
    /**
     * shortcut for locale
     */
    protected static $_de = true;
    
    /**
     * defaults to an empty password
     */
    protected static $_defaultPassword = '';
    
    /**
     * shortcut for locale
     */
    protected static $_en = false;

    protected function _getDays() {
        // find out where we are
        $now = new Tinebase_DateTime();
        $weekday = $now->format('w');

        $subdaysLastMonday = 6 + $weekday;    // Monday last Week
        $subdaysLastFriday = 2 + $weekday;    // Friday last Week

        // this week
        $this->_monday = new Tinebase_DateTime();
        $this->_monday->sub(date_interval_create_from_date_string(($weekday - 1) . ' days'));
        $this->_tuesday = new Tinebase_DateTime();
        $this->_tuesday->sub(date_interval_create_from_date_string(($weekday - 2) . ' days'));
        $this->_wednesday = new Tinebase_DateTime();
        $this->_wednesday->sub(date_interval_create_from_date_string(($weekday - 3) . ' days'));
        $this->_thursday = new Tinebase_DateTime();
        $this->_thursday->sub(date_interval_create_from_date_string(($weekday - 4) . ' days'));
        $this->_friday = new Tinebase_DateTime();
        $this->_friday->sub(date_interval_create_from_date_string(($weekday - 5) . ' days'));
        $this->_saturday = clone $this->_friday;
        $this->_saturday->add(date_interval_create_from_date_string('1 day'));
        $this->_sunday = clone $this->_friday;
        $this->_sunday->add(date_interval_create_from_date_string('2 days'));

        // last week
        $this->_lastMonday = clone $this->_monday;
        $this->_lastMonday->subWeek(1);
        $this->_lastWednesday = clone $this->_wednesday;
        $this->_lastWednesday->subWeek(1);
        $this->_lastFriday = clone $this->_friday;
        $this->_lastFriday->subWeek(1);
        $this->_lastThursday = clone $this->_thursday;
        $this->_lastThursday->subWeek(1);
        $this->_lastSaturday = clone $this->_saturday;
        $this->_lastSaturday->subWeek(1);
        $this->_lastSunday = clone $this->_sunday;
        $this->_lastSunday->subWeek(1);
        
        $this->_nextMonday = clone $this->_monday;
        $this->_nextMonday->addWeek(1);
        $this->_nextWednesday = clone $this->_wednesday;
        $this->_nextWednesday->addWeek(1);
        $this->_nextFriday = clone $this->_friday;
        $this->_nextFriday->addWeek(1);
        
        $this->_wednesday2week = clone $this->_nextWednesday;
        $this->_wednesday2week->addWeek(1);
        $this->_friday2week = clone $this->_nextFriday;
        $this->_friday2week->addWeek(1);
    }

    /**
     * loads all costcenters to this._costCenters property
     * 
     * @return Tinebase_Record_RecordSet
     */
    protected function _loadCostCenters()
    {
        $filter = new Sales_Model_CostCenterFilter(array());
        $this->_costCenters  = Sales_Controller_CostCenter::getInstance()->search($filter)->sort('number');
        $this->_costCenterKeys[] = array();
        
        foreach($this->_costCenters as $cc) {
            if ($cc->remark == 'Marketing') {
                $this->_marketingCostCenter = $cc;
            }
            if ($cc->remark == 'Development' || $cc->remark == 'Entwicklung') {
                $this->_developmentCostCenter = $cc;
            }
            $this->_costCenterKeys[] = $cc->getId();
        }
        return $this->_costCenters;
    }
    
    /**
     * 
     * @return multitype:array|null
     */
    public static function getRequiredApplications()
    {
        return static::$_requiredApplications ? static::$_requiredApplications : array();
    }
    
    /**
     * this is required for other applications needing demo data of this application
     * if this returns true, this demodata has been run already
     * 
     * @return boolean
     */
    public static function hasBeenRun()
    {
        return false;
    }
    
    /**
     * creates the demo data and is called from the Frontend_Cli
     *
     * @param string $_locale
     * @param array $_models
     * @param array $_users
     * @param boolean $this->_createShared
     * @param boolean $this->_createUsers
     * @param string $_password
     * @return boolean
     */
    public function createDemoData($_locale, $_models = NULL, $_users = NULL, $_createShared = TRUE, $_createUsers = TRUE, $_password = '') {

        $this->_createShared = $_createShared;
        $this->_createUsers  = $_createUsers;

        if ($_locale) {
            static::$_locale = $_locale;
        }
        // just shortcuts
        static::$_de = (static::$_locale == 'de') ? true : false;
        static::$_en = ! static::$_de;
        static::$_defaultPassword = $_password;
        $this->_beforeCreate();

        // look for defined models
        if(is_array($_models)) {
            foreach($_models as $model) {
                if(!in_array($model, $this->_models)) {
                    echo 'Model ' . $model . ' is not defined for demo data creation!' . chr(10);
                    return false;
                }
            }
            $this->_models = array_intersect($_models, $this->_models);
        }

        // get User Accounts
        if(is_array($_users)) {
            foreach($_users as $user) {
                if(!array_key_exists($user, $this->_personas)) {
                    echo 'User ' . $user . ' is not defined for demo data creation!' . chr(10);
                    return false;
                } else {
                    $users[$user] = $this->_personas[$user];
                }
            }
        } else {
            $users = $this->_personas;
        }

        $this->_personas = array();

        foreach($users as $loginName => $name) {
            try {
                $this->_personas[$loginName] = Tinebase_User::getInstance()->getFullUserByLoginName($loginName);
            } catch (Tinebase_Exception_NotFound $e) {
                echo 'Persona with login name ' . $loginName . ' does not exist or no demo data is defined!' . chr(10);
                echo 'Have you called Admin.createDemoDate already?' . PHP_EOL;
                echo 'If not, do this!' . PHP_EOL;
                return false;
            }
        }

        // admin User
        $this->_adminUser = Tinebase_Core::getUser();
        $this->_adminUserContact = Addressbook_Controller_Contact::getInstance()->getContactByUserId($this->_adminUser->getId());

        $this->_onCreate();

        $callQueue = array();
        $callQueueShared = array();

        if(is_array($this->_models)) {
            foreach($this->_models as $model) {
                $mname = ucfirst($model);
                // shared records
                if($this->_createShared) {
                    $methodName = '_createShared' . $mname . 's';
                    if(method_exists($this, $methodName)) {
                        $callQueueShared[] = array('methodName' => $methodName, 'modelName' => $mname);
                    }
                }

                // user records
                if($this->_createUsers) {
                    foreach($users as $userLogin => $userRecord) {
                        $uname = ucfirst($userLogin);
                        $methodName = '_create' . $mname . 'sFor' . $uname;
                        if(method_exists($this, $methodName)) {
                            $callQueue[$userLogin] = array('methodName' => $methodName, 'userName' => $uname, 'modelName' => $mname);
                        }
                    }
                }
            }
        }
        
        // call create shared method
        foreach($callQueueShared as $info) {
            echo 'Creating shared ' . $info['modelName'] . 's...' . PHP_EOL;
            $this->_modelName = $info['modelName'];
            $this->{$info['methodName']}();
        }
        
        // call create methods as the user itself
        foreach($callQueue as $loginName => $info) {
            Tinebase_Core::set(Tinebase_Core::USER, $this->_personas[$loginName]);
            echo 'Creating personal ' . $info['modelName'] . 's for ' . $info['userName'] . '...' . PHP_EOL;
            $this->{$info['methodName']}();
        }

        Tinebase_Core::set(Tinebase_Core::USER, $this->_adminUser);

        $this->_afterCreate();

        return true;
    }

    /**
     * returns a relation array by records
     * 
     * @param Tinebase_Record_Abstract $ownRecord
     * @param Tinebase_Record_Abstract $foreignRecord
     * @param string $ownDegree
     * @param string $type
     * @return array
     */
    protected function _getRelationArray($ownRecord, $foreignRecord, $ownDegree = Tinebase_Model_Relation::DEGREE_SIBLING, $type = NULL)
    {
        $ownModel = get_class($ownRecord);
        $foreignModel = get_class($foreignRecord);
        
        if (! $type) {
            $split = explode('_Model_', $foreignModel);
            $type = strtoupper($split[1]);
        }
         
        return array(
            'own_model'              => $ownModel,
            'own_backend'            => 'Sql',
            'own_id'                 => $ownRecord->getId(),
            'own_degree'             => $ownDegree,
            'related_model'          => $foreignModel,
            'related_backend'        => 'Sql',
            'related_id'             => $foreignRecord->getId(),
            'type'                   => $type
        );
    }

    protected function _createContainer()
    {
        
    }
    
    /**
     * creates a shared container by name and data, if given
     */
    protected function _createSharedContainer($containerName, $data = array(), $setAsThisSharedContainer = true)
    {
        // create shared calendar
        $container = Tinebase_Container::getInstance()->addContainer(
            new Tinebase_Model_Container(array_merge(
                array(
                    'name'           => $containerName,
                    'type'           => Tinebase_Model_Container::TYPE_SHARED,
                    'owner_id'       => Tinebase_Core::getUser(),
                    'backend'        => 'SQL',
                    'application_id' => Tinebase_Application::getInstance()->getApplicationByName($this->_appName)->getId(),
                    'model'          => $this->_appName . '_Model_' . $this->_modelName,
                    'color'          => '#00FF00'
                ),
                $data
            ), true)
        );

        $group = Tinebase_Group::getInstance()->getGroupByName(Tinebase_Group::DEFAULT_USER_GROUP);
        Tinebase_Container::getInstance()->addGrants($container->getId(), 'group', $group->getId(), $this->_userGrants, true);
        Tinebase_Container::getInstance()->addGrants($container->getId(), 'user', $this->_personas['sclever']->getId(), $this->_secretaryGrants, true);
        Tinebase_Container::getInstance()->addGrants($container->getId(), 'user', $this->_personas['pwulf']->getId(),   $this->_adminGrants, true);
        
        if ($setAsThisSharedContainer) {
            $this->_sharedContainer = $container;
        }
        
        return $container;
    }
    
    /**
     * returns an existing contact by its full name or by an account login name
     * usage:
     * array('user' => 'pwulf') 
     *      returns the contact of paul wulf 
     * array('contact' => 'Carolynn Hinsdale')
     *      returns the contact of Mrs. Hinsdale
     * 
     * @param array $_data
     * 
     */
    protected function _getContact($_data)
    {
        // handle user
        if (array_key_exists('user', $_data)) {
            $account = $this->_personas[$_data['user']];
            $contact = Addressbook_Controller_Contact::getInstance()->get($account->contact_id);
        } else { // handle contact
            $filter  = new Addressbook_Model_ContactFilter(array(
                array('field' => 'query', 'operator' => 'contains', 'value' => $_data['contact'])
            ));
            $contact = Addressbook_Controller_Contact::getInstance()->search($filter)->getFirstRecord();
        }
        return $contact;
    }
    
    /**
     * is called on createDemoData before finding out which models and users to build
     */
    protected function _beforeCreate() {

    }
    
    /**
     * is called on createDemoData after finding out which models
     * and users to build and before calling the demo data class methods
     */
    protected function _onCreate() {

    }
    
    /**
     * is called on createDemoData after all demo data has been created
     */
    protected function _afterCreate() {

    }
}
