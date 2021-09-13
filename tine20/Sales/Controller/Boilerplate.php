<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * controller for Boilerplate
 *
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Boilerplate extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_doContainerACLChecks = false;
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_modelName = Sales_Model_Boilerplate::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::TABLE_NAME        => Sales_Model_Boilerplate::TABLE_NAME,
            Tinebase_Backend_Sql::MODEL_NAME        => Sales_Model_Boilerplate::class,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => true,
        ]);
    }
}
