<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * holdes the instance of the singleton
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

    function _beforeCreate()
    {

        foreach ($this->_personas as $login => $fullName) {
            try {
                $user = Tinebase_User::getInstance()->getFullUserByLoginName($login);
            } catch (Tinebase_Exception_NotFound $e) {
                list($given, $last) = explode(' ', $fullName);

                $group   = Tinebase_Group::getInstance()->getGroupByName('Users');
                $groupId = $group->getId();

                $user = new Tinebase_Model_FullUser(array(
                    'accountLoginName'      => $login,
                    'accountPrimaryGroup'   => $groupId,
                    'accountDisplayName'    => $fullName,
                    'accountLastName'       => $last,
                    'accountFirstName'      => $given,
                    'accountFullName'       => $fullName,
                    //'accountEmailAddress'   => $login . '@tine-publications.co.uk',
                    'accountEmailAddress'   => $login . '@tine20.org'
                ));

                if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
                    $internalAddressbook = Tinebase_Container::getInstance()->getContainerByName('Addressbook', 'Internal Contacts', Tinebase_Model_Container::TYPE_SHARED);

                    $user->container_id = $internalAddressbook->getId();

                    $contact = Admin_Controller_User::getInstance()->createOrUpdateContact($user);

                    $user->contact_id = $contact->getId();
                }

                $user = Tinebase_User::getInstance()->addUser($user);

                Tinebase_Group::getInstance()->addGroupMember($groupId, $user);

                if (Tinebase_Application::getInstance()->isInstalled('Addressbook') === true) {
                    $listBackend = new Addressbook_Backend_List();

                    $listBackend->addListMember($group->list_id, $user->contact_id);
                }

                // give additional testusers the same password as the primary test account
                try {
                    $testconfig = Zend_Registry::get('testConfig');
                    Tinebase_User::getInstance()->setPassword($user, $testconfig->password);
                } catch (Zend_Exception $e) {
                    Tinebase_User::getInstance()->setPassword($user, $this->defaultPassword);
                }
            }
            $personas[$login] = $user;
        }
        Zend_Registry::set('personas', $personas);
    }
}
