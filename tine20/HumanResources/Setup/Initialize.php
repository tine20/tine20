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
     * create favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('HumanResources')->getId(),
            'model'             => 'HumanResources_Model_EmployeeFilter',
        );
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Currently employed employee", // _("Currently employed")
            'description'       => "Employees which are currently employed", // _("Employees which are currently employed")
            'filters'           => array(array('field' => 'is_employed', 'operator' => 'equals', 'value' => 1)),
        ))));
    }
    
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
                array('id' => 'SICKNESS',             'value' => 'Sickness',           'icon' => 'images/oxygen/16x16/actions/book.png',  'system' => true),  //_('Sickness')
                array('id' => 'VACATION',             'value' => 'Vacation',           'icon' => 'images/oxygen/16x16/actions/book2.png', 'system' => true),  //_('Vacation')
                array('id' => 'VACATION_REMAINING',   'value' => 'Remaining Vacation', 'icon' => 'images/oxygen/16x16/actions/book2.png', 'system' => true),  //_('Remaining Vacation')
                array('id' => 'VACATION_EXTRA',       'value' => 'Extra Vacation',     'icon' => 'images/oxygen/16x16/actions/book2.png', 'system' => true),  //_('Extra Vacation')
            ),
        );

        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => HumanResources_Config::FREETIME_TYPE,
            'value'             => json_encode($freeTimeTypeConfig),
        )));

        // create vacation status config
        $vacationStatusConfig = array(
            'name'    => HumanResources_Config::VACATION_STATUS,
            'records' => array(
                array('id' => 'REQUESTED',  'value' => 'Requested',  'icon' => 'images/oxygen/16x16/actions/mail-mark-unread-new.png', 'system' => true),  //_('Requested')
                array('id' => 'IN-PROCESS', 'value' => 'In process', 'icon' => 'images/oxygen/16x16/actions/view-refresh.png',         'system' => true),  //_('In process')
                array('id' => 'ACCEPTED',   'value' => 'Accepted',   'icon' => 'images/oxygen/16x16/actions/ok.png', 'system' => true),  //_('Accepted')
                array('id' => 'DECLINED',   'value' => 'Declined',   'icon' => 'images/oxygen/16x16/actions/dialog-cancel.png', 'system' => true),  //_('Declined')

            ),
        );

        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => HumanResources_Config::VACATION_STATUS,
            'value'             => json_encode($vacationStatusConfig),
        )));
        
        // create sickness status config
        $sicknessStatusConfig = array(
            'name'    => HumanResources_Config::SICKNESS_STATUS,
            'records' => array(
                array('id' => 'EXCUSED',   'value' => 'Excused',   'icon' => 'images/oxygen/16x16/actions/smiley.png', 'system' => true),  //_('Excused')
                array('id' => 'UNEXCUSED', 'value' => 'Unexcused', 'icon' => 'images/oxygen/16x16/actions/tools-report-bug.png', 'system' => true),  //_('Unexcused')

            ),
        );

        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => HumanResources_Config::SICKNESS_STATUS,
            'value'             => json_encode($sicknessStatusConfig),
        )));
    }

    /**
     * init example workingtime models
     */
    function _initializeWorkingTimeModels()
    {
        $_record = new HumanResources_Model_WorkingTime(array(
            'title' => 'Vollzeit 40 Stunden',
            'working_hours' => '40',
            'type'  => 'static',
            'json'  => '{"days":[8,8,8,8,8,0,0]}'
        ));
        HumanResources_Controller_WorkingTime::getInstance()->create($_record);
        $_record = new HumanResources_Model_WorkingTime(array(
            'title' => 'Vollzeit 37,5 Stunden',
            'working_hours' => '37.5',
            'type'  => 'static',
            'json'  => '{"days":[8,8,8,8,5.5,0,0]}'
        ));
        HumanResources_Controller_WorkingTime::getInstance()->create($_record);
        $_record = new HumanResources_Model_WorkingTime(array(
            'title' => 'Teilzeit 20 Stunden',
            'working_hours' => '20',
            'type'  => 'static',
            'json'  => '{"days":[4,4,4,4,4,0,0]}'
        ));
        HumanResources_Controller_WorkingTime::getInstance()->create($_record);
    }
}
