<?php
/**
 * Tine 2.0
  * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Crm initialization
 * 
 * @package     Crm
 */
class Crm_Setup_Initialize extends Setup_Initialize
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

        $roles = Tinebase_Acl_Roles::getInstance();
        $userRole = $roles->getRoleByName('user role');
        $roles->addSingleRight(
            $userRole->getId(), 
            $_application->getId(), 
            Crm_Acl_Rights::MANAGE_LEADS
        );
    }
    
    /**
     * init favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Crm')->getId(),
            'model'             => 'Crm_Model_LeadFilter',
        );
        
        $closedStatus = Crm_Controller::getInstance()->getConfigSettings()->getEndedLeadstates(TRUE);
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => Crm_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All leads I have read grants for", // _("All leads I have read grants for")
            'filters'           => array(
                array('field' => 'leadstate_id',    'operator' => 'notin',  'value' => $closedStatus),
            ),
        ))));
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Last modified by me", // _("Last modified by me")
            'description'       => "All leads that I have last modified", // _("All leads that I have last modified")
            'filters'           => array(array(
                'field'     => 'last_modified_by',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            )),
        ))));

        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My leads",                              // _("My leads")
            'description'       => "All leads that I am responsible for",   // _("All leads that I am responsible for")
            'filters'           => array(array(
                'field'     => 'contact',
                'operator'  => 'AND',
                'value'     => array(array(
                    'field'     => 'id',
                    'operator'  => 'equals',
                    'value'     => Addressbook_Model_Contact::CURRENTCONTACT,
                ))
            )),
        ))));

        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Leads with overdue tasks", // _("Leads with overdue tasks")
            'description'       => "Leads with overdue tasks",
            'filters'           => array(array(
                'field'     => 'task',
                'operator'  => 'AND',
                'value'     => array(array(
                    'field'     => 'due',
                    'operator'  => 'before',
                    'value'     => 'dayThis',
                ))
            )),
        ))));
    }
}
