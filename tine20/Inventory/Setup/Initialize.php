<?php
/**
 * Tine 2.0
 * 
 * @package     Inventory
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Inventory initialization
 * 
 * @package     Setup
 */
class Inventory_Setup_Initialize extends Setup_Initialize
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
        
        $typeConfig = array(
            'name'    => Inventory_Config::INVENTORY_STATUS,
            'records' => array(
                array('id' => 'ORDERED',    'value' => 'ordered'                       ), //_('ordered')
                array('id' => 'AVAILABLE',  'value' => 'available', 'system' => true   ), //_('available')
                array('id' => 'DEFECT',     'value' => 'defect'                        ), //_('defect')
                array('id' => 'MISSING',    'value' => 'missing'                       ), //_('missing')
                array('id' => 'REMOVED',    'value' => 'removed', 'system' => true     ), //_('removed')
                array('id' => 'UNKNOWN',    'value' => 'unknown'                       ), //_('unknown')
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Inventory')->getId(),
            'name'              => Inventory_Config::INVENTORY_STATUS,
            'value'             => json_encode($typeConfig),
        )));
        
    }
}
