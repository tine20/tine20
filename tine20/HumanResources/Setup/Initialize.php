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
        // create type config
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config', 
            'tableName' => 'config',
        ));
        $appId = Tinebase_Application::getInstance()->getApplicationByName('HumanResources')->getId();
        
        $freeTimeTypeConfig = array(
            'name'    => HumanResources_Config::FREETIME_TYPE,
            'records' => array(
                array('id' => 'SICKNESS', 'value' => 'Sickness', 'states' => array('REQUESTED', 'IN-PROCESS', 'ACCEPTED', 'DECLINED'), 'icon' => 'images/oxygen/16x16/actions/book.png', 'system' => true),  //_('Sickness')
                array('id' => 'VACATION', 'value' => 'Vacation', 'states' => array('REQUESTED', 'ACCEPTED'), 'icon' => 'images/oxygen/16x16/actions/book2.png', 'system' => true),  //_('Vacation')
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => HumanResources_Config::FREETIME_TYPE,
            'value'             => json_encode($freeTimeTypeConfig),
        )));
        
        // create status config
        $freeTimeStatusConfig = array(
            'name'    => HumanResources_Config::FREETIME_STATUS,
            'records' => array(
                array('id' => 'REQUESTED',  'value' => 'Requested',  'icon' => 'images/oxygen/16x16/actions/mail-mark-unread-new.png', 'system' => true),  //_('Requested')
                array('id' => 'IN-PROCESS', 'value' => 'In process', 'icon' => 'images/oxygen/16x16/actions/view-refresh.png',         'system' => true),  //_('In process')
                array('id' => 'ACCEPTED',   'value' => 'Accepted',   'icon' => 'images/oxygen/16x16/actions/ok.png', 'system' => true),  //_('Accepted')
                array('id' => 'DECLINED',   'value' => 'Declined',   'icon' => 'images/oxygen/16x16/actions/dialog-cancel.png', 'system' => true),  //_('Declined')
                
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => HumanResources_Config::FREETIME_STATUS,
            'value'             => json_encode($freeTimeStatusConfig),
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
