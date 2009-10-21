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
        $toUpdate = array(
            'leadtype' => array(
                'table'     => 'metacrm_leadtype',
                'cfgId'     => Crm_Model_Config::LEADTYPES,
                'fkName'    => 'metacrm_lead::leadtype_id--metacrm_leadtype::id',
            ), 
            'leadstate' => array(
                'table'     => 'metacrm_leadstate',
                'cfgId'     => Crm_Model_Config::LEADSTATES,
                'fkName'    => 'metacrm_lead::leadstate_id--metacrm_leadstate::id',
            ), 
            'leadsource' => array(
                'table'     => 'metacrm_leadsource',
                'cfgId'     => Crm_Model_Config::LEADSOURCES,
                'fkName'    => 'metacrm_lead::leadsource_id--metacrm_leadsource::id',
            ),
        );
        
        foreach ($toUpdate as $config) {
            // get from db
            $select = $this->_db->select()
                ->from(SQL_TABLE_PREFIX . $config['table']);
            $stmt = $this->_db->query($select);
            $queryResult = $stmt->fetchAll();

            // save to config
            Tinebase_Config::getInstance()->setConfigForApplication($config['cfgId'], Zend_Json::encode($queryResult), 'Crm');
            
            // remove tables and constraints
            $this->_backend->dropForeignKey('metacrm_lead', $config['fkName']);
            $this->_backend->dropTable($config['table']);
        }
        
        $this->setTableVersion('metacrm_lead', '5');
        $this->setApplicationVersion('Crm', '2.2');
    }
}
