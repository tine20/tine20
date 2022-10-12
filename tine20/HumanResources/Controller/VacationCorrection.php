<?php
/**
 * VacationCorrection controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * VacationCorrection controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_VacationCorrection extends Tinebase_Controller_Record_Abstract
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
        $this->_modelName = HumanResources_Model_VacationCorrection::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => $this->_modelName,
            Tinebase_Backend_Sql::TABLE_NAME    => HumanResources_Model_VacationCorrection::TABLE_NAME,
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
        /** @var HumanResources_Model_VacationCorrection $_record */
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
                if (HumanResources_Config::WTR_CORRECTION_STATUS_REQUESTED === $_record->{HumanResources_Model_WTRCorrection::FLD_STATUS} &&
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

    protected function _checkOwnEmployee(HumanResources_Model_VacationCorrection $record): bool
    {
        $ctrl = HumanResources_Controller_Employee::getInstance();
        $oldValue = $ctrl->doContainerACLChecks(false);

        $raii = new Tinebase_RAII(function() use($oldValue, $ctrl) { $ctrl->doContainerACLChecks($oldValue); });
        $result = $ctrl->get($record->getIdFromProperty(HumanResources_Model_VacationCorrection::FLD_EMPLOYEE_ID))
                ->account_id === Tinebase_Core::getUser()->getId();
        unset($raii);
        return $result;
    }
}
