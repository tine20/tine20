<?php

/**
 * ActionLog controller for Tinebase application
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * ActionLog controller for Tinebase application
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
class Tinebase_Controller_ActionLog extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     * @throws Tinebase_Exception_Backend_Database
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = Tinebase_Config::APP_NAME;
        $this->_modelName = Tinebase_Model_ActionLog::class;
        $this->_backend = new Tinebase_Backend_Sql(array(
            Tinebase_Backend_Sql::MODEL_NAME => Tinebase_Model_ActionLog::class,
            Tinebase_Backend_Sql::TABLE_NAME => Tinebase_Model_ActionLog::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true
        ));

        $this->_purgeRecords = false;
        $this->_resolveCustomFields = true;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = false;
    }

    public function addActionLogConfirmationEvent(object $_eventObject)
    {
        $actionLog = new Tinebase_Model_ActionLog([
            Tinebase_Model_ActionLog::FLD_ACTION_TYPE =>  Tinebase_Model_ActionLog::TYPE_ADD_USER_CONFIRMATION,
            Tinebase_Model_ActionLog::FLD_USER => Tinebase_Core::getUser()->getId(),
            Tinebase_Model_ActionLog::FLD_DATA => serialize($_eventObject),
            Tinebase_Model_ActionLog::FLD_DATETIME => Tinebase_DateTime::now(),
        ]);
        $this->create($actionLog);
    }

    public function addActionLogUserDelete($accountIds)
    {
        $data = is_string($accountIds) ? $accountIds : implode(',', $accountIds);
        $actionLog = new Tinebase_Model_ActionLog([
            Tinebase_Model_ActionLog::FLD_ACTION_TYPE => Tinebase_Model_ActionLog::TYPE_DELETION,
            Tinebase_Model_ActionLog::FLD_USER => Tinebase_Core::getUser()->getId(),
            Tinebase_Model_ActionLog::FLD_DATA => 'user ids deleted: ' . $data,
            Tinebase_Model_ActionLog::FLD_DATETIME => Tinebase_DateTime::now(),
        ]);
        $this->create($actionLog);
    }

    public function addActionLogQuotaNotification($data)
    {
        $actionLog = new Tinebase_Model_ActionLog([
            Tinebase_Model_ActionLog::FLD_ACTION_TYPE =>  Tinebase_Model_ActionLog::TYPE_EMAIL_NOTIFICATION,
            Tinebase_Model_ActionLog::FLD_USER => Tinebase_Core::getUser()->getId(),
            Tinebase_Model_ActionLog::FLD_DATA => 'email notification ' . $data,
            Tinebase_Model_ActionLog::FLD_DATETIME => Tinebase_DateTime::now(),
        ]);
        $this->create($actionLog);
    }
}
