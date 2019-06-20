<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Calendar initialization
 *
 * @package     Setup
 */
class Admin_Setup_DemoData extends Tinebase_Setup_DemoData_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Admin_Setup_DemoData
     */
    private static $_instance = NULL;

    /**
     * the constructor
     *
     */
    public function __construct()
    {
    }

    /**
     * this is required for other applications needing demo data of this application
     * if this returns true, this demodata has been run already
     * 
     * @return boolean
     */
    public static function hasBeenRun()
    {
        try {
            Tinebase_Group::getInstance()->getGroupByName('Secretary');
        } catch (Tinebase_Exception_Record_NotDefined $e) {
            return false;
        }
        
        return true;
    }
    
    /**
     * maps contact data for the personas
     */
    protected $_dataMapping = array(
        'sclever'  => array(
            'email_home' => 'minni.mouse@mailforyouandme.uk', 'tel_work' => '+441273-3766-373', 'salutation' => 'MS', 'tel_cell' => '+441273-23462111', 'tel_cell_private' => '+441273-853642436', 'tel_fax' => '+441273-3766-12', 'tel_home' => '+441273-23434521'
        ),
        'rwright'  => array(
            'email_home' => 'robertatis@mailforyouandme.uk','tel_work' => '+441273-3766-374', 'salutation' => 'MS', 'tel_cell' => '+441273-123587899', 'tel_cell_private' => '+441273-543236', 'tel_fax' => '+441273-3766-13', 'tel_home' => '+441273-98675333'
        ),
        'pwulf'    => array(
            'email_home' => 'masterchief@mailforyouandme.uk', 'tel_work' => '+441273-3766-376', 'tel_cell' => '+441273-23545265', 'tel_cell_private' => '+441273-345677', 'tel_fax' => '+441273-3766-15', 'tel_home' => '+441273-12566'
        ),
        'jmcblack' => array(
            'email_home' => 'full.house@mailforyouandme.uk', 'tel_work' => '+441273-3766-377', 'tel_cell' => '+441273-24353676', 'tel_cell_private' => '+441273-987643', 'tel_fax' => '+441273-3766-16', 'tel_home' => '+441273-335662'
        ),
        'jsmith'   => array(
            'email_home' => 'johnny@mailforyouandme.uk', 'tel_work' => '+441273-3766-378', 'tel_cell' => '+441273-98765443', 'tel_cell_private' => '+441273-236734', 'tel_fax' => '+441273-3766-17', 'tel_home' => '+441273-2354999'
        ),
        'default'  => array(
            'salutation' => 'MR', 'org_name' => 'Tine Publications, Ltd', 'adr_one_locality' => 'Brighton',
            'adr_one_region' => 'East Sussex', 'adr_one_postalcode' => 'BN1', 
            'adr_one_street' => 'Montgomery Street 589', 'adr_one_countryname' => 'GB'
        )
    );

    /**
     * tag names to create shared tags from
     * 
     * @var array
     */
    protected $_tagNames = array('customers', 'partners', 'europe', 'internet', 'china', 'fair', 'commercial', 'pre sales',
        'newsletter', 'public', 'suppliers', 'traders', 'employees', 'waits for action', 'friends', 'internal', 'international');
    
    /**
     * the singleton pattern
     *
     * @return Admin_Setup_DemoData
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Setup_DemoData;
        }

        return self::$_instance;
    }

    /**
     * unsets the instance to save memory, be aware that hasBeenRun still needs to work after unsetting!
     *
     */
    public function unsetInstance()
    {
        if (self::$_instance !== NULL) {
            self::$_instance = null;
        }
    }

    /**
     * creates the groups if not created already
     *
     * TODO why is this using Admin_Frontend_Json? fix that!
     */
    protected function _createGroups()
    {
        $fe = new Admin_Frontend_Json();
        $internalAddressbook = Tinebase_Container::getInstance()->getContainerById(
            Admin_Controller_User::getDefaultInternalAddressbook()
        );
        foreach ($this->_groups as $groupArray) {
            $groupArray['container_id'] = $internalAddressbook->getId();
            $members = array();
            foreach ($groupArray['members'] as $member) {
                $members[] = $this->_personas[$member]->getId();
            }
            
            try {
                $groupArray['groupData']['members'] = $members;
                $this->_groups[$groupArray['groupData']['name']] = $fe->saveGroup($groupArray['groupData']);
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
                echo 'Group "' . $groupArray['groupData']['name'] . '" already exists. Skipping...' . PHP_EOL;
                $gr = Tinebase_Group::getInstance()->getGroupByName($groupArray['groupData']['name']);
                $this->_groups[$groupArray['groupData']['name']] = $fe->getGroup($gr->getId());
            }
        }
    }
    
    /**
     * create roles
     */
    protected function _createRoles()
    {
        $fe = new Admin_Frontend_Json();
        foreach ($this->_roles as $roleArray) {
            
            // resolve members
            $members = array();
            foreach($roleArray['roleMembers'] as &$member) {
                $member['id'] = $this->_groups[$member['name']]['id'];
                unset($member['name']);
            }
            
            // resolve rights
            $roleRights = array();
            foreach($roleArray['roleRights'] as $application => $rights) {
                try {
                    $appId = Tinebase_Application::getInstance()->getApplicationByName($application)->getId();
                    foreach($rights as $rightName) {
                        $roleRights[] = array('application_id' => $appId, 'right' => $rightName);
                    }
                } catch (Exception $e) {
                    echo 'Application "' . $application . '" not installed. Skipping...' . PHP_EOL;
                }
            }
            
            try {
                $result = $fe->saveRole($roleArray['roleData'], $roleArray['roleMembers'], $roleRights);
            } catch (Exception $e) {
                echo 'Role "' . $roleArray['roleData']['name'] . '" already exists. Skipping...' . PHP_EOL;
            }
        }
    }

    /**
     * @see Tinebase_Setup_DemoData_Abstract
     */
    protected function _beforeCreate()
    {
        $be = new Addressbook_Backend_Sql();
        
        foreach ($this->_personas as $login => $fullName) {
            try {
                $user = Tinebase_User::getInstance()->getFullUserByLoginName($login);
                $contact = Addressbook_Controller_Contact::getInstance()->get($user->contact_id);
            } catch (Tinebase_Exception_NotFound $e) {
                list($given, $last) = explode(' ', $fullName);

                $group   = Tinebase_Group::getInstance()->getDefaultGroup();
                $groupId = $group->getId();
                
                $emailDomain = $this->_getMailDomain();

                $user = new Tinebase_Model_FullUser(array(
                    'accountLoginName'      => $login,
                    'accountPrimaryGroup'   => $groupId,
                    'accountDisplayName'    => $fullName,
                    'accountLastName'       => $last,
                    'accountFirstName'      => $given,
                    'accountFullName'       => $fullName,
                    'accountEmailAddress'   => $login . '@' . $emailDomain,
                ));

                if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
                    $internalAddressbook = Tinebase_Container::getInstance()->getContainerById(
                        Admin_Controller_User::getDefaultInternalAddressbook()
                    );

                    $user->container_id = $internalAddressbook->getId();

                    $contact = Admin_Controller_User::getInstance()->createOrUpdateContact($user);
                    
                    $user->contact_id = $contact->getId();
                }
                
                Tinebase_Timemachine_ModificationLog::setRecordMetaData($user, 'create');
                $user = Tinebase_User::getInstance()->addUser($user);

                // fire event to make sure all user data is created in the apps
                $event = new Admin_Event_AddAccount(array(
                    'account' => $user
                ));
                Tinebase_Event::fireEvent($event);

                Tinebase_Group::getInstance()->addGroupMember($groupId, $user);
                
                if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true && $group->list_id) {
                    $listBackend = new Addressbook_Backend_List();
                    $listBackend->addListMember($group->list_id, $user->contact_id);
                }

                $this->_setUserPassword($user);
            }
            
            if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
                $ar = array_merge($this->_dataMapping[$login], $this->_dataMapping['default']);
                $filename = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'DemoData' . DIRECTORY_SEPARATOR . 'persona_' . $login . '.jpg';
                if (file_exists($filename)) {
                    $handle = fopen($filename, "r");
                    $content = fread($handle, filesize($filename));
                    fclose($handle);
                    $be->_saveImage($contact->getId(), $content);
                    
                }
                foreach($ar as $property => $value) {
                    $contact->{$property} = $value;
                }
                
                Addressbook_Controller_Contact::getInstance()->update($contact);
            }
            
            $this->_personas[$login] = $user;
        }
        
        $this->_createGroups();
        $this->_createRoles();
        $this->_createSharedTags();
    }

    /**
     * get mail domain
     *
     * @return string
     */
    protected function _getMailDomain()
    {
        $smtpConfig = Tinebase_Config::getInstance()->get(Tinebase_Config::SMTP, new Tinebase_Config_Struct())->toArray();
        if (isset($smtpConfig['primarydomain'])) {
            return $smtpConfig['primarydomain'];
        }

        $testconfig = $this->_getTestConfig();
        $emailDomain = ($testconfig && isset($testconfig->maildomain)) ? $testconfig->maildomain : 'tine20.org';

        return $emailDomain;
    }

    /**
     * get testconfig
     *
     * @return mixed|null
     */
    protected function _getTestConfig()
    {
        try {
            $testconfig = Zend_Registry::get('testConfig');
        } catch (Zend_Exception $e) {
            $testconfig = NULL;
        }

        return $testconfig;
    }

    /**
     * give additional testusers a password
     * 
     * @param Tinebase_Model_User $user
     */
    protected function _setUserPassword($user)
    {
        $testconfig = $this->_getTestConfig();
        $password =
            // use password of the primary test account 
            ($testconfig && isset($testconfig->password)) ? $testconfig->password :
            // use password from login in config.inc.php
                (isset(Tinebase_Config::getInstance()->login->password) ? Tinebase_Config::getInstance()->login->password :
            // use static password
                static::$_defaultPassword);
        if (! $password || empty($password)) {
            // set random password
            $password = Tinebase_Record_Abstract::generateUID(12);
            echo "\033[33mUser \"" . $user->accountDisplayName . "\" got a random password: \"" . $password . "\"\033[0m" . PHP_EOL;
        }
        
        Tinebase_User::getInstance()->setPassword($user, $password);
    }
    
    /**
     * create some shared tags
     */
    protected function _createSharedTags()
    {
        $appList = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED)->toArray();

        foreach ($this->_tagNames as $tagName) {
            Tinebase_Tags::getInstance()->createTag(new Tinebase_Model_Tag(array(
                'type'  => Tinebase_Model_Tag::TYPE_SHARED,
                'name'  => $tagName,
                'description' => 'this is the shared tag ' . $tagName,
                'color' => '#' . $this->_generateRandomColor(),
                'contexts' => array('any'),
                'appList'  => $appList,
                'rights'   => array(array(
                    'account_type'  => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
                    'account_id'    => 0,
                    'view_right'    => TRUE,
                    'use_right'     => TRUE,
                ))
            )));
        }
    }
    
    /**
     * generate a random html color code
     * 
     * @return string
     */
    protected function _generateRandomColor()
    {
        mt_srand((double)microtime()*1000000);
        $color = '';
        while (strlen($color) < 6) {
            $color .= sprintf("%02X", mt_rand(0, 255));
        }
        return $color;
    }
}
