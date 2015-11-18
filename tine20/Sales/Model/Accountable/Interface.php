<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * interface Sales_Model_Accountable_Interface
 *
 * @package     Sales
 * @subpackage  Model
 */
interface Sales_Model_Accountable_Interface
{
    /**
     * set each billable of this accountable billed
     * 
     * @param Sales_Model_Invoice $invoice
     */
    public function clearBillables(Sales_Model_Invoice $invoice);
    
    /**
     * if the model does not have its own last_autobill property, this is volatile and controlled by the contract
     *
     * @return boolean
     */
    public function isVolatile();
    
    /**
     * this returns true, if the billables should be summarized per month
     * if this returns false, each billable will get its own invoice position
     * 
     * @return array
     */
    public function sumBillables();
    
    /**
     * returns billables for this record
     * 
     * @param Tinebase_DateTime $date
     * @param Sales_Model_ProductAggregate $productAggregate
     * @return array
     */
    public function getBillables(Tinebase_DateTime $date = NULL, Sales_Model_ProductAggregate $productAggregate = NULL);
    
    /**
     * returns the max interval of all billables
     * 
     * @param Tinebase_DateTime $date
     * @return array
     */
    public function getInterval(Tinebase_DateTime $date = NULL);
    
    /**
     * loads billables for this record
     *
     * @param Tinebase_DateTime $date
     * @param Sales_Model_ProductAggregate $productAggregate
     * @return void
     */
    public function loadBillables(Tinebase_DateTime $date, Sales_Model_ProductAggregate $productAggregate);
    
    /**
     * returns true if this record should be billed for the specified date
     * 
     * @param Tinebase_DateTime $date
     * @param Sales_Model_Contract $contract
     * @return boolean
     */
    public function isBillable(Tinebase_DateTime $date, Sales_Model_Contract $contract = NULL);
    
    /**
     * returns the name of the billable controller
     * 
     * @return string
     */
    public static function getBillableControllerName();
    
    /**
     * returns the name of the billable filter
     *
     * @return string
     */
    public static function getBillableFilterName();
    
    /**
     * returns the name of the billable model
     *
     * @return string
     */
    public static function getBillableModelName();
    
    /**
     * returns a temporarily productaggregate which contains the
     * default billing information of this accountable
     * 
     * @param Sales_Model_Contract $contract
     * @return Sales_Model_ProductAggregate
     */
    public function getDefaultProductAggregate(Sales_Model_Contract $contract);

    /**
     * called by the product aggregate controller in case accountables are assigned to the changed
     * product aggregate
     *
     * @param Sales_Model_ProductAggregate $productAggregate
     * @return void
     */
    public function _inspectBeforeCreateProductAggregate(Sales_Model_ProductAggregate $productAggregate);

    /**
     * called by the product aggregate controller in case accountables are assigned to the changed
     * product aggregate
     *
     * @param Sales_Model_ProductAggregate $productAggregate
     * @param Sales_Model_ProductAggregate $oldRecord
     * @return void
     */
    public function _inspectBeforeUpdateProductAggregate(Sales_Model_ProductAggregate $productAggregate, Sales_Model_ProductAggregate $oldRecord);
}
