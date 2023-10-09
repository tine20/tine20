<?php
/**
 * GDPR Data Provenance Controller
 *
 * @package      GDPR
 * @subpackage   Controller
 * @license      http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author       Paul Mehrer <p.mehrer@metaways.de>
 * @copyright    Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as TMCC;

/**
 * GDPR Data Provenance Controller
 *
 * @package      GDPR
 * @subpackage   Controller
 */
class GDPR_Controller_DataProvenance extends Tinebase_Controller_Record_Abstract
{
    const ADB_CONTACT_CUSTOM_FIELD_NAME = 'GDPR_DataProvenance';
    const ADB_CONTACT_REASON_CUSTOM_FIELD_NAME = 'GDPR_DataEditingReason';

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
        $this->_modelName = GDPR_Model_DataProvenance::class;

        $this->_backend = new Tinebase_Backend_Sql([
            'modelName' => $this->_modelName,
            'tableName' => 'gdpr_dataprovenances',
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

        // get is ok, everything else requires GDPR_Acl_Rights::MANAGE_CORE_DATA_DATA_PROVENANCE
        if ('get' !== $_action && !Tinebase_Core::getUser()->hasRight(GDPR_Config::APP_NAME,
                GDPR_Acl_Rights::MANAGE_CORE_DATA_DATA_PROVENANCE)) {
            throw new Tinebase_Exception_AccessDenied("You don't have the right to manage data provenances.");
        }

        if ('delete' === $_action) {
            throw new Tinebase_Exception_AccessDenied(GDPR_Model_DataProvenance::class . ' can\'t be deleted');
        }

        parent::_checkRight($_action);
    }

    public static function modelConfigHook(array &$_fields)
    {
        if (GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY_DEFAULT ===
                GDPR_Config::getInstance()->{GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY}) {
            $_fields[GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME][TMCC::VALIDATORS]
                [Zend_Filter_Input::DEFAULT_VALUE] =
                    GDPR_Config::getInstance()->{GDPR_Config::DEFAULT_ADB_CONTACT_DATA_PROVENANCE};
            $_fields[GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME][TMCC::VALIDATORS]
                [Zend_Filter_Input::ALLOW_EMPTY] = true;
        } elseif (GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY_YES ===
                GDPR_Config::getInstance()->{GDPR_Config::ADB_CONTACT_DATA_PROVENANCE_MANDATORY}) {
            $_fields[GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME][TMCC::VALIDATORS][] =
                new GDPR_Model_Validator_NotEmpty();
        } else {
            $_fields[GDPR_Controller_DataProvenance::ADB_CONTACT_CUSTOM_FIELD_NAME][TMCC::VALIDATORS]
                [Zend_Filter_Input::ALLOW_EMPTY] = true;
        }
    }

    public function applyReplicationModificationLog(Tinebase_Model_ModificationLog $_modification)
    {
        Tinebase_Timemachine_ModificationLog::defaultApply($_modification, $this);

        if (!GDPR_Config::getInstance()->{GDPR_Config::DEFAULT_ADB_CONTACT_DATA_PROVENANCE} &&
                Tinebase_Timemachine_ModificationLog::CREATED === $_modification->change_type) {
            GDPR_Config::getInstance()->{GDPR_Config::DEFAULT_ADB_CONTACT_DATA_PROVENANCE} = $_modification->record_id;
        }
    }
}
