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
     * the reference date fo work on
     *
     * @var Tinebase_DateTime
     */
    protected $_referenceDate = NULL;
    
    /**
     * 
     * @return array
     */
    protected function _getIdsOfBillables()
    {
        $ids = array();
        
        foreach($this->_billables as $month => $billablesPerMonth) {
            foreach($billablesPerMonth as $billable) {
                $ids[] = $billable->getId();
            }
        }
        
        return $ids;
    }
    
    /**
     * returns billables for this record
     * 
     * @param Tinebase_DateTime $date
     * @return array
     */
    public function getBillables(Tinebase_DateTime $date = NULL)
    {
        if (! $date) {
            if (! $this->_referenceDate) {
                throw new Tinebase_Exception_InvalidArgument('date is needed if not set before');
            }
            $date = clone $this->_referenceDate;
        }
        
        if (! $this->_billablesLoaded) {
            $this->loadBillables($date);
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
        if (! $this->_billablesLoaded) {
            $this->loadBillables($date);
        }
        
        if (empty($this->_billables)) {
            return array(NULL, NULL);
        }
        
        if (! $date) {
            $date = clone $this->_referenceDate;
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
     * returns the unit of the accountable
     *
     * @return string
     */
    abstract public function getUnit();
    
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
     * @return void
     */
    abstract public function loadBillables(Tinebase_DateTime $date);
    
    /**
     * returns true if this record should be billed for the specified date
     * 
     * @param Tinebase_DateTime $date
     * @param Sales_Model_Contract $contract
     * @return boolean
     */
    abstract public function isBillable(Tinebase_DateTime $date, Sales_Model_Contract $contract = NULL);
}
