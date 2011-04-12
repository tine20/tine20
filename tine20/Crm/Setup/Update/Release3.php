<?php
/**
 * Tine 2.0
 *
 * @package     Crm
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

class Crm_Setup_Update_Release3 extends Setup_Update_Abstract
{
    /**
     * update from 3.0 -> 3.1
     * - add new xls export definition
     * 
     * @return void
     */
    public function update_0()
    {
        // get import export definitions and save them in db
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Crm'));
        
        $this->setApplicationVersion('Crm', '3.1');
    }
    
    /**
     * create default persistent filters
     */
    public function update_1()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $myEventsPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array(
            'name'              => Crm_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All leads I have read grants for", // _("All leads I have read grants for")
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Crm')->getId(),
            'model'             => 'Crm_Model_LeadFilter',
            'filters'           => array(),
        )));
        
        $this->setApplicationVersion('Crm', '3.2');
    }
    
    /**
     * add status to default persistent filter
     */
    public function update_2()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        $defaultFavorite = $pfe->getByProperty(Crm_Preference::DEFAULTPERSISTENTFILTER_NAME, 'name');
        $defaultFavorite->bypassFilters = TRUE;
        
        $closedStatus = Crm_Controller::getInstance()->getConfigSettings()->getEndedLeadstates(TRUE);
        
        $defaultFavorite->filters =  array(
            array('field' => 'leadstate_id',    'operator' => 'notin',  'value' => $closedStatus),
        );
        $pfe->update($defaultFavorite);
        
        $this->setApplicationVersion('Crm', '3.3');
    }
    
    /**
     * add more default favorites
     */
    public function update_3()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Crm')->getId(),
            'model'             => 'Crm_Model_LeadFilter',
        );
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Last modified by me",
            'description'       => "All leads that I have last modified",
            'filters'           => array(array(
                'field'     => 'last_modified_by',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            )),
        ))));

        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My leads",
            'description'       => "All leads that I am responsible for",
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
            'name'              => "Leads with overdue tasks",
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
        
        $this->setApplicationVersion('Crm', '3.4');
    }

    /**
     * update to 4.0
     * @return void
     */
    public function update_4()
    {
        $this->setApplicationVersion('Crm', '4.0');
    }
}
