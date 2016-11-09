<?php
/**
 * Tine 2.0
 * 
 * @package     Events
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Events initialization
 * 
 * @package     Setup
 */
class Events_Setup_Initialize extends Setup_Initialize
{
    /**
     * init the default persistentfilters
     */
    protected function _initializeFavorites()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();
            
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Events')->getId(),
            'model'             => 'Events_Model_EventFilter',
        );
        
        // default persistent filter for all records
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "All Events", // _("All Events")
            'description'       => "All existing Events", // _("All existing Events")
            'filters'           => array(),
        ))));
    }
}
