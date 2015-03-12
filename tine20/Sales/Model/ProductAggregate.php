<?php
/**
 * class to hold product aggregate data
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexaander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold product data
 * 
 * @package     Sales
 */
class Sales_Model_ProductAggregate extends Sales_Model_Accountable_Abstract
{
    /**
     * if this is billed the first time, this is true
     * 
     * @var boolean
     */
    protected $_firstBill = FALSE;
    
    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
    
    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = array(
        'recordName'        => 'Product',
        'recordsName'       => 'Products', // ngettext('Product', 'Products', n)
        'hasRelations'      => TRUE,
        'hasCustomFields'   => FALSE,
        'hasNotes'          => FALSE,
        'hasTags'           => FALSE,
        'modlogActive'      => FALSE,
        'hasAttachments'    => FALSE,
        'createModule'      => FALSE,
        'containerProperty' => NULL,
        'isDependent'       => TRUE,
        
        'titleProperty'     => 'product_id.name',
        'appName'           => 'Sales',
        'modelName'         => 'ProductAggregate',
        
        'fields'            => array(
            'product_id'       => array(
                'label'      => 'Product',    // _('Product')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'type'       => 'record',
                'sortable'   => FALSE,
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Product',
                    'idProperty'  => 'id',
                )
            ),
            'contract_id'       => array(
                'isParent'    => TRUE,
                'label'      => 'Contract',    // _('Contract')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE),
                'type'       => 'record',
                'sortable'   => FALSE,
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Contract',
                    'idProperty'  => 'id',
                    'isParent'    => TRUE
                )
            ),
            'quantity' => array(
                'label' => 'Quantity', // _('Quantity')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
                'type'       => 'integer',
                'default'    => NULL,
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
            ),
            'interval' => array(
                'label'      => 'Billing Interval', // _('Billing Interval')
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'type'       => 'integer',
                'default'    => 1
            ),
            'last_autobill' => array(
                'label'      => NULL,
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => NULL),
                'type'       => 'datetime',
                'default'    => NULL
            ),
            'billing_point' => array(
                'label' => 'Billing Point', // _('Billing Point')
                'type'  => 'string',
                'default' => 'begin',
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => FALSE, Zend_Filter_Input::DEFAULT_VALUE => 'begin')
            ),
            'start_date' => array(
                'type' => 'datetime',
                'label'      => 'Start Date',    // _('Start Date')
            ),
            'end_date' => array(
                'type' => 'datetime',
                'label'      => 'End Date',    // _('End Date')
            ),
        )
    );

    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        array('relatedApp' => 'Sales', 'relatedModel' => 'Invoice', 'config' => array(
            array('type' => 'INVOICE_ITEM', 'degree' => 'sibling', 'text' => 'Invoice Item', 'max' => '0:0'), // _('Invoice Item')
        ), 'defaultType' => 'INVOICE_ITEM'
        ),
    );
    
    /**
     * returns the max interval of all billables
     *
     * @param Tinebase_DateTime $date
     * @return array
     */
    public function getInterval(Tinebase_DateTime $date = NULL)
    {
        if ($date != NULL) {
            $date = clone $date;
        } elseif($this->_referenceDate != NULL) {
            $date = clone $this->_referenceDate;
        }
        if ($date != NULL) {
            $date->setDate($date->format('Y'), $date->format('m'), 1);
            // if we are not already in user timezone we are in deep shit, add assertation rather instead or something
            $date->setTimezone(Tinebase_Core::getUserTimezone());
            $date->setTime(0,0,0);
            if ($this->billing_point == 'begin') {
                $date->addMonth($this->interval);
            }
        }
        
        if (! $this->last_autobill) {
            if (! $this->start_date) {
                $from = clone $this->_referenceContract->start_date;
            } else {
                $from = clone $this->start_date;
            }
        } else {
            $from = clone $this->last_autobill;
            if ($this->billing_point == 'begin') {
                $from->addMonth($this->interval);
            }
        }
        
        $from->setDate($from->format('Y'), $from->format('m'), 1);
        // if we are not already in user timezone we are in deep shit, add assertation rather instead or something
        $from->setTimezone(Tinebase_Core::getUserTimezone());
        $from->setTime(0,0,0);
        
        $to = clone $from;
        do {
            $to->addMonth($this->interval);
        } while($date != NULL && $to->isEarlier($date)) ;
        if ($this->billing_point == 'end' && $to->isLater($date)) {
            $to->subMonth($this->interval);
        }
        $to->subSecond(1);
        
        return array($from, $to);
    }
    
    /**
     * loads billables for this record
     *
     * @param Tinebase_DateTime $date
     * @param Sales_Model_ProductAggregate $productAggregate
     * @return void
     */
    public function loadBillables(Tinebase_DateTime $date, Sales_Model_ProductAggregate $productAggregate)
    {
        $this->_referenceDate = $date;
        
        list($from, $to) = $this->getInterval();
        $this->_billables = array();
        
        // if we are not already in user timezone we are in deep shit, add assertation rather instead or something
        $this->setTimezone(Tinebase_Core::getUserTimezone());
        
        while($from < $to) {
            $this->_billables[$from->format('Y-m')] = array(clone $this);
            // 1 or interval?!? should show up every month as a position, so 1! NOT interval
            $from->addMonth(1);
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see Sales_Model_Accountable_Abstract::getBillables()
     */
    public function getBillables(Tinebase_DateTime $date = NULL, Sales_Model_ProductAggregate $productAggregate = NULL)
    {
        return $this->_billables;
    }
    
    /**
     * returns true if this record should be billed for the specified date
     * 
     * @param Tinebase_DateTime $date
     * @param Sales_Model_Contract $contract
     * @param Sales_Model_ProductAggregate $productAggregate
     * @return boolean
     */
     public function isBillable(Tinebase_DateTime $date, Sales_Model_Contract $contract = NULL, Sales_Model_ProductAggregate $productAggregate = NULL)
     {
         $this->_referenceContract = $contract;
         
         if (! $this->last_autobill) {
             if (! $this->start_date) {
                 $nextBill = clone $contract->start_date;
             } else {
                 $nextBill = clone $this->start_date;
             }
             $nextBill->setDate($nextBill->format('Y'), $nextBill->format('m'), 1);
             if ($this->billing_point == 'end') {
                $nextBill->addMonth($this->interval);
             }
             
         } else {
             $nextBill = clone $this->last_autobill;
             $nextBill->setDate($nextBill->format('Y'), $nextBill->format('m'), 1);
             $nextBill->addMonth($this->interval);
         }
         
         // if we are not already in user timezone we are in deep shit, add assertation rather instead or something
         $nextBill->setTimeZone(Tinebase_Core::getUserTimezone());
         $nextBill->setTime(0,0,0);
         
         return $date->isLaterOrEquals($nextBill);
     }
     
     /**
      * returns the quantity of this billable
      *
      * @return float
      */
     public function getQuantity()
     {
         return $this->quantity;
     }
     
     /**
      * the billed_in - field of all billables of this accountable gets the id of this invoice
      *
      * @param Sales_Model_Invoice $invoice
      */
     public function conjunctInvoiceWithBillables($invoice)
     {
         // nothing to do. ProductAggregates are always conjuncted with the invoice
     }
     
     /**
      * set each billable of this accountable billed
      *
      * @param Sales_Model_Invoice $invoice
      */
     public function clearBillables(Sales_Model_Invoice $invoice)
     {
         // nothing to do. ProductAggregates are always billed
     }
     
     /**
      * returns the unit of this billable
      *
      * @return string
      */
     public function getUnit()
     {
         return 'Piece'; // _('Piece')
     }
     
     /**
      * (non-PHPdoc)
      * @see Tinebase_Record_Abstract::getTitle()
      */
     public function getTitle()
     {
         $p = Sales_Controller_Product::getInstance()->get($this->product_id);
         
         return $p->name;
     }

    /**
     * sanitize product_id
     * 
     * @param array $_data the json decoded values
     * @return void
     * 
     * @todo should be moved to json converter (toTine20Model) (@see 0009906: generic solution for sanitizing ids by extracting id value from array)
     * @todo needs a test
     */
    protected function _setFromJson(array &$_data)
    {
        // sanitize product id if it is an array
        if (is_array($_data['product_id']) && isset($_data['product_id']['id']) ) {
            $_data['product_id'] = $_data['product_id']['id'];
        }
    }
    
    /**
     * returns the name of the billable controller
     *
     * @return string
     */
    public static function getBillableControllerName() {
        return 'Sales_Controller_ProductAggregate';
    }
    
    /**
     * returns the name of the billable filter
     *
     * @return string
     */
    public static function getBillableFilterName() {
        return 'Sales_Model_ProductAggregateFilter';
    }
    
    /**
     * returns the name of the billable model
     *
     * @return string
     */
    public static function getBillableModelName() {
        return 'Sales_Model_ProductAggregate';
    }
}
