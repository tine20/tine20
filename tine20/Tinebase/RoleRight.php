<?php
/**
 * Tine 2.0 role right controller
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

/**
 * this class handles the role rights
 *
 * @package     Tinebase
 */
class Tinebase_RoleRight extends Tinebase_Controller_Record_Abstract
{
    /**
     * holds the _instance of the singleton
     *
     * @var Tinebase_RoleRight
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
        $this->_modelName = 'Tinebase_Model_RoleRight';
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_RoleRight',
            'tableName' => 'role_rights',
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
     * @return Tinebase_RoleRight
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_RoleRight;
        }

        return self::$_instance;
    }
}