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
     * creates soo many records, if set to true
     * 
     * @var boolean
     */
    protected static $_createFullData = FALSE;
    
    /**
     * the personas to create demodata for
     * http://wiki.tine20.org/Personas
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
                'visibility' => 'displayed', 'name' => 'Managers', 'description' => 'Managers of the company'
            ),
            'members' => array('pwulf')
        ),
        array(
            'groupData' => array(
                'visibility' => 'displayed', 'name' => 'HumanResources', 'description' => 'Human Resources Managment'
            ),
            'members' => 
                array('sclever', 'pwulf')
            ),
        array(
            'groupData' => array(
                'visibility' => 'displayed', 'name' => 'Secretary', 'description' => 'Secretarys of the company'
            ),
            'members' => array('sclever', 'pwulf')
        ),
        array(
            'groupData' => array(
                'visibility' => 'displayed', 'name' => 'Controllers', 'description' => 'Controllers of the company'
            ),
            'members' => array('rwright', 'pwulf')
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
                'Sales' => array(
                    'run','manage_products'
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
                'Sales' => array(
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
    
    /**
     * the reference date for data in aggregates/invoices...
     * 
     * @var Tinebase_DateTime
     */
    protected $_referenceDate = NULL;
    
    /**
     * holds an array containing the last day of each month for last year
     * 
     * @var array
     */
    protected $_lastMonthDays = NULL;
    
    /**
     * if the last year was a leap year, this is set to true
     * 
     * @var boolean
     */
    protected $_isLeapYear = FALSE;
    
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
     * defaults to NULL, a random password will be generated and shown on the cli output
     * prevent this by adding a 'login' array containing 'username' and 'password' to the config
     */
    protected static $_defaultPassword = NULL;
    
    /**
     * shortcut for locale
     */
    protected static $_en = false;
    
    /**
     * sets $this->_referenceDate to the first january of last year
     */
    protected function _setReferenceDate()
    {
        // set reference date to the 1st january of last year
        $this->_referenceDate = Tinebase_DateTime::now();
        $this->_referenceDate->setTimezone(Tinebase_Core::getUserTimezone());
        $this->_referenceDate->subYear(1);
        $this->_referenceDate->setDate($this->_referenceDate->format('Y'), 1 ,1);
        $this->_referenceDate->setTime(0,0,0);
    
        $this->_referenceYear = $this->_referenceDate->format('Y');
        $this->_lastMonthDays = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    
        // find out if year is a leap year
        if (($this->_referenceYear % 400) == 0 || (($this->_referenceYear % 4) == 0 && ($this->_referenceYear % 100) != 0)) {
            $this->_isLeapYear = TRUE;
            $this->_lastMonthDays[1] = 29;
        }
    }
    
    /**
     * 
     * @param Tinebase_DateTime $now
     */
    protected function _getDays(Tinebase_DateTime $now = NULL)
    {
        // find out where we are
        if (! $now) {
            $now = new Tinebase_DateTime();
        }
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
        $this->_nextTuesday = clone $this->_tuesday;
        $this->_nextTuesday->addWeek(1);
        $this->_nextWednesday = clone $this->_wednesday;
        $this->_nextWednesday->addWeek(1);
        $this->_nextThursday = clone $this->_thursday;
        $this->_nextThursday->addWeek(1);
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
    protected function _loadCostCentersAndDivisions()
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
        
        $filter = new Sales_Model_DivisionFilter(array());
        $this->_divisions  = Sales_Controller_Division::getInstance()->search($filter)->sort('number');
        
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
     * @param array $options
     * @return boolean
     */
    public function createDemoData(array $options)
    {
        static::$_createFullData = (isset($options['full']) || array_key_exists('full', $options)) ? TRUE : FALSE;
        
        $this->_createShared = (isset($options['createShared']) || array_key_exists('createShared', $options)) ? $options['createShared'] : TRUE;
        $this->_createUsers  = (isset($options['createUsers']) || array_key_exists('createUsers', $options))  ? $options['createUsers']  : TRUE;

        if ((isset($options['locale']) || array_key_exists('locale', $options))) {
            static::$_locale = $options['locale'];
        }
        // just shortcuts
        static::$_de = (static::$_locale == 'de') ? true : false;
        static::$_en = ! static::$_de;
        static::$_defaultPassword = (isset($options['password']) || array_key_exists('password', $options)) ? $options['password'] : '';
        $this->_beforeCreate();

        // look for defined models
        if ((isset($options['models']) || array_key_exists('models', $options)) && is_array($options['models'])) {
            foreach ($options['models'] as $model) {
                if (! in_array($model, $this->_models)) {
                    echo 'Model ' . $model . ' is not defined for demo data creation!' . chr(10);
                    return false;
                }
            }
            $this->_models = array_intersect($options['models'], $this->_models);
        }

        // get User Accounts
        if ((isset($options['users']) || array_key_exists('users', $options)) && is_array($options['users'])) {
            foreach ($options['users'] as $user) {
                if (! (isset($this->_personas[$user]) || array_key_exists($user, $this->_personas))) {
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

        if (is_array($this->_models)) {
            foreach ($this->_models as $model) {
                $mname = ucfirst($model);
                // shared records
                if ($this->_createShared) {
                    $methodName = '_createShared' . $mname . 's';
                    if(method_exists($this, $methodName)) {
                        $callQueueShared[] = array('methodName' => $methodName, 'modelName' => $mname);
                    }
                }

                // user records
                if ($this->_createUsers) {
                    foreach ($users as $userLogin => $userRecord) {
                        $uname = ucfirst($userLogin);
                        $methodName = '_create' . $mname . 'sFor' . $uname;
                        if (method_exists($this, $methodName)) {
                            $callQueue[$userLogin] = array('methodName' => $methodName, 'userName' => $uname, 'modelName' => $mname);
                        }
                    }
                }
            }
        }
        
        // call create shared method
        foreach ($callQueueShared as $info) {
            echo 'Creating shared ' . $info['modelName'] . 's...' . PHP_EOL;
            $this->_modelName = $info['modelName'];
            $this->{$info['methodName']}();
        }
        
        // call create methods as the user itself
        foreach ($callQueue as $loginName => $info) {
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
     * @param Tinebase_Record_Interface $ownRecord
     * @param Tinebase_Record_Interface $foreignRecord
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
            'related_degree'         => $ownDegree,
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
                    'backend'        => 'SQL',
                    'application_id' => Tinebase_Application::getInstance()->getApplicationByName($this->_appName)->getId(),
                    'model'          => $this->_appName . '_Model_' . $this->_modelName,
                    'color'          => '#00FF00'
                ),
                $data
            ), true)
        );
        $group = Tinebase_Group::getInstance()->getDefaultGroup();
        Tinebase_Container::getInstance()->addGrants($container->getId(), 'group', $group->getId(), $this->_userGrants, true);
        if (isset($this->_personas['sclever'])) {
            Tinebase_Container::getInstance()->addGrants($container->getId(), 'user', $this->_personas['sclever']->getId(), $this->_secretaryGrants, true);
        }
        if (isset($this->_personas['pwulf'])) {
            Tinebase_Container::getInstance()->addGrants($container->getId(), 'user', $this->_personas['pwulf']->getId(), $this->_adminGrants, true);
        }
        
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
        if ((isset($_data['user']) || array_key_exists('user', $_data))) {
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
    protected function _beforeCreate()
    {
    }
    
    /**
     * is called on createDemoData after finding out which models
     * and users to build and before calling the demo data class methods
     */
    protected function _onCreate()
    {
    }
    
    /**
     * is called on createDemoData after all demo data has been created
     */
    protected function _afterCreate()
    {
    }
}
