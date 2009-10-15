<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: Release0.php 2759 2008-06-10 15:52:56Z nelius_weiss $
 */

class Crm_Setup_Update_Release2 extends Setup_Update_Abstract
{
    /**
     * update from 2.0 -> 2.1
     * - get crm default settings from config and write them to db 
     * 
     * @return void
     */
    public function update_0()
    {
        $config = Tinebase_Core::getConfig();
    	if (isset($config->crm)) {
    	    $defaults = array(
                'leadstate_id'  => (isset($config->crm->defaultstate))  ? Tinebase_Core::getConfig()->crm->defaultstate     : 1,
                'leadtype_id'   => (isset($config->crm->defaulttype))   ? Tinebase_Core::getConfig()->crm->defaulttype      : 1,
                'leadsource_id' => (isset($config->crm->defaultsource)) ? Tinebase_Core::getConfig()->crm->defaultsource    : 1,    	    
    	    );
    	    
    	    Tinebase_Config::getInstance()->setConfigForApplication(Tinebase_Model_Config::APPDEFAULTS, Zend_Json::encode($defaults), 'Crm');
    	}
    	
    	$this->setApplicationVersion('Crm', '2.1');
    }

    /**
     * update from 2.1 -> 2.2
     * - move leadstates/types/sources to config and remove tables
     * 
     * @return void
     */
    public function update_1()
    {
        // get from db
        $json = new Crm_Frontend_Json();
        $leadtypes      = $json->getLeadtypes('leadtype','ASC');
        $leadstates     = $json->getLeadStates('leadstate','ASC');
        $leadsources    = $json->getLeadSources('leadsource','ASC');
        
        // save to config
        Tinebase_Config::getInstance()->setConfigForApplication(Crm_Model_Config::LEADTYPES, Zend_Json::encode($leadtypes['results']), 'Crm');
        Tinebase_Config::getInstance()->setConfigForApplication(Crm_Model_Config::LEADSTATES, Zend_Json::encode($leadstates['results']), 'Crm');
        Tinebase_Config::getInstance()->setConfigForApplication(Crm_Model_Config::LEADSOURCES, Zend_Json::encode($leadsources['results']), 'Crm');
        
        // remove tables and constraints
        $this->_backend->dropForeignKey('metacrm_lead', 'metacrm_lead::leadsource_id--metacrm_leadsource::id');
        $this->_backend->dropForeignKey('metacrm_lead', 'metacrm_lead::leadtype_id--metacrm_leadtype::id');
        $this->_backend->dropForeignKey('metacrm_lead', 'metacrm_lead::leadstate_id--metacrm_leadstate::id');
        $this->_backend->dropTable('metacrm_leadsource');
        $this->_backend->dropTable('metacrm_leadtype');
        $this->_backend->dropTable('metacrm_leadstate');
        
        $this->setTableVersion('metacrm_lead', '5');
        $this->setApplicationVersion('Crm', '2.2');
    }
}
