<?php declare(strict_types=1);
/**
 * AttendanceRecorderDeviceRef Controller
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * AttendanceRecorderDeviceRef Controller
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_AttendanceRecorderDeviceRef extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct() {
        $this->_applicationName = HumanResources_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME    => HumanResources_Model_AttendanceRecorderDeviceRef::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true,
            Tinebase_Backend_Sql::MODEL_NAME    => HumanResources_Model_AttendanceRecorderDeviceRef::class,
        ]);
        $this->_modelName = HumanResources_Model_AttendanceRecorderDeviceRef::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }
}
