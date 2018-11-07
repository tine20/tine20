<?php
/**
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Meeting controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_Break extends Tinebase_Controller_Record_Abstract
{
    private function __construct()
    {
        $this->_applicationName = 'HumanResources';
        $this->_modelName = 'HumanResources_Model_Break';
        $this->_purgeRecords = FALSE;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = FALSE;
        $this->_resolveCustomFields = FALSE;
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName,
            'tableName' => 'humanresources_breaks',
            'modlogActive' => false,
        ));
    }

    /**
     * holds the instance of the singleton
     *
     * @var HumanResources_Controller_Meeting
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return HumanResources_Controller_Meeting
     */
    public static function getInstance()
    {
        if (static::$_instance === NULL) {
            static::$_instance = new self();
        }

        return static::$_instance;
    }
}