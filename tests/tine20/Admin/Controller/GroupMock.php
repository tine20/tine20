<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Group Controller mock for admin tests
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller_GroupMock extends Admin_Controller_Group
{
    /**
     * holds the instance of the singleton
     *
     * @var Admin_Controller_GroupMock
     */
    private static $_instance = NULL;

    /** @noinspection PhpMissingParentConstructorInspection */
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'Admin';
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * the singleton pattern
     *
     * @return Admin_Controller_Group
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller_GroupMock;
        }

        return self::$_instance;
    }

    /**
     * create or update list in addressbook sql backend
     *
     * @param  Tinebase_Model_Group $group
     * @return Addressbook_Model_List|void
     * @throws Exception
     */
    public function createOrUpdateList(Tinebase_Model_Group $group)
    {
        parent::createOrUpdateList($group);
        throw new Exception('kabum');
    }
}