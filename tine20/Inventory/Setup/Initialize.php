<?php
/**
 * Tine 2.0
 * 
 * @package     Inventory
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
        
        $statusConfig = array(
            'name'    => Inventory_Config::INVENTORY_STATUS,
            'records' => array(
                array('id' => 'COMPLETED',    'value' => 'Completed',   'is_open' => 0, 'icon' => 'images/oxygen/16x16/actions/ok.png',                   'system' => true), //_('Completed')
                array('id' => 'CANCELLED',    'value' => 'Cancelled',   'is_open' => 0, 'icon' => 'images/oxygen/16x16/actions/dialog-cancel.png',        'system' => true), //_('Cancelled')
                array('id' => 'IN-PROCESS',   'value' => 'In process',  'is_open' => 1, 'icon' => 'images/oxygen/16x16/actions/view-refresh.png',         'system' => true), //_('In process')
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Inventory')->getId(),
            'name'              => Inventory_Config::INVENTORY_STATUS,
            'value'             => json_encode($statusConfig),
        )));
    }    
}
