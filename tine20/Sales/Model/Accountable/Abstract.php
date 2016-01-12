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
 * abstract class Sales_Model_Accountable_Abstract
 *
 * @package     Sales
 * @subpackage  Model
 */
abstract class Sales_Model_Accountable_Abstract extends Tinebase_Record_Abstract implements Sales_Model_Accountable_Interface
{
    /**
     * the default interval used on billing if no product aggregate has been defined for this accountable
     * 
     * @var integer
     */
    protected $_defaultInterval = 1;
    
    /**
     * the default billing point used on billing if no product aggregate has been defined for this accountable
     *
     * @var integer
     */
    protected $_defaultBillingPoint = 'end';
    
    /**
     * if billables has been loaded or should have been loaded, but none has been found, this is set to true
     * 
     * @var boolean
     */
    protected $_billablesLoaded = FALSE;
    
    /**
     * holds found billables
     * 
     * @var array
     */
    protected $_billables = NULL;
    
    /**
     * the reference date to work on
     *
     * @var Tinebase_DateTime
     */
    protected $_referenceDate = NULL;
    
    /**
     * the reference contract to work on
     *
     * @var Sales_Model_Contract
     */
    protected $_referenceContract = NULL;
    
    /**
     * returns the ids of all loaded billables
     * 
     * @return array
     */
    protected function _getIdsOfBillables()
    {
        $ids = array();
        
        if ($this->_billables) {
            foreach($this->_billables as $month => $billablesPerMonth) {
                foreach($billablesPerMonth as $billable) {
                    $ids[] = $billable->getId();
                }
            }
        }
        
        return $ids;
    }

    /**
     * returns true if this invoice needs to be recreated because data changed
     *
     * @param Tinebase_DateTime $date
     * @param Sales_Model_ProductAggregate $productAggregate
     * @param Sales_Model_Invoice $invoice
     * @param Sales_Model_Contract $contract
     * @return boolean
     */
    public function needsInvoiceRecreation(Tinebase_DateTime $date, Sales_Model_ProductAggregate $productAggregate, Sales_Model_Invoice $invoice, Sales_Model_Contract $contract)
    {
        return false;
    }


    /**
     * returns billables for this record
     * 
     * @param Tinebase_DateTime $date
     * @param Sales_Model_ProductAggregate $productAggregate
     * @return array
     */
    public function getBillables(Tinebase_DateTime $date = NULL, Sales_Model_ProductAggregate $productAggregate = NULL)
    {
        if (! $date) {
            if (! $this->_referenceDate) {
                throw new Tinebase_Exception_InvalidArgument('date is needed if not set before');
            }
            $date = clone $this->_referenceDate;
        }
        
        return $this->_billables;
    }
    
    /**
     * returns the max interval of all billables
     * 
     * @param Tinebase_DateTime $date
     * @return array
     */
    public function getInterval(Tinebase_DateTime $date = NULL)
    {
        if (! $date) {
            if (! $this->_referenceDate) {
                throw new Tinebase_Exception_InvalidArgument('date is needed if not set before');
            }
            
            $date = clone $this->_referenceDate;
        }
        
        if (empty($this->_billables)) {
            return array(NULL, NULL);
        }
        
        $latestEndDate = NULL;
        $earliestStartDate = NULL;
        
        foreach($this->_billables as $month => $billablesPerMonth) {
            foreach($billablesPerMonth as $billable) {
                $interval = $billable->getInterval();
                
                list($startDate, $endDate) = $interval;
                
                if (! $latestEndDate) {
                    $latestEndDate = clone $endDate;
                } elseif ($endDate > $latestEndDate) {
                    $latestEndDate = clone $endDate;
                }
                
                if (! $earliestStartDate) {
                    $earliestStartDate = clone $startDate;
                } elseif ($startDate < $earliestStartDate) {
                    $earliestStartDate = clone $startDate;
                }
            }
        }
        
        return array($earliestStartDate, $latestEndDate);
    }
    
    /**
     * if the model does not have its own last_autobill property, this is volatile and controlled by the contract
     *
     * @return boolean
     */
    public function isVolatile()
    {
        return ! in_array('last_autobill', $this->getFields());
    }
    
    /**
     * this returns true, if the billables should be summarized per month
     * if this returns false, each billable will get its own invoice position
     *
     * @return array
     */
    public function sumBillables()
    {
        return TRUE;
    }
    
    /**
     * sets the contract
     * 
     * @param Sales_Model_Contract $contract
     */
    public function setContract(Sales_Model_Contract $contract)
    {
        $this->_referenceContract = $contract;
    }
    
    /**
     * returns a temporarily productaggregate which contains the
     * default billing information of this accountable
     * 
     * @param Sales_Model_Contract $contract
     * @return Sales_Model_ProductAggregate
     */
    public function getDefaultProductAggregate(Sales_Model_Contract $contract)
    {
        $startDate = clone $contract->start_date;
        
        if ($contract->start_date->format('d') !== 1) {
            $startDate->setDate($startDate->format('Y'), $startDate->format('m'), 1);
        }
        
        $accountable = get_class($this);
        
        $filter = new Sales_Model_ProductFilter(array(
                array('field' => 'accountable', 'operator' => 'equals', 'value' => $accountable)
        ));
        $product = Sales_Controller_Product::getInstance()->search($filter)->getFirstRecord();
        
        // create product, if no product is found
        if (! $product) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' '
                        . ' Create Product for ' . $accountable);
            }
            $product = Sales_Controller_Product::getInstance()->create(new Sales_Model_Product(array(
                    'name' => $accountable,
                    'accountable' => $accountable,
                    'description' => 'auto generated on invoicing',
            )));
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' '
                    . ' Create ProductAggregate for ' . $accountable . ' contract: ' . $contract->getId());
        }
        
        $endDate = clone $startDate;
        $endDate->addMonth($this->_defaultInterval);
        
        $pa = new Sales_Model_ProductAggregate(array(
            'interval'      => $this->_defaultInterval,
            'billing_point' => $this->_defaultBillingPoint,
            'contract_id'   => $contract->getId(),
            'start_date'    => $startDate,
            'end_date'      => NULL,
            'last_autobill' => NULL,
            'product_id'    => $product->getId(),#
            'quantity'      => $product->accountable ? NULL : 1,
        ));
        
        return $pa;
    }
    
    /**
     * the billed_in - field of all billables of this accountable gets the id of this invoice
     * 
     * @param Sales_Model_Invoice $invoice
     */
    abstract public function conjunctInvoiceWithBillables($invoice);
    
    /**
     * set each billable of this accountable billed
     *
     * @param Sales_Model_Invoice $invoice
     */
    abstract public function clearBillables(Sales_Model_Invoice $invoice);

    /**
     * loads billables for this record
     *
     * @param Tinebase_DateTime $date
     * @param Sales_Model_ProductAggregate $productAggregate
     * @return void
     */
    abstract public function loadBillables(Tinebase_DateTime $date, Sales_Model_ProductAggregate $productAggregate);
    
    /**
     * returns true if this record should be billed for the specified date
     * 
     * @param Tinebase_DateTime $date
     * @param Sales_Model_Contract $contract
     * @return boolean
     */
    abstract public function isBillable(Tinebase_DateTime $date, Sales_Model_Contract $contract = NULL);

    /**
     * called by the product aggregate controller in case accountables are assigned to the changed
     * product aggregate
     *
     * @param Sales_Model_ProductAggregate $productAggregate
     * @return void
     */
    public function _inspectBeforeCreateProductAggregate(Sales_Model_ProductAggregate $productAggregate) {}

    /**
     * called by the product aggregate controller in case accountables are assigned to the changed
     * product aggregate
     *
     * @param Sales_Model_ProductAggregate $productAggregate
     * @param Sales_Model_ProductAggregate $oldRecord
     * @return void
     */
    public function _inspectBeforeUpdateProductAggregate(Sales_Model_ProductAggregate $productAggregate, Sales_Model_ProductAggregate $oldRecord) {}
}
