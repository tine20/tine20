<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
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
        $config = Crm_Config::getInstance();
        $defaults = array(
            'leadstate_id'  => $config->get(defaultstate, 1),
            'leadtype_id'   => $config->get(defaulttype, 1),
            'leadsource_id' => $config->get(defaultsource, 1),
        );
        
        Crm_Config::getInstance()->set(Tinebase_Config::APPDEFAULTS, $defaults);
        
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
            Crm_Config::getInstance()->set($config['cfgId'], Zend_Json::encode($queryResult));
            
            // remove tables and constraints
            try {
                $this->_backend->dropForeignKey('metacrm_lead', $config['fkName']);
            } catch (Zend_Db_Statement_Exception $zdse) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' error dropping fk for ' . $config['table'] . ': ' . $zdse->__toString());
            }
            $this->_backend->dropTable($config['table']);
        }
        
        $this->setTableVersion('metacrm_lead', '5');
        $this->setApplicationVersion('Crm', '2.2');
    }
    
    /**
     * move ods export config to import export definitions
     * 
     * @return void
     */
    public function update_2()
    {
        // get import export definitions and save them in db
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Crm'));
        
        $this->setApplicationVersion('Crm', '3.0');
    }
}
