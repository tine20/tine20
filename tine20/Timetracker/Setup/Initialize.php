<?php
/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Timetracker initialization
 * 
 * @package     Setup
 */
class Timetracker_Setup_Initialize extends Setup_Initialize
{
    /**
     * init favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Timetracker')->getId(),
            'model'             => 'Timetracker_Model_TimesheetFilter',
        );
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Timesheets today", // _("Timesheets today")
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
            'name'              => "Timesheets this week", // _("Timesheets this week")
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
            'name'              => "Timesheets last week", // _("Timesheets last week")
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
            'name'              => "Timesheets this month", // _("Timesheets this month")
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
            'name'              => "Timesheets last month", // _("Timesheets last month")
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
    }
}
