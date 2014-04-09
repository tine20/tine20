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
    
    protected function _fromArrayToId($_record)
    {
        if (is_array($_record->product_id)) {
            $_record->product_id = $_record->product_id['id'];
        }
        if (is_array($_record->contract_id)) {
            $_record->contract_id = $_record->contract_id['id'];
        }
    }
    
    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   boolean $_duplicateCheck
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function create(Tinebase_Record_Interface $_record, $_duplicateCheck = TRUE)
    {
        $this->_fromArrayToId($_record);
        
        return parent::create($_record, $_duplicateCheck);
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @param   boolean $_duplicateCheck
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_AccessDenied
     * 
     * @todo    fix duplicate check on update / merge needs to remove the changed record / ux discussion
     */
    public function update(Tinebase_Record_Interface $_record, $_duplicateCheck = TRUE)
    {
        $this->_fromArrayToId($_record);
        return parent::update($_record, $_duplicateCheck);
    }

    /**
     * sets the last billed date to the next date by interval and returns the updated contract
     *
     * @param Sales_Model_ProductAggregate $productAggregate
     * @param Sales_Model_Contract $contract
     * @return Sales_Model_Product
     */
    public function updateLastBilledDate(Sales_Model_ProductAggregate $productAggregate, Sales_Model_Contract $contract)
    {
        // update last billed information -> set last_autobill to the date the invoice should have
        // been created and not to the current date, so we can calculate the interval properly
        $lastBilled = $productAggregate->last_autobill ? clone $productAggregate->last_autobill : NULL;
    
        if ($lastBilled === NULL) {
            // set billing date to start date of the contract
            $productAggregate->last_autobill = clone $contract->start_date;
            $productAggregate->last_autobill->addMonth($productAggregate->interval);
        } else {
            $productAggregate->last_autobill->addMonth($productAggregate->interval);
        }
    
        return $this->update($productAggregate);
    }
}
