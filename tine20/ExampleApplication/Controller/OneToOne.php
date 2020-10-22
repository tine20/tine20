<?php
/**
 * OneToOne controller for ExampleApplication application
 * 
 * @package     ExampleApplication
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * OneToOne controller class for ExampleApplication application
 * 
 * @package     ExampleApplication
 * @subpackage  Controller
 */
class ExampleApplication_Controller_OneToOne extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    protected function __construct()
    {
        $this->_applicationName = ExampleApplication_Config::APP_NAME;
        $this->_modelName = ExampleApplication_Model_OneToOne::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME    => ExampleApplication_Model_OneToOne::class,
            Tinebase_Backend_Sql::TABLE_NAME    => ExampleApplication_Model_OneToOne::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE => true
        ]);

        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }
}
