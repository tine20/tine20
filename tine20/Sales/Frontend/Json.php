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
     * Contract controller
     *
     * @var Sales_Controller_Contract
     */
    protected $_contractController = NULL;

    /**
     * Product controller
     *
     * @var Sales_Controller_Product
     */
    protected $_productController = NULL;
    
    /**
     * @see Tinebase_Frontend_Json_Abstract
     */
    protected $_relatableModels = array('Sales_Model_Contract');

    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName     = 'Sales';
        $this->_contractController  = Sales_Controller_Contract::getInstance();
        $this->_productController   = Sales_Controller_Product::getInstance();
    }

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
        
        return array(
            'defaultContainer' => $sharedContainer->toArray()
        );
    }
    
    /**
     * Sets the config for Sales
     * @param array $config
     */
    public function setConfig($config) {
        return Sales_Controller::getInstance()->setConfig($config);
    }
    
    /**
     * Get Config for Sales
     * @return array
     */
    public function getConfig() {
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
        return $this->_search($filter, $paging, $this->_contractController, 'Sales_Model_ContractFilter');
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getContract($id)
    {
        return $this->_get($id, $this->_contractController);
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveContract($recordData)
    {
        return $this->_save($recordData, $this->_contractController, 'Contract');
    }

    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteContracts($ids)
    {
        return $this->_delete($ids, $this->_contractController);
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
        return $this->_search($filter, $paging, $this->_productController, 'Sales_Model_ProductFilter');
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getProduct($id)
    {
        return $this->_get($id, $this->_productController);
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveProduct($recordData)
    {
        return $this->_save($recordData, $this->_productController, 'Product');
    }

    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteProducts($ids)
    {
        return $this->_delete($ids, $this->_productController);
    }
    
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
    
    // division functions

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchDivisions($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_Division::getInstance(), 'Sales_Model_ContractFilter');
    }
}
