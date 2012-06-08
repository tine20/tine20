<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * the default personas' passwort
     * @var string
     */
    protected $_defaultPassword = 'tine20';

    /**
     * default ip for the fake session
     * @var unknown_type
     */
    protected $_defaultCliIp = '127.0.0.1';

    /**
     * the personas to create demodata for
     * http://www.tine20.org/wiki/index.php/Personas
     * will be resolved to array of accounts
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
     * the locale, the demodata should created with
     * @var string
     */
    protected $_locale = 'en';

    /**
     * creates the demo data and is called from the Frontend_Cli
     *
     * @param string $_locale
     * @param array $_models
     * @param array $_users
     * @param boolean $this->_createShared
     * @param boolean $this->_createUsers
     * @return boolean
     */
    public function createDemoData($_locale, $_models = NULL, $_users = NULL, $_createShared = TRUE, $_createUsers = TRUE) {

        $this->_createShared = $_createShared;
        $this->_createUsers = $_createUsers;

        if($_locale) $this->_locale = $_locale;
        // just shortcuts
        $this->de = ($this->_locale == 'de') ? true : false;
        $this->en = ! $this->de;

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
            if($user = Tinebase_User::getInstance()->getFullUserByLoginName($loginName)) {
                $this->_personas[$loginName] = $user;
            } else {
                echo 'Persona with login name' . $loginName . ' does not exist or no demo data is defined!' . chr(10);
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

                // shared records
                if($this->_createShared) {
                    $methodName = 'createShared' . ucfirst($model) . 's';
                    if(method_exists($this, $methodName)) {
                        $callQueueShared[] = $methodName;
                    }
                }

                // user records
                if($this->_createUsers) {
                    foreach($users as $userLogin => $userRecord) {
                        $methodName = 'create' . ucfirst($model) . 'sFor' . ucfirst($userLogin);
                        if(method_exists($this, $methodName)) {
                            $callQueue[$userLogin] = $methodName;
                        }
                    }
                }
            }
        }
        foreach($callQueueShared as $method) {
            $this->{$method}();
        }

        foreach($callQueue as $loginName => $method) {
            Tinebase_Core::set(Tinebase_Core::USER, $this->_personas[$loginName]);
            $this->{$method}();
        }

        Tinebase_Core::set(Tinebase_Core::USER, $this->_adminUser);

        $this->_afterCreate();

        return true;
    }

    protected function _beforeCreate() {

    }

    protected function _onCreate() {

    }

    protected function _afterCreate() {

    }
}