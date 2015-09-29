<?php
/**
 * class to hold Timeaccount data
 * 
 * @package     Timetracker
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        update validators (default values, mandatory fields)
 * @todo        add setFromJson with relation handling
 */

/**
 * class to hold Timeaccount data
 * 
 * @package     Timetracker
 * @subpackage  Model
 */
class Timetracker_Model_Timeaccount extends Sales_Model_Accountable_Abstract
{
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Timetracker';

    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        array('relatedApp' => 'Sales', 'relatedModel' => 'CostCenter', 'config' => array(
            array('type' => 'COST_CENTER', 'degree' => 'sibling', 'text' => 'Cost Center', 'max' => '1:0'), // _('Cost Center')
            )
        ),
        array('relatedApp' => 'Addressbook', 'relatedModel' => 'Contact', 'config' => array(
            array('type' => 'RESPONSIBLE', 'degree' => 'sibling', 'text' => 'Responsible Person', 'max' => '1:0'), // _('Responsible Person')
        )
        )
    );
    
    /**
     * if foreign Id fields should be resolved on search and get from json
     * should have this format:
     *     array('Calendar_Model_Contact' => 'contact_id', ...)
     * or for more fields:
     *     array('Calendar_Model_Contact' => array('contact_id', 'customer_id), ...)
     * (e.g. resolves contact_id with the corresponding Model)
     *
     * @var array
     */
    protected static $_resolveForeignIdFields = array(
        'Sales_Model_Invoice' => array('invoice_id'),
    );
    
    /**
     * relation type: contract
     *
     */
    const RELATION_TYPE_CONTRACT = 'CONTRACT';
    
    /**
     * deadline type: none
     * = no deadline for timesheets
     */
    const DEADLINE_NONE = 'none';
    
    /**
     * deadline type: last week
     * = booking timesheets allowed until monday midnight for the last week
     */
    const DEADLINE_LASTWEEK = 'lastweek';
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'container_id'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'account_grants'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'title'                 => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'number'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'description'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'budget'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'budget_unit'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'hours'),
        'price'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'price_unit'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'hours'),
        'is_open'               => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
        'is_billable'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
        'billed_in'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'invoice_id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'status'                => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'not yet billed'),
        'cleared_at'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // how long can users book timesheets for this timeaccount 
        'deadline'              => array(
            Zend_Filter_Input::ALLOW_EMPTY      => true, 
            Zend_Filter_Input::DEFAULT_VALUE    => self::DEADLINE_NONE,
            array('InArray', array(self::DEADLINE_NONE, self::DEADLINE_LASTWEEK)),
        ),    
    // modlog information
        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'seq'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // relations (linked Timetracker_Model_Timeaccount records) and other metadata
        'relations'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'tags'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'notes'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'grants'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'customfields'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
       
        'attachments'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );

    /**
     * name of fields containing datetime or an array of datetime information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time',
        'cleared_at'
    );
    
    /**
     * overwrite constructor to add more filters
     *
     * @param mixed $_data
     * @param bool $_bypassFilters
     * @param mixed $_convertDates
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        $this->_filters['budget']  = new Zend_Filter_Empty(0);
        $this->_filters['price'] = array(new Zend_Filter_PregReplace('/,/', '.'), new Zend_Filter_Empty(NULL));
        $this->_filters['is_open'] = new Zend_Filter_Empty(0);
        $this->_filters['invoice_id']  = array(new Zend_Filter_Empty(NULL));
        
        return parent::__construct($_data, $_bypassFilters, $_convertDates);
    }

    /**
     * set from array data
     *
     * @param array $_data
     * @return void
     */
    public function setFromArray(array $_data)
    {
        parent::setFromArray($_data);
        
        if (isset($_data['grants']) && !empty($_data['grants'])) {
            $this->grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants', $_data['grants']);
        }  else {
            $this->grants = new Tinebase_Record_RecordSet('Timetracker_Model_TimeaccountGrants');
        }
    }
    
    /**
     * returns the timesheet filter 
     * 
     * @param Tinebase_DateTime $date
     * @param Sales_Model_Contract
     * @return Timetracker_Model_TimesheetFilter
     */
    protected function _getBillableTimesheetsFilter(Tinebase_DateTime $date, Sales_Model_Contract $contract = NULL)
    {
        $endDate = clone $date;
        $endDate->setDate($endDate->format('Y'), $endDate->format('n'), 1);
        $endDate->setTime(0,0,0);
        $endDate->subSecond(1);
        
        if (! $contract) {
            $contract = $this->_referenceContract;
        }
        
        $csdt = clone $contract->start_date;
        
        $csdt->setTimezone('UTC');
        $endDate->setTimezone('UTC');
        
        $border = new Tinebase_DateTime(Sales_Config::getInstance()->get(Sales_Config::IGNORE_BILLABLES_BEFORE));
        
        // if this is not budgeted, show for timesheets in this period
        $filter = new Timetracker_Model_TimesheetFilter(array(
            array('field' => 'start_date', 'operator' => 'before_or_equals', 'value' => $endDate),
            array('field' => 'start_date', 'operator' => 'after_or_equals', 'value' => $csdt),
            array('field' => 'start_date', 'operator' => 'after_or_equals', 'value' => $border),
            array('field' => 'is_cleared', 'operator' => 'equals', 'value' => FALSE),
            array('field' => 'is_billable', 'operator' => 'equals', 'value' => TRUE),
        ), 'AND');
        
        if (! is_null($contract->end_date)) {
            $ced = clone $contract->end_date;
            $ced->setTimezone('UTC');
            
            $filter->addFilter(new Tinebase_Model_Filter_Date(
                array('field' => 'start_date', 'operator' => 'before_or_equals', 'value' => $ced)
            ));
        }
        
        $filter->addFilter(new Tinebase_Model_Filter_Text(
            array('field' => 'invoice_id', 'operator' => 'equals', 'value' => '')
        ));
        
        $filter->addFilter(new Tinebase_Model_Filter_Text(
            array('field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $this->getId())
        ));
        
        return $filter;
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
            $date = $this->_referenceDate;
        }
        
        // if this is a timeaccount with a budget, the timeaccount is the billable
        if (intval($this->budget > 0)) {
            
            $startDate = clone $date;
            $startDate->setDate($date->format('Y'), $date->format('n'), 1);
            $endDate = clone $startDate;
            $endDate->addMonth(1)->subSecond(1);
            
            $interval = array($startDate, $endDate);
        } else {
            $interval = parent::getInterval($date);
        }
        
        return $interval;
    }
    
    /**
     * returns the quantity of this billable
     *
     * @return float
     */
    public function getQuantity()
    {
        return (float) $this->budget;
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
        $this->_billables = array();
        
        if (intval($this->budget) > 0) {
            
            $month = $date->format('Y-m');
            
            $this->_billables[$month] = array($this);
            
        } else {
            if ($productAggregate !== null && $productAggregate->billing_point == 'end') {
                $enddate = $this->_getEndDate($productAggregate);
            } else {
                $enddate = null;
            }
            
            $filter = $this->_getBillableTimesheetsFilter($enddate !== null ? $enddate : $date);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' TS Filter: ' . print_r($filter->toArray(), true));
            $timesheets = Timetracker_Controller_Timesheet::getInstance()->search($filter);
            
            foreach($timesheets as $timesheet) {
                $month = new Tinebase_DateTime($timesheet->start_date);
                $month = $month->format('Y-m');
                
                if (! isset($this->_billables[$month])) {
                    $this->_billables[$month] = array();
                }
                
                $this->_billables[$month][] = $timesheet;
            }
        }
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
        $this->_referenceDate = clone $date;
        $this->_referenceContract = $contract;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->toArray(), true));
        
        if (! $this->is_open || $this->status == 'billed' || $this->cleared_at || $this->invoice_id) {
            return FALSE;
        }
        
        if (intval($this->budget) > 0) {
             if ($this->status == 'to bill' && $this->invoice_id == NULL) {
                // if there is a budget, this timeaccount should be billed and there is no invoice linked, bill it
                return TRUE;
             } else {
                 return FALSE;
             }
        } else {
            
            if (! $this->is_billable) {
                return FALSE;
            }
            
            if ($productAggregate !== null && $productAggregate->billing_point == 'end') {
                $enddate = $this->_getEndDate($productAggregate);
            } else {
                $enddate = null;
            }
            
            $pagination = new Tinebase_Model_Pagination(array('limit' => 1));
            $filter = $this->_getBillableTimesheetsFilter($enddate !== null ? $enddate : $date, $contract);

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Use filter in "isBillable"-Method of Timetracker_Model_Timeaccount: '
                    . print_r($filter->toArray(), 1));
            }
            
            $timesheets = Timetracker_Controller_Timesheet::getInstance()->search($filter, $pagination, FALSE, /* $_onlyIds = */ TRUE);
            
            if (! empty($timesheets))  {
                return TRUE;
            }
        }
        
        // no match, not billable
        return FALSE;
    }
    
    /**
     * returns the end date to look for timesheets
     * 
     * @param Sales_Model_ProductAggregate $productAggregate
     * 
     * @return Tinebase_DateTime
     */
    protected function _getEndDate(Sales_Model_ProductAggregate $productAggregate)
    {
        if ($productAggregate->last_autobill) {
            $enddate = clone $productAggregate->last_autobill;
        } else {
            $enddate = clone ( ($productAggregate->start_date && $productAggregate->start_date->isLaterOrEquals($this->_referenceContract->start_date)) ? $productAggregate->start_date : $this->_referenceContract->start_date );  
        }
        while ($enddate->isEarlier($this->_referenceDate)) {
            $enddate->addMonth($productAggregate->interval);
        }
        if ($enddate->isLater($this->_referenceDate)) {
            $enddate->subMonth($productAggregate->interval);
        }
        
        return $enddate;
    }
    
    /**
     * returns the name of the billable controller
     *
     * @return string
     */
    public static function getBillableControllerName() {
        return 'Timetracker_Controller_Timesheet';
    }
    
    /**
     * returns the name of the billable filter
     *
     * @return string
    */
    public static function getBillableFilterName() {
        return 'Timetracker_Model_TimesheetFilter';
    }
    
    /**
     * returns the name of the billable model
     *
     * @return string
    */
    public static function getBillableModelName() {
        return 'Timetracker_Model_Timesheet';
    }
    
    /**
     * the invoice_id - field of all billables of this accountable gets the id of this invoice
     *
     * @param Sales_Model_Invoice $invoice
     */
    public function conjunctInvoiceWithBillables($invoice)
    {
        $tsController = Timetracker_Controller_Timesheet::getInstance();
        $this->_disableTimesheetChecks($tsController);
        
        if (intval($this->budget) > 0) {
            // set this ta billed
            $this->invoice_id = $invoice->getId();
            Timetracker_Controller_Timeaccount::getInstance()->update($this, FALSE);

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' TA got budget: set all unbilled TS of this TA billed');
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' TA:' . print_r($this->toArray(), true));

            $filter = new Timetracker_Model_TimesheetFilter(array(
                array('field' => 'is_cleared', 'operator' => 'equals', 'value' => FALSE),
                array('field' => 'is_billable', 'operator' => 'equals', 'value' => TRUE),
            ), 'AND');
            // NOTE: using text filter here for id (operator equals is not defined in default timeaccount_id filter)
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $this->getId())));
            $tsController->updateMultiple($filter, array('invoice_id' => $invoice->getId()));
        } else {
            $ids = $this->_getIdsOfBillables();
            
            if (! empty($ids)) {
                $filter = new Timetracker_Model_TimesheetFilter(array());
                $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'id', 'operator' => 'in', 'value' => $ids)));

                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Bill ' . count($ids) . ' TS');

                $tsController->updateMultiple($filter, array('invoice_id' => $invoice->getId()));
            }
        }
        
        $this->_enableTimesheetChecks($tsController);
    }
    
    /**
     * disable ts checks
     * 
     * @param Timetracker_Controller_Timesheet $tsController
     */
    protected function _disableTimesheetChecks($tsController)
    {
        $tsController->doCheckDeadLine(false);
        $tsController->doContainerACLChecks(false);
        $tsController->doRightChecks(false);
        $tsController->doRelationUpdate(false);
    }
    
    /**
     * enable ts checks
     * 
     * @param Timetracker_Controller_Timesheet $tsController
     */
    protected function _enableTimesheetChecks($tsController)
    {
        $tsController->doCheckDeadLine(true);
        $tsController->doContainerACLChecks(true);
        $tsController->doRightChecks(true);
        $tsController->doRelationUpdate(true);
    }
    
    /**
     * returns the unit of this billable
     *
     * @return string
     */
    public function getUnit()
    {
        return 'hour'; // _('hour')
    }
    
    /**
     * set each billable of this accountable billed
     *
     * @param Sales_Model_Invoice $invoice
     */
    public function clearBillables(Sales_Model_Invoice $invoice)
    {
        $tsController = Timetracker_Controller_Timesheet::getInstance();
        $this->_disableTimesheetChecks($tsController);
        
        $filter = new Timetracker_Model_TimesheetFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'is_cleared', 'operator' => 'equals', 'value' => 0)));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $this->getId())));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice->getId())));
        
        // if this timeaccount has a budget, close and bill this and set cleared at date
        if (intval($this->budget) > 0) {
            $this->is_open    = 0;
            $this->status     = 'billed';
            $this->cleared_at = Tinebase_DateTime::now();
            
            Timetracker_Controller_Timeaccount::getInstance()->update($this);
            // also clear all timesheets belonging to this invoice and timeaccount
            $tsController->updateMultiple($filter, array('is_cleared' => 1));
        } else {
            // otherwise clear all timesheets of this invoice
            $tsController->updateMultiple($filter, array('is_cleared' => 1));
        }
        
        $this->_enableTimesheetChecks($tsController);
    }
}
