<?php
/**
 * product controller for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2014 Metaways Infosystems GmbH (http://www.metaways.de)
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
        
        $this->_backend = new Sales_Backend_Product();

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
            self::$_instance = new self();
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
                if (! Tinebase_Core::getUser()->hasRight('Sales', Sales_Acl_Rights::MANAGE_PRODUCTS)) {
                    throw new Tinebase_Exception_AccessDenied("You don't have the right to manage products!");
                }
                break;
            default;
               break;
        }
    }

    /**
     * updateProductLifespan (switch products active/inactive)
     */
    public function updateProductLifespan()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Updating product lifespans...');
        
        $productIdsToChangeToInactive = $this->_getProductIdsForLifespanUpdate(/* $setToActive = */ false);
        $productIdsToChangeToActive = $this->_getProductIdsForLifespanUpdate(/* $setToActive = */ true);
        
        $this->_backend->updateMultiple($productIdsToChangeToInactive, array('is_active' => false));
        $this->_backend->updateMultiple($productIdsToChangeToActive, array('is_active' => true));
    }
    
    /**
     * helper function for updateProductLifespan
     * 
     * @param boolean $setToActive
     * return array of product ids
     */
    protected function _getProductIdsForLifespanUpdate($setToActive = true)
    {
        $now = Tinebase_DateTime::now();
        
        if ($setToActive) {
            // find all products that should be set to active
            $filter = new Sales_Model_ProductFilter(array(array(
                'field'    => 'is_active',
                'operator' => 'equals',
                'value'    => false
            ), array('condition' => 'OR', 'filters' => array(array(
                'field'    => 'lifespan_start',
                'operator' => 'before',
                'value'    => $now
            ), array(
                'field'    => 'lifespan_start',
                'operator' => 'isnull',
                'value'    => null
            ))), array('condition' => 'OR', 'filters' => array(array(
                'field'    => 'lifespan_end',
                'operator' => 'after',
                'value'    => $now
            ), array(
                'field'    => 'lifespan_end',
                'operator' => 'isnull',
                'value'    => null
            )))));
            
        } else {
            // find all products that should be set to inactive
            $filter = new Sales_Model_ProductFilter(array(array(
                'field'    => 'is_active',
                'operator' => 'equals',
                'value'    => true
            ), array('condition' => 'OR', 'filters' => array(array(
                'field'    => 'lifespan_start',
                'operator' => 'after',
                'value'    => $now
            ), array(
                'field'    => 'lifespan_end',
                'operator' => 'before',
                'value'    => $now
            )
            ))));
        }
        
        $productIdsToChange = $this->_backend->search($filter, null, Tinebase_Backend_Sql_Abstract::IDCOL);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Found ' . count($productIdsToChange) . ' to change to ' . ($setToActive ? 'active' : 'inactive'));
        
        return $productIdsToChange;
    }
}
