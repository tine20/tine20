<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * ListMemberRole controller for Addressbook
 *
 * @package     Addressbook
 * @subpackage  Controller
 */
class Addressbook_Controller_ListMemberRole extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = 'Addressbook';
        $this->_modelName = Addressbook_Model_ListMemberRole::class;
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName'     => Addressbook_Model_ListMemberRole::class,
            'tableName'     => 'adb_list_m_role',
            'modlogActive'  => false,
        ));
        $this->_purgeRecords = true;
        $this->_omitModLog = true;
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }

    /**
     * holds the instance of the singleton
     *
     * @var Addressbook_Controller_ListMemberRole
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Addressbook_Controller_ListMemberRole
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public static function destroyInstance()
    {
        self::$_instance = null;
    }
}
