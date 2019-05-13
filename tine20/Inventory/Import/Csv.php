<?php
/**
 * Tine 2.0
 *
 * @package     Inventory
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2013-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the inventory
 *
 * @package     Inventory
 * @subpackage  Import
 *
 * @property Inventory_Controller_InventoryItem $_controller NOT public, workaround to typecast protected member
 */
class Inventory_Import_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * additional config options
     *
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id'      => '',
        'dates' => array('added_date','warranty','invoice_date'),
    );
    
    /**
     * constructs a new importer from given config
     *
     * @param array $_options
     */
    public function __construct(array $_options = array())
    {
        parent::__construct($_options);
        
        // get container id from default container if not set
        if (empty($this->_options['container_id'])) {
            /** @var Tinebase_Record_Interface $defaultContainer */
            $defaultContainer = $this->_controller->getDefaultInventory();
            $this->_options['container_id'] = $defaultContainer->getId();
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting default container id: ' . $this->_options['container_id']);
        }
    }
    
    /**
     * add some more values (container id)
     *
     * @return array
     */
    protected function _addData()
    {
        $result['container_id'] = $this->_options['container_id'];
        return $result;
    }
    
    /**
     * do conversions
     *
     * @param array $_data
     * @return array
     */
    protected function _doConversions($_data)
    {
        if($this->_options['demoData'])$_data = $this->_getDay($_data, $this->_additionalOptions['dates']);

        $result = parent::_doConversions($_data);
        
        if ((isset($result['warranty']) || array_key_exists('warranty', $result)) && (empty($_data['warranty']))) {
            unset($result['warranty']);
        }
        
        if ((isset($result['invoice_date']) || array_key_exists('invoice_date', $result)) && (empty($_data['invoice_date']))) {
            unset($result['invoice_date']);
        }
        
        if ((isset($result["name"]) || array_key_exists("name", $result)) && ($result['name'] == "")) {
            $result['name'] = "!Not defined!";
        }
        
        if ((isset($result["inventory_id"]) || array_key_exists("inventory_id", $result)) && ($result['inventory_id'] == "")) {
                $result['inventory_id'] = Tinebase_Record_Abstract::generateUID(40);
        }
        
        if ((isset($result["costcenter"]) || array_key_exists("costcenter", $result))) {
            $result["costcenter"] = $c = Sales_Controller_CostCenter::getInstance()->search(new Sales_Model_CostCenterFilter(array(array(
                'field'    => 'number',
                'operator' => 'equals',
                'value'    => $result["costcenter"]
            ))))->getFirstRecord();
        }
        
        if ((isset($result["status"]) || array_key_exists("status", $result))) {
            
            $statusRecord = Inventory_Config::getInstance()->get(Inventory_Config::INVENTORY_STATUS)->getKeyfieldRecordByValue($result["status"]);
            if (empty($statusRecord)) {
                $statusRecord = Inventory_Config::getInstance()->get(Inventory_Config::INVENTORY_STATUS)->getKeyfieldDefault();
            }
            $result["status"] = $statusRecord['id'];
        }

        return $result;
    }
}
