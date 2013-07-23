<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Sales initialization
 *
 * @package     Setup
 */
class Sales_Setup_DemoData extends Tinebase_Setup_DemoData_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Sales_Setup_DemoData
     */
    private static $_instance = NULL;
    
    /**
     * The contract controller
     * 
     * @var Sales_Controller_Contract
     */
    protected $_contractController = NULL;
    
    /**
     * 
     * required apps
     * @var array
     */
    protected static $_requiredApplications = array('Admin', 'Addressbook', 'HumanResources');
    
    /**
     * The product controller
     * 
     * @var Sales_Controller_Product
     */
    protected $_productController  = NULL;
    
    /**
     * the application name to work on
     * 
     * @var string
     */
    protected $_appName = 'Sales';
    /**
     * models to work on
     * @var array
     */
    protected $_models = array('product', 'contract');
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
        $this->_productController     = Sales_Controller_Product::getInstance();
        $this->_contractController    = Sales_Controller_Contract::getInstance();
        
        $this->_loadCostCenters();
    }

    /**
     * the singleton pattern
     *
     * @return Sales_Setup_DemoData
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
    
    /**
     * this is required for other applications needing demo data of this application
     * if this returns true, this demodata has been run already
     * 
     * @return boolean
     */
    public static function hasBeenRun()
    {
        $c = Sales_Controller_Contract::getInstance();
        
        $f = new Sales_Model_ContractFilter(array(
            array('field' => 'description', 'operator' => 'equals', 'value' => 'Created by Tine 2.0 DEMO DATA'),
        ), 'AND');
        
        return ($c->search($f)->count() > 10) ? true : false;
    }
    
    /**
     * creates the products - no containers, just "shared"
     */
    protected function _createSharedProducts()
    {
        // TODO: create some products
    }
    
    /**
     * creates the contracts - no containers, just "shared"
     */
    protected function _createSharedContracts()
    {
        $cNumber = 1;
        
        $container = $this->_contractController->getSharedContractsContainer();
        $cid = $container->getId();
        
        foreach($this->_costCenters as $costcenter) {
            $i = 0;
            
            $title = self::$_de ? ('Vertrag für KST ' . $costcenter->number . ' - ' . $costcenter->remark) : ('Contract for costcenter ' . $costcenter->number . ' - ' . $costcenter->remark) . ' ' . Tinebase_Record_Abstract::generateUID(3);
            $ccid = $costcenter->getId();
            
            while ($i < 2) {
                $i++;
                
                $contract = new Sales_Model_Contract(array(
                    'number'       => $cNumber,
                    'title'        => $title,
                    'description'  => 'Created by Tine 2.0 DEMO DATA',
                    'container_id' => $cid
                ));
                
                $relations = array(
                    array(
                        'own_model'              => 'Sales_Model_Contract',
                        'own_backend'            => Tasks_Backend_Factory::SQL,
                        'own_id'                 => NULL,
                        'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
                        'related_model'          => 'Sales_Model_CostCenter',
                        'related_backend'        => Tasks_Backend_Factory::SQL,
                        'related_id'             => $ccid,
                        'type'                   => 'LEAD_COST_CENTER'
                    )
                );
                $contract->relations = $relations;
                
                $this->_contractController->create($contract);
                $cNumber++;
            }
        }
    }
    
    /**
     * returns a new product
     * return Sales_Model_Product
     */
    protected function _createProduct($data)
    {
        
    }
}
