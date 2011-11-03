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
                array('id' => 'NEEDS-ACTION', 'value' => 'On hold',     'is_open' => 1, 'icon' => 'images/oxygen/16x16/actions/mail-mark-unread-new.png', 'system' => true),  //_('On hold')
                array('id' => 'COMPLETED',    'value' => 'Completed',   'is_open' => 0, 'icon' => 'images/oxygen/16x16/actions/ok.png',                   'system' => true),  //_('Completed')
                array('id' => 'CANCELLED',    'value' => 'Cancelled',   'is_open' => 0, 'icon' => 'images/oxygen/16x16/actions/dialog-cancel.png',        'system' => true),  //_('Cancelled')
                array('id' => 'IN-PROCESS',   'value' => 'In process',  'is_open' => 1, 'icon' => 'images/oxygen/16x16/actions/view-refresh.png',         'system' => true),  //_('In process')
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
                array('id' => 'COWORKER',    'value' => 'Coworker',    'icon' => 'images/oxygen/16x16/apps/system-users.png',               'system' => true), //_('Coworker')
                array('id' => 'RESPONSIBLE', 'value' => 'Responsible', 'icon' => 'images/oxygen/16x16/apps/preferences-desktop-user.png',   'system' => true), //_('Responsible')
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => Projects_Config::PROJECT_ATTENDEE_ROLE,
            'value'             => json_encode($projectsAttendeeRoleConfig),
        )));
    }
    
    /**
     * init favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Projects')->getId(),
            'model'             => 'Projects_Model_ProjectFilter',
        );
        
        $closedStatus = Projects_Config::getInstance()->get(Projects_Config::PROJECT_STATUS)->records->filter('is_open', 0);
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => Projects_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All my open projects", // _("All my open projects")
            'filters'           => array(
                array(
                    'field'     => 'contact',
                    'operator'  => 'AND',
                    'value'     => array(array(
                        'field'     => ':relation_type',
                        'operator'  => 'in',
                        'value'     => Projects_Config::getInstance()->get(Projects_Config::PROJECT_ATTENDEE_ROLE)->records->id
                    ), array(
                        'field'     => ':id',
                        'operator'  => 'equals',
                        'value'     => Addressbook_Model_Contact::CURRENTCONTACT,
                    )
                )),
                array('field' => 'status',    'operator' => 'notin',  'value' => $closedStatus->getId()),
            )
        ))));

        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My projects that I'm responsible for",           // _("My projects that I'm responsible for")
            'description'       => "All my open projects that I am responsible for", // _("All my open projects that I am responsible for")
            'filters'           => array(
                array(
                    'field'     => 'contact',
                    'operator'  => 'AND',
                    'value'     => array(array(
                        'field'     => ':relation_type',
                        'operator'  => 'in',
                        'value'     => array('RESPONSIBLE')
                    ), array(
                        'field'     => ':id',
                        'operator'  => 'equals',
                        'value'     => Addressbook_Model_Contact::CURRENTCONTACT,
                    )
                )),
                array('field' => 'status',    'operator' => 'notin',  'value' => $closedStatus->getId()),
            )
        ))));
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My waiting projects",          // _("My waiting projects")
            'description'       => "My projects that are on hold", // _("My projects that are on hold")
            'filters'           => array(
                array(
                    'field'     => 'contact',
                    'operator'  => 'AND',
                    'value'     => array(array(
                        'field'     => ':relation_type',
                        'operator'  => 'in',
                        'value'     => Projects_Config::getInstance()->get(Projects_Config::PROJECT_ATTENDEE_ROLE)->records->id
                    ), array(
                        'field'     => ':id',
                        'operator'  => 'equals',
                        'value'     => Addressbook_Model_Contact::CURRENTCONTACT,
                    )
                )),
                array('field' => 'status',    'operator' => 'in',  'value' => array('NEEDS-ACTION')),
            )
        ))));
    }
}
