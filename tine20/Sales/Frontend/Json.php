<?php
/**
 * Tine 2.0
 * @package     Sales
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        add functions again (__call interceptor doesn't work because of the reflection api)
 * @todo        check if we can add these functions to the reflection without implementing them here
 */

/**
 *
 * This class handles all Json requests for the Sales application
 *
 * @package     Sales
 * @subpackage  Frontend
 */
class Sales_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * @see Tinebase_Frontend_Json_Abstract
     * 
     * @var string
     */
    protected $_applicationName = 'Sales';

    /**
     * @see Tinebase_Frontend_Json_Abstract
     */
    protected $_relatableModels = array(
        'Sales_Model_Contract',
        'Sales_Model_CostCenter',
        'Sales_Model_Customer',
        'Sales_Model_Address',
        'Sales_Model_Invoice'
    );

    /**
     * All configured models
     *
     * @var array
    */
    protected $_configuredModels = array(
        'Product',
        'Contract',
        'Division',
        'CostCenter',
        'Customer',
        'Address',
        'Invoice'
    );
    
   /**
     * Returns registry data of the application.
     *
     * Each application has its own registry to supply static data to the client.
     * Registry data is queried only once per session from the client.
     *
     * This registry must not be used for rights or ACL purposes. Use the generic
     * rights and ACL mechanisms instead!
     *
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        $sharedContainer = Sales_Controller_Contract::getSharedContractsContainer();
        $sharedContainer->resolveGrantsAndPath();
        // defaultContainer is deprecated, use defaultContractContainer
        // todo: remove first if sure it works everywhere
        return array(
            'defaultContainer' => $sharedContainer->toArray(),
            'defaultContractContainer' => $sharedContainer->toArray(),
        );
    }
    
    /**
     * Sets the config for Sales
     * @param array $config
     */
    public function setConfig($config)
    {
        return Sales_Controller::getInstance()->setConfig($config);
    }
    
    /**
     * Get Config for Sales
     * @return array
     */
    public function getConfig()
    {
        return Sales_Controller::getInstance()->getConfig();
    }
    
    /*************************** contracts functions *****************************/

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchContracts($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_Contract::getInstance(), 'Sales_Model_ContractFilter', /* $_getRelations */ TRUE);
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getContract($id)
    {
        return $this->_get($id, Sales_Controller_Contract::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveContract($recordData)
    {
        return $this->_save($recordData, Sales_Controller_Contract::getInstance(), 'Contract');
    }

    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteContracts($ids)
    {
        return $this->_delete($ids, Sales_Controller_Contract::getInstance());
    }

    /*************************** products functions *****************************/

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchProducts($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_Product::getInstance(), 'Sales_Model_ProductFilter');
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getProduct($id)
    {
        return $this->_get($id, Sales_Controller_Product::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveProduct($recordData)
    {
        return $this->_save($recordData, Sales_Controller_Product::getInstance(), 'Product');
    }

    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteProducts($ids)
    {
        return $this->_delete($ids, Sales_Controller_Product::getInstance());
    }
    
    // costcenter methods
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchCostCenters($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_CostCenter::getInstance(), 'Sales_Model_CostCenterFilter');
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getCostCenter($id)
    {
        return $this->_get($id, Sales_Controller_CostCenter::getInstance());
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveCostCenter($recordData)
    {
        return $this->_save($recordData, Sales_Controller_CostCenter::getInstance(), 'CostCenter');
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteCostCenters($ids)
    {
        return $this->_delete($ids, Sales_Controller_CostCenter::getInstance());
    }
    
    // division functions
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getDivision($id)
    {
        return $this->_get($id, Sales_Controller_Division::getInstance());
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveDivision($recordData)
    {
        return $this->_save($recordData, Sales_Controller_Division::getInstance(), 'Division');
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteDivisions($ids)
    {
        return $this->_delete($ids, Sales_Controller_Division::getInstance());
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchDivisions($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_Division::getInstance(), 'Sales_Model_DivisionFilter');
    }
    
    // customer methods
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchCustomers($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_Customer::getInstance(), 'Sales_Model_CustomerFilter');
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getCustomer($id)
    {
        return $this->_get($id, Sales_Controller_Customer::getInstance());
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @param  boolean $duplicateCheck
     * 
     * @return array created/updated record
     */
    public function saveCustomer($recordData, $duplicateCheck = TRUE)
    {
        $postalAddress = array();

        foreach($recordData as $field => $value) {
            if (strpos($field, 'adr_') !== FALSE && ! empty($value)) {
                $postalAddress[substr($field, 4)] = $value;
                unset($recordData[$field]);
            }
        }
    
        foreach (array('cpextern_id', 'cpintern_id') as $prop) {
            if (is_array($recordData[$prop])) {
                $recordData[$prop] = $recordData[$prop]['id'];
            }
        }
    
        $ret = $this->_save($recordData, Sales_Controller_Customer::getInstance(), 'Customer', 'id', array($duplicateCheck));
        $postalAddress['customer_id'] = $ret['id'];
    
        $addressController = Sales_Controller_Address::getInstance();
        $filter = new Sales_Model_AddressFilter(array(array('field' => 'type', 'operator' => 'equals', 'value' => 'postal')));
        $filter->addFilter(new Tinebase_Model_Filter_Text(
            array('field' => 'customer_id', 'operator' => 'equals', 'value' => $ret['id'])
        ));
    
        $postalAddressRecord = $addressController->search($filter)->getFirstRecord();
    
        // delete if fields are empty
        if (empty($postalAddress) && $postalAddressRecord) {
            $addressController->delete(array($postalAddressRecord->getId()));
            $postalAddressRecord = NULL;
        } else {
            // create if none has been found
            if (! $postalAddressRecord) {
                $postalAddressRecord = $addressController->create(new Sales_Model_Address($postalAddress));
            } else {
                // update if it has changed
                $postalAddress['id'] = $postalAddressRecord->getId();
                $postalAddress = new Sales_Model_Address($postalAddress);
                $diff = $postalAddressRecord->diff($postalAddress);
                if (! empty($diff)) {
                    $postalAddressRecord = $addressController->update($postalAddress);
                }
            }
        }
    
        return $ret;
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteCustomers($ids)
    {
        return $this->_delete($ids, Sales_Controller_Customer::getInstance());
    }
    
    // customer address method - addresses are dependent records, so we need a search method, no more (relation picker combo)
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchAddresss($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_Address::getInstance(), 'Sales_Model_AddressFilter');
    }
    
    
    // invoice methods
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchInvoices($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_Invoice::getInstance(), 'Sales_Model_InvoiceFilter', TRUE);
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getInvoice($id)
    {
        return $this->_get($id, Sales_Controller_Invoice::getInstance());
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @param  boolean $duplicateCheck
     *
     * @return array created/updated record
     */
    public function saveInvoice($recordData, $duplicateCheck = TRUE)
    {
        if (is_array($recordData["address_id"])) {
            $recordData["address_id"] = $recordData["address_id"]['id'];
        }

        return $this->_save($recordData, Sales_Controller_Invoice::getInstance(), 'Invoice', 'id', array($duplicateCheck));
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteInvoices($ids)
    {
        return $this->_delete($ids, Sales_Controller_Invoice::getInstance());
    }
}
