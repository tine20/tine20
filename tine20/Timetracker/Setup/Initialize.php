<?php
/**
 * Tine 2.0
 *
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Timetracker initialization
 *
 * @package     Setup
 */
class Timetracker_Setup_Initialize extends Setup_Initialize
{
    public static function addTSRequestedFavorite()
    {
        Tinebase_PersistentFilter::getInstance()->createDuringSetup(new Tinebase_Model_PersistentFilter(array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Timetracker')->getId(),
            'model'             => Timetracker_Model_Timesheet::class,
            'name'              => "Requested Timesheets", // _("Requested Timesheets")
            'description'       => "Requested Timesheets",
            'filters'           => array(array(
                'field'     => 'process_status',
                'operator'  => 'equals',
                'value'     => Timetracker_Config::TS_PROCESS_STATUS_REQUESTED,
            )),
        )));
    }

    /**
     * init favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();

        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Timetracker')->getId(),
            'model'             => 'Timetracker_Model_TimesheetFilter',
        );

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My Timesheets today", // _("My Timesheets today")
            'description'       => "My Timesheets today",
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

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My Timesheets this week", // _("My Timesheets this week")
            'description'       => "My Timesheets this week",
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

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My Timesheets last week", // _("My Timesheets last week")
            'description'       => "My Timesheets last week",
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

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My Timesheets this month", // _("My Timesheets this month")
            'description'       => "My Timesheets this month",
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

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My Timesheets last month", // _("My Timesheets last month")
            'description'       => "My Timesheets last month",
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

        static::addTSRequestedFavorite();
        
        // Timeaccounts
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Timetracker')->getId(),
            'model'             => 'Timetracker_Model_TimeaccountFilter',
        );

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'              => "Timeaccounts to bill", // _('Timeaccounts to bill')
                'description'       => "Timeaccounts to bill",
                'filters'           => array(
                    array(
                        'field'     => 'status',
                        'operator'  => 'equals',
                        'value'     => 'to bill',
                    )
                ),
            ))
        ));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'              => "Timeaccounts not yet billed", // _('Timeaccounts not yet billed')
                'description'       => "Timeaccounts not yet billed",
                'filters'           => array(
                    array(
                        'field'     => 'status',
                        'operator'  => 'equals',
                        'value'     => 'not yet billed',
                    )
                ),
            ))
        ));

        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'              => "Timeaccounts already billed", // _('Timeaccounts already billed')
                'description'       => "Timeaccounts already billed",
                   'filters'           => array(
                    array(
                        'field'     => 'status',
                        'operator'  => 'equals',
                        'value'     => 'billed',
                    )
                ),
            ))
        ));
        
    }
}
