<?php
/**
 * GDPR Data Intended Purpose Controller
 *
 * @package      GDPR
 * @subpackage   Controller
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Paul Mehrer <p.mehrer@metaways.de>
 * @copyright    Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * GDPR Data Intended Purpose Controller
 *
 * @package      GDPR
 * @subpackage   Controller
 */
class GDPR_Controller_DataIntendedPurpose extends Tinebase_Controller_Record_Abstract
{
    protected static $_defaultModel = GDPR_Model_DataIntendedPurpose::class;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     * @throws Tinebase_Exception_Backend_Database
     */
    private function __construct()
    {
        $this->_doContainerACLChecks = false;

        $this->_applicationName = GDPR_Config::APP_NAME;
        $this->_modelName = GDPR_Model_DataIntendedPurpose::class;

        $this->_backend = new Tinebase_Backend_Sql([
            'modelName' => $this->_modelName,
            'tableName' => 'gdpr_dataintendedpurposes',
            'modlogActive' => true
        ]);

        $this->_purgeRecords = false;
    }

    private function __clone()
    {
    }

    /**
     * @var self
     */
    private static $_instance = null;

    /**
     * @return self
     * @throws Tinebase_Exception_Backend_Database
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * overwrite this function to check rights / don't forget to call parent
     *
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_AreaLocked
     */
    protected function _checkRight($_action)
    {
        if (!$this->_doRightChecks) {
            return;
        }

        // get is ok, everything else requires GDPR_Acl_Rights::MANAGE_CORE_DATA_DATA_INTENDED_PURPOSE
        if ('get' !== $_action && !Tinebase_Core::getUser()->hasRight(GDPR_Config::APP_NAME,
                GDPR_Acl_Rights::MANAGE_CORE_DATA_DATA_INTENDED_PURPOSE)) {
            throw new Tinebase_Exception_AccessDenied("You don't have the right to manage data intended purposes.");
        }

        parent::_checkRight($_action);
    }
}
