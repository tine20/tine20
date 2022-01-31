<?php
/**
 * FreeDay controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * FreeDay controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_FreeDay extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;
    use HumanResources_Controller_CheckFilterACLEmployeeTrait;

    protected $_getMultipleGrant = [HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA];
    protected $_requiredFilterACLget = [HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA];
    protected $_requiredFilterACLupdate  = [HumanResources_Model_DivisionGrants::UPDATE_EMPLOYEE_DATA];
    protected $_requiredFilterACLsync  = [HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA];
    protected $_requiredFilterACLexport  = [HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA];

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct() {
        $this->_applicationName = 'HumanResources';
        $this->_backend = new HumanResources_Backend_FreeDay();
        $this->_modelName = HumanResources_Model_FreeDay::class;
        $this->_purgeRecords = TRUE;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = true;
        $this->_traitDelegateAclField = 'freetime_id';
    }

    protected function _getCheckFilterACLTraitFilter()
    {
        return new Tinebase_Model_Filter_ForeignId('freetime_id', 'definedBy', [
            ['field' => 'employee_id', 'operator' => 'definedBy', 'value' => [
                    ['field' => 'account_id', 'operator' => 'equals', 'value' => Tinebase_Core::getUser()->getId()],
                ],
            ]], [
                'controller' => HumanResources_Controller_FreeTime::class,
                'filtergroup' => HumanResources_Model_FreeTimeFilter::class,
            ]);
    }

    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        if (!$this->_doContainerACLChecks) {
            return true;
        }

        // if we have manage_employee right, we have all grants
        if (Tinebase_Core::getUser()->hasRight(HumanResources_Config::APP_NAME, HumanResources_Acl_Rights::MANAGE_EMPLOYEE)) {
            return true;
        }

        switch ($_action) {
            case self::ACTION_GET:
                try {
                    HumanResources_Controller_FreeTime::getInstance()->get($_record->getIdFromProperty('freetime_id'));
                } catch (Tinebase_Exception_AccessDenied $e) {
                    if ($_throw) {
                        throw new Tinebase_Exception_AccessDenied($_errorMessage);
                    } else {
                        return false;
                    }
                }
                return true;
            case self::ACTION_CREATE:
            case self::ACTION_UPDATE:
            case self::ACTION_DELETE:
                $_action = HumanResources_Model_DivisionGrants::UPDATE_EMPLOYEE_DATA;
                break;
        }
        return parent::_checkGrant($_record, $_action, $_throw, $_errorMessage, $_oldRecord);
    }
}
