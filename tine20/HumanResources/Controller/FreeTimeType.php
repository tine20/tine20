<?php
/**
 * FreeTimeType controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * FreeTimeType controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_FreeTimeType extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = HumanResources_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => HumanResources_Model_FreeTimeType::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => HumanResources_Model_FreeTimeType::TABLE_NAME,
        ]);
        $this->_modelName = HumanResources_Model_FreeTimeType::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }
}