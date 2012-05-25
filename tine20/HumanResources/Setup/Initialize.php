<?php
/**
 * Tine 2.0
 * 
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Tinebase initialization
 * 
 * @package     HumanResources
 */
class HumanResources_Setup_Initialize extends Setup_Initialize
{
    /**
     * init key fields
     */
    function _initializeKeyfields()
    {
        // create status config
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config', 
            'tableName' => 'config',
        ));
        $appId = Tinebase_Application::getInstance()->getApplicationByName('HumanResources')->getId();
        
        $freeTimeTypeConfig = array(
            'name'    => HumanResources_Config::FREETIME_TYPE,
            'records' => array(
                array('id' => 'SICKNESS', 'value' => 'Sickness', 'is_open' => 1, 'icon' => 'images/oxygen/16x16/actions/book.png', 'system' => true),  //_('On hold')
                array('id' => 'VACATION', 'value' => 'Vacation', 'is_open' => 0, 'icon' => 'images/oxygen/16x16/actions/book2.png', 'system' => true),  //_('Completed')
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => HumanResources_Config::FREETIME_TYPE,
            'value'             => json_encode($freeTimeTypeConfig),
        )));
    }
    
    function _initializeWorkingTimeModels()
    {
        $_record = new HumanResources_Model_WorkingTime(array(
            'title' => 'Vollzeit 40 Stunden',
            'working_hours' => '40',
            'type'  => 'static',
            'json'  => ''
            ));
        HumanResources_Controller_WorkingTime::getInstance()->create($_record);
        $_record = new HumanResources_Model_WorkingTime(array(
            'title' => 'Vollzeit 37,5 Stunden',
            'working_hours' => '37.5',
            'type'  => 'static',
            'json'  => ''
            ));
        HumanResources_Controller_WorkingTime::getInstance()->create($_record);
        $_record = new HumanResources_Model_WorkingTime(array(
            'title' => 'Teilzeit 20 Stunden',
            'working_hours' => '20',
            'type'  => 'static',
            'json'  => ''
            ));
        HumanResources_Controller_WorkingTime::getInstance()->create($_record);
    }
}
