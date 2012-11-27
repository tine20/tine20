<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Timetracker_Setup_Update_Release3 extends Setup_Update_Abstract
{
    /**
     * move ods export config to config table
     * -> disabled / we don't need that any more (@see update_1)
     * 
     * @return void
     */
    public function update_0()
    {
        $this->setApplicationVersion('Timetracker', '3.1');
    }

    /**
     * move ods export config to import export definitions
     * 
     * @return void
     */
    public function update_1()
    {
        // remove Tinebase_Config::ODSEXPORTCONFIG
        Tinebase_Config::getInstance()->delete('odsexportconfig');
        
        // get import export definitions and save them in db
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Timetracker'));
        
        $this->setApplicationVersion('Timetracker', '3.2');
    }
    
    /**
     * added more default favorites
     * 
     * @return void
     */
    public function update_2()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Timetracker')->getId(),
            'model'             => 'Timetracker_Model_TimesheetFilter',
        );
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Timesheets today",
            'description'       => "Timesheets today",
            'filters'           => array(array(
                'field'     => 'account_id',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            ), array(
                'field'     => 'start_date',
                'operator'  => 'within',
                'value'     => 'dayThis',
            )),
        ))));

        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Timesheets this week",
            'description'       => "Timesheets this week",
            'filters'           => array(array(
                'field'     => 'account_id',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            ), array(
                'field'     => 'start_date',
                'operator'  => 'within',
                'value'     => 'weekThis',
            )),
        ))));
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Timesheets last week",
            'description'       => "Timesheets last week",
            'filters'           => array(array(
                'field'     => 'account_id',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            ), array(
                'field'     => 'start_date',
                'operator'  => 'within',
                'value'     => 'weekLast',
            )),
        ))));

        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Timesheets this month",
            'description'       => "Timesheets this month",
            'filters'           => array(array(
                'field'     => 'account_id',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            ), array(
                'field'     => 'start_date',
                'operator'  => 'within',
                'value'     => 'monthThis',
            )),
        ))));

        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Timesheets last month",
            'description'       => "Timesheets last month",
            'filters'           => array(array(
                'field'     => 'account_id',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            ), array(
                'field'     => 'start_date',
                'operator'  => 'within',
                'value'     => 'monthLast',
            )),
        ))));
                
        $this->setApplicationVersion('Timetracker', '3.3');
    }
    
    /**
     * update from 3.0 -> 4.0
     * @return void
     */
    public function update_3()
    {
        $this->setApplicationVersion('Timetracker', '4.0');
    }
}
