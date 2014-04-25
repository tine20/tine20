<?php
/**
 * ProductAggregate controller for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * ProductAggregate controller class for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_ProductAggregate extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName         = 'Sales';
        $this->_modelName               = 'Sales_Model_ProductAggregate';
        $this->_backend                 = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName, 
            'tableName' => 'sales_product_agg',
        ));
        $this->_doContainerACLChecks    = FALSE;
    }    
    
    /**
     * don't clone. Use the singleton.
     */
    private function __clone()
    {
        
    }
     
    /**
     * holds the instance of the singleton
     *
     * @var Sales_Controller_ProductAggregate
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Sales_Controller_ProductAggregate
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
}
