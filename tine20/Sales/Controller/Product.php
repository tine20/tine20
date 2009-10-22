<?php
/**
 * product controller for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Product controller class for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Product extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {        
        $this->_applicationName         = 'Sales';
        $this->_modelName               = 'Sales_Model_Product';
        $this->_backend                 = new Tinebase_Backend_Sql($this->_modelName, 'sales_products');
        $this->_currentAccount          = Tinebase_Core::getUser();
        $this->_doContainerACLChecks    = FALSE;
    }    
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
        
    }   
     
    /**
     * holds the instance of the singleton
     *
     * @var Sales_Controller_Product
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Sales_Controller_Product
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Sales_Controller_Product();
        }
        
        return self::$_instance;
    }
    
    /**
     * check if user has the right to manage Products
     * 
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        switch ($_action) {
            case 'create':
            case 'update':
            case 'delete':
                if (! Tinebase_Core::getUser()->hasRight('Felamimail', Sales_Acl_Rights::MANAGE_PRODUCTS)) {
                    throw new Tinebase_Exception_AccessDenied("You don't have the right to manage products!");
                }
                break;
            default;
               break;
        }
    }
}
