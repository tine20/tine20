<?php
/**
 * Tine 2.0 role member controller
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

/**
 * this class handles the role members
 *
 * @package     Tinebase
 */
class Tinebase_RoleMember extends Tinebase_Controller_Record_Abstract
{
    /**
     * holds the _instance of the singleton
     *
     * @var Tinebase_RoleMember
     */
    private static $_instance = NULL;

    /**
     * the clone function
     *
     * disabled. use the singleton
     */
    private function __clone()
    {
    }

    /**
     * the constructor
     *
     * disabled. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'Tinebase';
        $this->_modelName = 'Tinebase_Model_RoleMember';
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_RoleMember',
            'tableName' => 'role_accounts',
        ), Tinebase_Core::getDb());
        $this->_purgeRecords = TRUE;
        //$this->_resolveCustomFields = FALSE;
        $this->_updateMultipleValidateEachRecord = TRUE;
        $this->_doContainerACLChecks = FALSE;
        $this->_setNotes = FALSE;
        $this->_omitModLog = TRUE;
    }

    /**
     * the singleton pattern
     *
     * @return Tinebase_RoleMember
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_RoleMember;
        }

        return self::$_instance;
    }
}