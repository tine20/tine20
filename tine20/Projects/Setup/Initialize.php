<?php
/**
 * Tine 2.0
 * 
 * @package     Projects
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Projects initialization
 * 
 * @package     Setup
 */
class Projects_Setup_Initialize extends Setup_Initialize
{
    /**
     * init key fields
     */
    protected function _initializeKeyFields()
    {
        // create status config
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config', 
            'tableName' => 'config',
        ));
        $appId = Tinebase_Application::getInstance()->getApplicationByName('Projects')->getId();
        
        $projectsStatusConfig = array(
            'name'    => Projects_Config::PROJECT_STATUS,
            'records' => array(
                array('id' => 'COMPLETED',    'value' => 'Completed',   'is_open' => 0, 'icon' => 'images/oxygen/16x16/actions/ok.png',                   'system' => true), //_('Completed')
                array('id' => 'CANCELLED',    'value' => 'Cancelled',   'is_open' => 0, 'icon' => 'images/oxygen/16x16/actions/dialog-cancel.png',        'system' => true), //_('Cancelled')
                array('id' => 'IN-PROCESS',   'value' => 'In process',  'is_open' => 1, 'icon' => 'images/oxygen/16x16/actions/view-refresh.png',         'system' => true), //_('In process')
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => Projects_Config::PROJECT_STATUS,
            'value'             => json_encode($projectsStatusConfig),
        )));

        $projectsAttendeeRoleConfig = array(
            'name'    => Projects_Config::PROJECT_ATTENDEE_ROLE,
            'records' => array(
                array('id' => 'COWORKER',    'value' => 'Coworker',    'icon' => 'images/oxygen/16x16/actions/ok.png',                   'system' => true), //_('Coworker')
                array('id' => 'RESPONSIBLE', 'value' => 'Responsible', 'icon' => 'images/oxygen/16x16/actions/dialog-cancel.png',        'system' => true), //_('Responsible')
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => Projects_Config::PROJECT_ATTENDEE_ROLE,
            'value'             => json_encode($projectsAttendeeRoleConfig),
        )));
    }
}
