<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
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
            'description'       => "All contacts I have read grants for", // _("All contacts I have read grants for")
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
        
        $closedStatus = Crm_Controller::getInstance()->getSettings()->getEndedLeadstates(TRUE);
        
        $defaultFavorite->filters =  array(
            array('field' => 'leadstate_id',    'operator' => 'notin',  'value' => $closedStatus),
        );
        $pfe->update($defaultFavorite);
        
        $this->setApplicationVersion('Crm', '3.3');
    }
}
