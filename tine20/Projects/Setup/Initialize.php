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
     * init favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Projects')->getId(),
            'model'             => 'Projects_Model_ProjectFilter',
        );

        $closedStatus = Projects_Config::getInstance()->get(Projects_Config::PROJECT_STATUS)->records->filter('is_open', 0);
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
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

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
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
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
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
