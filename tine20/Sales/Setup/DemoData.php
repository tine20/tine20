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
     * the application name to work on
     * 
     * @var string
     */
    protected $_appName         = 'Sales';

    /**
     * models to work on
     * @var unknown_type
     */
    protected $_models = array('product', 'contract');
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {

    }

    /**
     * the singleton pattern
     *
     * @return Sales_Setup_DemoData
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Sales_Setup_DemoData;
        }

        return self::$_instance;
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
        // TODO: create some contracts
    }
    
    /**
     * returns a new product
     * return Sales_Model_Product
     */
    protected function _createProduct($data)
    {
        
    }
}
