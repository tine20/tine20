<?php
/**
 * WageType controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * WageType controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_WageType extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = HumanResources_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => HumanResources_Model_WageType::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => HumanResources_Model_WageType::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = HumanResources_Model_WageType::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    /**
     * @param HumanResources_Model_WageType $_record
     * @param HumanResources_Model_WageType $_oldRecord
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        if ($_oldRecord->system || $_record->system) {
            throw new Tinebase_Exception_Record_NotAllowed('system fields may not be updated');
        }

        parent::_inspectBeforeUpdate($_record, $_oldRecord);
    }

    protected function _inspectDelete(array $_ids)
    {
        if ($this->getMultiple($_ids)->filter('system', true)->count() > 0) {
            throw new Tinebase_Exception_Record_NotAllowed('system fields may not be deleted');
        }
        return parent::_inspectDelete($_ids);
    }

    /**
     * check rights
     *
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        if (! $this->_doRightChecks) {
            return;
        }

        if (self::ACTION_GET !== $_action && !$this->checkRight(HumanResources_Acl_Rights::ADMIN, FALSE)) {
            throw new Tinebase_Exception_AccessDenied('You are not allowed to ' . $_action . ' free time type.');
        }
        parent::_checkRight($_action);
    }
}
