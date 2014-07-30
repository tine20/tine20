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
     * required apps
     * 
     * @var array
     */
    protected static $_requiredApplications = array('Admin', 'Addressbook');
    
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
        
        $this->_loadCostCentersAndDivisions();
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
            array('field' => 'description', 'operator' => 'equals', 'value' => 'Created by Tine 2.0 DemoData'),
        ), 'AND');
        
        return ($c->search($f)->count() > 10) ? true : false;
    }
    
    /**
     * creates the products - no containers, just "shared"
     */
    protected function _createSharedProducts()
    {
        $products = array(
            array(
                'name' => '10 Port 100 MBit Ethernet Switch',
                'description' => '10 Port Fast Ethernet Switch, RJ45',
                'price' => '28.13',
            ),
            array(
                'name' => '28 Port 100 MBit Ethernet Switch PoE',
                'description' => '28 Port Fast Ethernet Switch, PoE, RJ45',
                'price' => '1029.99',
            ),
            array(
                'name' => '10 Port Gigabit Ethernet Switch',
                'description' => '10 Port 1 Gigabit Switch, RJ45',
                'price' => '78.87',
            ),
            array(
                'name' => '28 Port Gigabit Ethernet Switch PoE',
                'description' => '28 Port 1 Gigabit Ethernet Switch PoE',
                'price' => '3496.45',
            )
        );
        
        $default = array(
            'manufacturer' => 'SwitchCo',
            'category' => self::$_en ? 'LAN Equipment' : 'Netzwerkausrüstung'
        );
        
        foreach($products as $product) {
            $this->_productController->create(new Sales_Model_Product(array_merge($product, $default)));
        }
        
        $products = array(
            array(
                'name' => self::$_en ? '10m Cat. 5a red' : '10m Kat. 5a rot',
                'description' => self::$_en ? '10m Cat. 5a red cable up to 100MBit.' : '10m Kat. 5a rotes Kabel. Erlaubt Übertragungsraten von bis zu 100MBit.',
                'price' => '5.99',
            ),
            array(
                'name' => self::$_en ? '10m Cat. 5a blue' : '10m Kat. 5a blau',
                'description' => self::$_en ? '10m Cat. 5a blue cable up to 100MBit.' : '10m Kat. 5a blaues Kabel. Erlaubt Übertragungsraten von bis zu 100MBit.',
                'price' => '5.99',
            ),
            array(
                'name' => self::$_en ? '10m Cat. 6 red' : '10m Kat. 6 rot',
                'description' => self::$_en ? '10m Cat. 6 red cable up to 1000MBit.' : '10m Kat. 5a rotes Kabel. Erlaubt Übertragungsraten von bis zu 1000MBit.',
                'price' => '9.99',
            ),
            array(
                'name' => self::$_en ? '10m Cat. 6 blue' : '10m Kat. 6 blau',
                'description' => self::$_en ? '10m Cat. 6 blue cable up to 1000MBit.' : '10m Kat. 5a blaues Kabel. Erlaubt Übertragungsraten von bis zu 1000MBit.',
                'price' => '9.99',
            ),
        );
        
        $default = array(
            'manufacturer' => self::$_en ? 'Salad Cabels' : 'Salat Kabel & Co.',
            'category' => self::$_en ? 'LAN Equipment' : 'Netzwerkausrüstung'
        );
        
        foreach($products as $product) {
            $this->_productController->create(new Sales_Model_Product(array_merge($product, $default)));
        }
    }
    
    /**
     * creates the contracts - no containers, just "shared"
     */
    protected function _createSharedContracts()
    {
        $cNumber = 1;
        
        $container = $this->_contractController->getSharedContractsContainer();
        $cid = $container->getId();
        $ccs = array($this->_developmentCostCenter, $this->_marketingCostCenter);
        
        $i = 0;
        
        while ($i < 12) {
            $costcenter = $ccs[$i%2];
            $i++;
            
            $title = self::$_de ? ('Vertrag für KST ' . $costcenter->number . ' - ' . $costcenter->remark) : ('Contract for costcenter ' . $costcenter->number . ' - ' . $costcenter->remark) . ' ' . Tinebase_Record_Abstract::generateUID(3);
            $ccid = $costcenter->getId();
            
            $contract = new Sales_Model_Contract(array(
                'number'       => $cNumber,
                'title'        => $title,
                'description'  => 'Created by Tine 2.0 DemoData',
                'container_id' => $cid,
                'status'       => 'OPEN',
                'cleared'      => 'NOT_YET_CLEARED'
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
    
    /**
     * returns a new product
     * return Sales_Model_Product
     */
    protected function _createProduct($data)
    {
        
    }
    
    /**
     * create some costcenters
     *
     * @see Tinebase_Setup_DemoData_Abstract
     */
    protected function _onCreate()
    {
        $controller = Sales_Controller_CostCenter::getInstance();
        $this->_costCenters = new Tinebase_Record_RecordSet('Sales_Model_CostCenter');
        $ccs = (static::$_de)
        ? array('Management', 'Marketing', 'Entwicklung', 'Produktion', 'Verwaltung',     'Controlling')
        : array('Management', 'Marketing', 'Development', 'Production', 'Administration', 'Controlling')
        ;
    
        $id = 1;
        foreach($ccs as $title) {
            $cc = new Sales_Model_CostCenter(
                array('remark' => $title, 'number' => $id)
            );
            try {
                $record = $controller->create($cc);
                $this->_costCenters->addRecord($record);
            } catch (Zend_Db_Statement_Exception $e) {
                $this->_costCenters = $controller->search(new Sales_Model_CostCenterFilter(array()));
            }
    
            $id++;
        }
    
        $divisionsArray = (static::$_de)
        ? array('Management', 'EDV', 'Marketing', 'Public Relations', 'Produktion', 'Verwaltung')
        : array('Management', 'IT', 'Marketing', 'Public Relations', 'Production', 'Administration')
        ;
    
        foreach($divisionsArray as $divisionName) {
            Sales_Controller_Division::getInstance()->create(new Sales_Model_Division(array('title' => $divisionName)));
        }
        
        $this->_loadCostCentersAndDivisions();
    }
}
