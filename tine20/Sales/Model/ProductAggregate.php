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
                'default'    => 1
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
        $from = clone $this->last_autobill;
        
        if (! $this->_firstBill) {
            $from->addMonth($this->interval);
        }
        
        $to   = clone $from;
        $to->addMonth($this->interval)->subSecond(1);
        
        return array($from, $to);
    }
    
    /**
     * loads billables for this record
     *
     * @param Tinebase_DateTime $date
     * @return void
     */
    public function loadBillables(Tinebase_DateTime $date)
    {
        $this->_referenceDate = $date;
        
        list($from, $to) = $this->getInterval();
        $this->_billables = array();
        
        while($from < $to) {
            $this->_billables[$from->format('Y-m')] = array(clone $this);
            $from->addMonth(1);
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see Sales_Model_Accountable_Abstract::getBillables()
     */
    public function getBillables(Tinebase_DateTime $date = NULL)
    {
        return $this->_billables;
    }
    
    /**
     * returns true if this record should be billed for the specified date
     * 
     * @param Tinebase_DateTime $date
     * @param Sales_Model_Contract $contract
     * @return boolean
     */
     public function isBillable(Tinebase_DateTime $date, Sales_Model_Contract $contract = NULL)
     {
         $this->_referenceDate = clone $date;
         
         if (! $this->last_autobill) {
             
             $this->_firstBill = TRUE;
             
             // products always get billed at the beginning of a period
             $this->last_autobill = clone $contract->start_date;
             
             return TRUE;
         }
         
         $lastAutobill = clone $this->last_autobill;
         
         if ($lastAutobill->addMonth($this->interval) < $date) {
             return TRUE;
         }
         
         return FALSE;
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
     
     public function updateLastBilledDate()
     {
         if (! $this->_firstBill) {
             $this->last_autobill->addMonth($this->interval);
         }
         
         Sales_Controller_ProductAggregate::getInstance()->update($this, FALSE);
     }
     
     /**
      * returns the unit of the accountable
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
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' json data: ' . print_r($_data, true));
        
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
        throw new Tinebase_Exception_NotImplemented('Must be implemented seperately.');
    }
    
    /**
     * returns the name of the billable filter
     *
     * @return string
     */
    public static function getBillableFilterName() {
        throw new Tinebase_Exception_NotImplemented('Must be implemented seperately.');
    }
    
    /**
     * returns the name of the billable model
     *
     * @return string
     */
    public static function getBillableModelName() {
        throw new Tinebase_Exception_NotImplemented('Must be implemented seperately.');
    }
}
