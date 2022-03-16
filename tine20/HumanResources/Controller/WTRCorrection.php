<?php
/**
 * WTRCorrection controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * WTRCorrection controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_WTRCorrection extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;
    use HumanResources_Controller_CheckFilterACLEmployeeTrait;

    protected $_getMultipleGrant = [HumanResources_Model_DivisionGrants::READ_CHANGE_REQUEST];
    protected $_requiredFilterACLget = [HumanResources_Model_DivisionGrants::READ_CHANGE_REQUEST];
    protected $_requiredFilterACLupdate  = [HumanResources_Model_DivisionGrants::UPDATE_CHANGE_REQUEST];
    protected $_requiredFilterACLsync  = [HumanResources_Model_DivisionGrants::READ_CHANGE_REQUEST];
    protected $_requiredFilterACLexport  = [HumanResources_Model_DivisionGrants::READ_CHANGE_REQUEST];

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct() {
        $this->_applicationName = HumanResources_Config::APP_NAME;
        $this->_modelName = HumanResources_Model_WTRCorrection::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => $this->_modelName,
            Tinebase_Backend_Sql::TABLE_NAME    => HumanResources_Model_WTRCorrection::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true
        ]);
        $this->_purgeRecords = false;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = true;
        $this->_traitGetOwnGrants = [
            HumanResources_Model_DivisionGrants::READ_OWN_DATA,
            HumanResources_Model_DivisionGrants::CREATE_OWN_CHANGE_REQUEST
        ];
    }

    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        /** @var HumanResources_Model_WTRCorrection $_record */
        if (!$this->_doContainerACLChecks) {
            return true;
        }

        // if we have manage_employee right, we have all grants
        if (Tinebase_Core::getUser()->hasRight(HumanResources_Config::APP_NAME, HumanResources_Acl_Rights::MANAGE_EMPLOYEE)) {
            return true;
        }

        if (parent::_checkGrant($_record, HumanResources_Model_DivisionGrants::UPDATE_EMPLOYEE_DATA, false) ||
                parent::_checkGrant($_record, HumanResources_Model_DivisionGrants::GRANT_ADMIN, false)) {
            return true;
        }
        if (self::ACTION_DELETE !== $_action &&
                parent::_checkGrant($_record, HumanResources_Model_DivisionGrants::UPDATE_CHANGE_REQUEST, false)) {
            return true;
        }

        switch ($_action) {
            case self::ACTION_GET:
                if (parent::_checkGrant($_record, HumanResources_Model_DivisionGrants::READ_CHANGE_REQUEST, false) ||
                        ($this->_checkOwnEmployee($_record) &&
                            (parent::_checkGrant($_record, HumanResources_Model_DivisionGrants::CREATE_OWN_CHANGE_REQUEST, false) ||
                                parent::_checkGrant($_record, HumanResources_Model_DivisionGrants::READ_OWN_DATA, false)
                            ))) {
                    return true;
                }
                break;
            case self::ACTION_CREATE:
            case self::ACTION_UPDATE:
                if (HumanResources_Config::FREE_TIME_PROCESS_STATUS_REQUESTED === $_record->{HumanResources_Model_FreeTime::FLD_PROCESS_STATUS} &&
                        (parent::_checkGrant($_record, HumanResources_Model_DivisionGrants::CREATE_CHANGE_REQUEST, false) ||
                            (parent::_checkGrant($_record, HumanResources_Model_DivisionGrants::CREATE_OWN_CHANGE_REQUEST, false) &&
                            $this->_checkOwnEmployee($_record)))) {
                    return true;
                }
                break;
            case self::ACTION_DELETE:
                break;
        }

        throw new Tinebase_Exception_AccessDenied($_errorMessage);
    }

    protected function _checkOwnEmployee(HumanResources_Model_WTRCorrection $record): bool
    {
        $ctrl = HumanResources_Controller_Employee::getInstance();
        $oldValue = $ctrl->doContainerACLChecks(false);

        $raii = new Tinebase_RAII(function() use($oldValue, $ctrl) { $ctrl->doContainerACLChecks($oldValue); });
        $result = $ctrl->get($record->getIdFromProperty(HumanResources_Model_WTRCorrection::FLD_EMPLOYEE_ID))
                ->account_id === Tinebase_Core::getUser()->getId();
        unset($raii);
        return $result;
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->_inspect($_record);
        parent::_inspectBeforeCreate($_record);
    }

    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        $this->_inspect($_record);
        parent::_inspectBeforeUpdate($_record, $_oldRecord);
    }

    protected function _inspect($record)
    {
        /** @var HumanResources_Model_WTRCorrection $record */
        if (!$record->{HumanResources_Model_WTRCorrection::FLD_WTR_MONTHLY} &&
                !$record->{HumanResources_Model_WTRCorrection::FLD_WTR_MONTHLY}) {
            throw new Tinebase_Exception_Record_Validation('either ' .
                HumanResources_Model_WTRCorrection::FLD_WTR_MONTHLY . ' or ' .
                HumanResources_Model_WTRCorrection::FLD_WTR_MONTHLY . ' needs to be set');
        }
        if ($record->{HumanResources_Model_WTRCorrection::FLD_WTR_MONTHLY} &&
                $record->{HumanResources_Model_WTRCorrection::FLD_WTR_MONTHLY}) {
            throw new Tinebase_Exception_Record_Validation(HumanResources_Model_WTRCorrection::FLD_WTR_MONTHLY . ' and '
                . HumanResources_Model_WTRCorrection::FLD_WTR_MONTHLY . ' must not be both set');
        }
    }

    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        parent::_inspectAfterCreate($_createdRecord, $_record);
        /** @var HumanResources_Model_WTRCorrection $_createdRecord */
        $this->updateParent($_createdRecord);
    }

    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        parent::_inspectAfterUpdate($updatedRecord, $record, $currentRecord);
        /** @var HumanResources_Model_WTRCorrection $updatedRecord */
        $this->updateParent($updatedRecord);
        if ($record->getIdFromProperty(HumanResources_Model_WTRCorrection::FLD_WTR_DAILY)
                !== $currentRecord->getIdFromProperty(HumanResources_Model_WTRCorrection::FLD_WTR_DAILY) ||
                $record->getIdFromProperty(HumanResources_Model_WTRCorrection::FLD_WTR_MONTHLY)
                !== $currentRecord->getIdFromProperty(HumanResources_Model_WTRCorrection::FLD_WTR_MONTHLY)) {
            $this->updateParent($currentRecord);
        }
    }

    protected function _inspectAfterDelete(Tinebase_Record_Interface $record)
    {
        parent::_inspectAfterDelete($record);
        /** @var HumanResources_Model_WTRCorrection $record */
        $this->updateParent($record);
    }

    protected function updateParent(HumanResources_Model_WTRCorrection $record)
    {
        if ($record->{HumanResources_Model_WTRCorrection::FLD_WTR_DAILY}) {
            $ctrl = HumanResources_Controller_DailyWTReport::getInstance();
            $id = $record->getIdFromProperty(HumanResources_Model_WTRCorrection::FLD_WTR_DAILY);
        } else {
            $ctrl = HumanResources_Controller_MonthlyWTReport::getInstance();
            $id = $record->getIdFromProperty(HumanResources_Model_WTRCorrection::FLD_WTR_MONTHLY);
        }
        $ctrl->recalcCorrection($id);
    }
}
