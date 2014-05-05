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
class Timetracker_Model_Timeaccount extends Sales_Model_Accountable_Abstract implements Sales_Model_Billable_Interface
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
            array('type' => 'RESPONSIBLE', 'degree' => 'sibling', 'text' => 'Responsible person', 'max' => '1:0'), // _('Cost Center')
        )
        )
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
     * @return void
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        $this->_filters['budget']  = array('Digits', new Zend_Filter_Empty(NULL));
        $this->_filters['price'] = array(new Zend_Filter_PregReplace('/,/', '.'), new Zend_Filter_Empty(NULL));
        $this->_filters['is_open'] = new Zend_Filter_Empty(0);
        
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
     * @return Timetracker_Model_TimesheetFilter
     */
    protected function _getBillableTimesheetsFilter(Tinebase_DateTime $date)
    {
        // find all timesheets for this timeaccount the last year
        $startDate = clone $date;
        $startDate->subYear(1);
        
        // if this is not budgeted, show for timesheets in this period
        $filter = new Timetracker_Model_TimesheetFilter(array(
            array('field' => 'start_date', 'operator' => 'before', 'value' => $date),
            array('field' => 'start_date', 'operator' => 'after', 'value' => $startDate),
            array('field' => 'is_cleared', 'operator' => 'equals', 'value' => FALSE),
        ), 'AND');
        
        $filter->addFilter(new Tinebase_Model_Filter_Text(
            array('field' => 'invoice_id', 'operator' => 'isnull', 'value' => NULL)
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
            $startDate->setDate($date->format('Y'), $date->format('m'), 1);
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
     * @return void
    */
    public function loadBillables(Tinebase_DateTime $date)
    {
        $this->_referenceDate = $date;
        $this->_billables = array();
        
        if (intval($this->budget) > 0) {
            
            $month = $date->format('Y-m');
            
            $this->_billables[$month] = array($this);
            
        } else {
            $timesheets = Timetracker_Controller_Timesheet::getInstance()->search($this->_getBillableTimesheetsFilter($date));
            
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
     * @return boolean
    */
    public function isBillable(Tinebase_DateTime $date, Sales_Model_Contract $contract = NULL)
    {
        $this->_referenceDate = $date;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->toArray(), true));
        
        if (intval($this->budget) > 0 && $this->status == 'to bill' && $this->invoice_id == NULL) {
            // if there is a budget, bill it
            return TRUE;
            
        } else {
            $pagination = new Tinebase_Model_Pagination(array('limit' => 1));
            $timesheets = Timetracker_Controller_Timesheet::getInstance()->search($this->_getBillableTimesheetsFilter($date), $pagination, FALSE, /* $_onlyIds = */ TRUE);
        
            if (! empty($timesheets))  {
                return TRUE;
            }
        }
        
        // no match, not billable
        return FALSE;
    }
    
    /**
     * the invoice_id - field of all billables of this accountable gets the id of this invoice
     *
     * @param Sales_Model_Invoice $invoice
     */
    public function conjunctInvoiceWithBillables($invoice)
    {
        if (intval($this->budget) > 0) {
            $this->invoice_id = $invoice->getId();
            Timetracker_Controller_Timeaccount::getInstance()->update($this);
        } else {
            $ids = $this->_getIdsOfBillables();
            
            if (! empty($ids)) {
                $filter = new Timetracker_Model_TimesheetFilter(array());
                $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'id', 'operator' => 'in', 'value' => $ids)));
                
                Timetracker_Controller_Timesheet::getInstance()->updateMultiple($filter, array('invoice_id' => $invoice->getId()));
            }
        }
    }
    
    /**
     * returns the unit of the accountable
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
        // if this timeaccount has a budget, close and bill this and set cleared at date
        if (intval($this->budget) > 0) {
            $this->is_open    = 0;
            $this->status     = 'billed';
            $this->cleared_at = Tinebase_DateTime::now();
            
            Timetracker_Controller_Timeaccount::getInstance()->update($this);
            
        } else {
            // otherwise clear all timesheets of this invoice
            $tsController = Timetracker_Controller_Timesheet::getInstance();
            
            $filter = new Timetracker_Model_TimesheetFilter(array(), 'AND');
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'is_cleared', 'operator' => 'equals', 'value' => 0)));
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $this->getId())));
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice->getId())));
            
            $tsController->updateMultiple($filter, array('is_cleared' => 1));
        }
    }
    
}
