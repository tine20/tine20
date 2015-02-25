<?php
/**
 * Tine 2.0
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Phone initialization
 * 
 * @package     Setup
 */
class Phone_Setup_Initialize extends Setup_Initialize
{
    /**
     * init favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Phone')->getId(),
            'model'             => 'Phone_Model_CallFilter',
        );
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Calls this week", // _("Calls this week")
            'description'       => "Incoming and outgoing calls of this week", // _("Incoming and outgoing calls of this week")
            'filters'           => array(array(
                'field'     => 'start',
                'operator'  => 'within',
                'value'     => 'weekThis',
            )),
        ))));
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Calls last week", // _("Calls last week")
            'description'       => "Incoming and outgoing calls of last week", // _("Incoming and outgoing calls of last week")
            'filters'           => array(array(
                'field'     => 'start',
                'operator'  => 'within',
                'value'     => 'weekLast',
            )),
        ))));
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Calls this month", // _("Calls this month")
            'description'       => "Incoming and outgoing calls of this month", // _("Incoming and outgoing calls of this month")
            'filters'           => array(array(
                'field'     => 'start',
                'operator'  => 'within',
                'value'     => 'monthThis',
            )),
        ))));
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Calls last month", // _("Calls last month")
            'description'       => "Incoming and outgoing calls of last month", // _("Incoming and outgoing calls of last month")
            'filters'           => array(array(
                'field'     => 'start',
                'operator'  => 'within',
                'value'     => 'monthLast',
            )),
        ))));
    }
}
