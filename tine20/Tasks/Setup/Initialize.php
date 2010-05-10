<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: TineInitial.php 9535 2009-07-20 10:30:05Z p.schuele@metaways.de $
 *
 */

/**
 * class for Tasks initialization
 * 
 * @package     Setup
 */
class Tasks_Setup_Initialize extends Setup_Initialize
{
    /**
     * Override method because this app requires special rights
     * @see tine20/Setup/Setup_Initialize#_createInitialRights($_application)
     * 
     * @todo make hard coded role name ('user role') configurable
     */
    protected function _createInitialRights(Tinebase_Model_Application $_application)
    {
        parent::_createInitialRights($_application);
        $this->_initializeFavorites(); 
    }
    
    protected function _initializeFavorites()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $myEventsPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array(
            'name'              => Tasks_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All my tasks", // _("All my tasks")
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
            'model'             => 'Tasks_Model_TaskFilter',
            'filters'           => array(),
        )));
    }
}