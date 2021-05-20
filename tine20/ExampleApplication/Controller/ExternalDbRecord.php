<?php
/**
 * ExternalDbRecord controller for ExampleApplication application
 * 
 * @package     ExampleApplication
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * ExternalDbRecord controller class for ExampleApplication application
 * 
 * @package     ExampleApplication
 * @subpackage  Controller
 */
class ExampleApplication_Controller_ExternalDbRecord extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    protected function __construct()
    {
        Tinebase_Config::getInstance()->{Tinebase_Config::EXTERNAL_DATABASE} = [
            'exampleRecordsDb' => Tinebase_Config::getInstance()->database->toArray(),
        ];
        Tinebase_Config::getInstance()->{Tinebase_Config::EXTERNAL_DATABASE}->exampleRecordsDb->useUtf8mb4 = true;

        $this->_applicationName = ExampleApplication_Config::APP_NAME;
        $this->_modelName = ExampleApplication_Model_ExampleRecord::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME        => ExampleApplication_Model_ExampleRecord::class,
            Tinebase_Backend_Sql::TABLE_NAME        => ExampleApplication_Model_ExampleRecord::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => true,
        ], Tinebase_Core::getExternalDb('exampleRecordsDb'));

        $this->_purgeRecords = false;
        $this->_resolveCustomFields = true;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = false;
    }
}
