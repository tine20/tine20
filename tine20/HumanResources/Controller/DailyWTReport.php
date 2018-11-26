<?php
/**
 * DailyWorkingTimeReport controller for HumanResources application
 * 
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * DailyWorkingTimeReport controller class for HumanResources application
 * 
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_DailyWTReport extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_applicationName = 'HumanResources';
        $this->_modelName = HumanResources_Model_DailyWTReport::class;
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName,
            'tableName' => 'humanresources_wt_dailyreport',
            'modlogActive' => true
        ));

        $this->_purgeRecords = false;
        $this->_resolveCustomFields = true;
        $this->_doContainerACLChecks = false;
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var HumanResources_Controller_DailyWTReport
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return HumanResources_Controller_DailyWTReport
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
}
