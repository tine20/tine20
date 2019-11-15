<?php
/**
 * class to hold Timeaccount data
 * 
 * @package     Timetracker
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold Timeaccount data
 * 
 * @package     Timetracker
 * @subpackage  Model
 */
class Timetracker_Model_Timeaccount extends Sales_Model_Accountable_Abstract
{
    const TABLE_NAME = 'timetracker_timeaccount';

    const MODEL_NAME_PART = 'Timeaccount';

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
        'version'           => 13,
        'containerName'     => 'Timeaccount',
        'containersName'    => 'Timeaccounts',
        'recordName'        => 'Timeaccount',
        'recordsName'       => 'Timeaccounts', // ngettext('Timeaccount', 'Timeaccounts', n)
        'hasRelations'      => TRUE,
        'copyRelations'     => FALSE,
        'hasCustomFields'   => TRUE,
        'hasNotes'          => TRUE,
        'hasTags'           => TRUE,
        'modlogActive'      => TRUE,
        'hasAttachments'    => TRUE,
        'createModule'      => TRUE,
        'containerProperty' => 'container_id',
        'grantsModel'       => 'Timetracker_Model_TimeaccountGrants',
        'multipleEdit'      => TRUE,
        'requiredRight'     => 'manage',

        'titleProperty'     => '{{number}} - {{title}}{% if not is_open %} (closed) {% endif %}',
        'appName'           => Timetracker_Config::APP_NAME,
        'modelName'         => self::MODEL_NAME_PART,

        // TODO add this when we convert container to MCV2
//       'associations' => [\Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_ONE => [
//            'container' => [
//                'targetEntity' => 'Tinebase_Model_Container',
//                'fieldName' => 'container_id',
//                'joinColumns' => [[
//                    'name' => 'container_id',
//                    'referencedColumnName'  => 'id'
//                ]],
//            ]
//        ]],

        'table'             => array(
            'name'    => self::TABLE_NAME,
            'indexes' => array(
                'title' => array(
                    'columns' => array('title')
                ),
                'number' => array(
                    'columns' => array('number')
                ),
                'container_id' => array(
                    'columns' => array('container_id')
                ),
                'description' => array(
                    'columns' => array('description'),
                    'flags' => array('fulltext')
                ),
            ),
        ),

        'filterModel'       => array(
            'contract'          => array(
                'filter'            => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'title'             => 'Contract', // _('Contract')
                'options'           => array(
                    'controller'        => 'Sales_Controller_Contract',
                    'filtergroup'       => 'Sales_Model_ContractFilter',
                    'own_filtergroup'   => 'Timetracker_Model_TimeaccountFilter',
                    'own_controller'    => 'Timetracker_Controller_Timeaccount',
                    'related_model'     => 'Sales_Model_Contract',
                ),
                'jsConfig'          => array('filtertype' => 'timetracker.timeaccountcontract')
            ),
            'responsible'       => array(
                'filter'            => 'Tinebase_Model_Filter_ExplicitRelatedRecord',
                'title'             => 'Responsible',
                'options'           => array(
                    'controller'        => 'Addressbook_Controller_Contact',
                    'filtergroup'       => 'Addressbook_Model_ContactFilter',
                    'own_filtergroup'   => 'Timetracker_Model_TimeaccountFilter',
                    'own_controller'    => 'Timetracker_Controller_Timeaccount',
                    'related_model'     => 'Addressbook_Model_Contact',
                ),
                'jsConfig'          => array('filtertype' => 'timetracker.timeaccountresponsible')
            )
        ),

        'fields'            => array(
            'account_grants'    => array(
                'label'                 => NULL,
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'type'                  => 'virtual',
            ),
            'title'             => array(
                'label'                 => 'Title', //_('Title')
                'duplicateCheckGroup'   => 'title',
                'queryFilter'           => TRUE,
                'showInDetailsPanel'    => TRUE,
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
            ),
            'number'            => array(
                'label'                 => 'Number', //_('Number')
                'duplicateCheckGroup'   => 'number',
                'queryFilter'           => TRUE,
                'showInDetailsPanel'    => TRUE,
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'nullable'              => true,
            ),
            'description'       => array(
                'label'                 => 'Description', // _('Description')
                'type'                  => 'fulltext',
                'showInDetailsPanel'    => TRUE,
                'queryFilter'           => true,
                'nullable'              => true,
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'budget'            => array(
                'type'                  => 'float',
                'inputFilters'          => array('Zend_Filter_Digits', 'Zend_Filter_Empty' => NULL),
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'nullable'              => true,
            ),
            'budget_unit'       => array(
                'shy'                   => TRUE,
                'default'               => 'hours',
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'hours'),
            ),
            // @TODO price -> move this to Sales contracts/positions
            'price' => array(
                'type'         => 'money',
                'nullable'     => true,
                'validators'   => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE),
                'label'        => 'Price', // _('Price')
                'inputFilters' => array('Zend_Filter_Empty' => NULL),
            ),
            // @TODO price_unit -> move this to Sales contracts/positions
            'price_unit'        => array(
                'shy'                   => TRUE,
                'nullable'              => true,
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'is_open'           => array(
                // is_open = Status, status = Billed
                'label'                 => 'Status', //_('Status')
                'type'                  => 'boolean',
                'default'               => 1,
                'inputFilters'          => array('Zend_Filter_Empty' => 0),
                'filterDefinition'      => array(
                    'filter'                => 'Tinebase_Model_Filter_Bool',
                    'jsConfig'              => array('filtertype' => 'timetracker.timeaccountstatus')
                ),
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
            ),
            'is_billable'       => array(
                // TODO why is this not visible / editable?
                'type'                  => 'boolean',
                'default'               => TRUE,
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
            ),
            'billed_in'         => array(
                'label'                 => "Cleared in", // _("Cleared in"),
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'copyOmit'              => true,
                'nullable'              => true,
            ),
            'invoice_id'        => array(
                'label'                 => 'Invoice', // _('Invoice')
                'type'                  => 'record',
                'inputFilters'          => array('Zend_Filter_Empty' => NULL),
                'nullable'              => true,
                'config'                => array(
                    'appName'               => 'Sales',
                    'modelName'             => 'Invoice',
                    'idProperty'            => 'id',
                    // TODO we should replace this with a generic approach to fetch configured models of an app
                    // -> APP_Frontend_Json::$_configuredModels should be moved from json to app controller
                    'feature'               => 'invoicesModule', // Sales_Config::FEATURE_INVOICES_MODULE
                ),
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'copyOmit'              => true,
            ),
            'status'            => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => self::STATUS_NOT_YET_BILLED),
                'nullable' => true,
                'default' => self::STATUS_NOT_YET_BILLED,
                'copyOmit'              => true,
                'label' => 'Billed', // _('Billed')
                'type' => 'keyfield',
                'name' => 'status',
            ),
            'cleared_at'        => array(
                'label'                 => "Cleared at", // _("Cleared at")
                'type'                  => 'datetime',
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'nullable'              => true,
                'copyOmit'              => true,
            ),
                'deadline' => array(
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => TRUE, Zend_Filter_Input::DEFAULT_VALUE => self::DEADLINE_NONE),
                'nullable' => true,
                'default' => self::DEADLINE_NONE,
                'label' => 'Booking deadline', // _('Booking deadline')
                'type' => 'keyfield',
                'name' => 'deadline',
            ),
            'grants'            => array(
                'label'                 => NULL,
                'type'                  => 'records',
                'config'                => array(
                    'appName'               => 'Timetracker',
                    'modelName'             => 'TimeaccountGrants',
                    'idProperty'            => 'id'
                ),
                'validators'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
            ),
            'responsible'       => array(
                'type'                  => 'virtual',
                'config'                => array(
                    'type'                  => 'relation',
                    'label'                 => 'Responsible',    // _('Responsible')
                    'config'                => array(
                        'appName'               => 'Addressbook',
                        'modelName'             => 'Contact',
                        'type'                  => 'RESPONSIBLE'
                    )
                )
            ),
            'contract'       => array(
                'type'                  => 'virtual',
                'config'                => array(
                    'type'                  => 'relation',
                    'label'                 => 'Contract',    // _('Contract')
                    'config'                => array(
                        'appName'               => 'Sales',
                        'modelName'             => 'Contract',
                        'type'                  => 'TIME_ACCOUNT'
                    )
                )
            ),
        )
    );

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
        ),
        array('relatedApp' => 'Sales', 'relatedModel' => 'Contract', 'config' => array(
            array('type' => 'TIME_ACCOUNT', 'degree' => 'sibling', 'text' => 'Time Account', 'max' => '1:0'), // _('Time Account')
        )
        ),
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

    const STATUS_NOT_YET_BILLED = 'not yet billed';
    const STATUS_TO_BILL = 'to bill';
    const STATUS_BILLED = 'billed';

    /**
     * set from array data
     *
     * @param array $_data
     * @return void
     */
    public function setFromArray(array &$_data)
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
        $this->_referenceDate = clone $date;
        $this->_billables = array();
        
        if (intval($this->budget) > 0) {

            $date = clone $date;
            $date->subMonth(1);
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

        if (intval($this->budget) > 0) {

            // we don't touch cleared TAs at all
            if ($this->cleared_at) {
                return false;
            }

            if ($this->status === self::STATUS_TO_BILL && $this->invoice_id !== NULL) {
                $this->_cleanToBillWithInvoiceId();
                return true;
            }

            if ($this->status === self::STATUS_TO_BILL && $this->invoice_id === NULL) {
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

            if ($this->status != self::STATUS_TO_BILL && $this->invoice_id != NULL) {
                return;
            }

            // set this ta billed
            $this->status = self::STATUS_BILLED;
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
        $tsController->doRightChecks(false);
        $tsController->doRelationUpdate(false);
        $tsController->setRequestContext(array('skipClosedCheck' => true));
    }
    
    /**
     * enable ts checks
     * 
     * @param Timetracker_Controller_Timesheet $tsController
     */
    protected function _enableTimesheetChecks($tsController)
    {
        $tsController->doCheckDeadLine(true);
        $tsController->doRightChecks(true);
        $tsController->doRelationUpdate(true);
        $tsController->setRequestContext(array('skipClosedCheck' => false));
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

            if ($this->invoice_id !== $invoice->getId()) {
                return;
            }

            $this->is_open    = 0;
            $this->status     = self::STATUS_BILLED;
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

    protected function _cleanToBillWithInvoiceId()
    {
        $this->invoice_id = null;
        Timetracker_Controller_Timeaccount::getInstance()->update($this);

        // we unassign all assigned TS
        $filter = new Timetracker_Model_TimesheetFilter(array(
            array('field' => 'is_cleared', 'operator' => 'equals', 'value' => FALSE),
            array('field' => 'is_billable', 'operator' => 'equals', 'value' => TRUE),
        ), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Text(
            array('field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $this->getId())
        ));

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' TS Filter: ' . print_r($filter->toArray(), true));
        Timetracker_Controller_Timesheet::getInstance()->updateMultiple($filter, array('invoice_id' => null));
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
        if (intval($this->budget) > 0) {

            // we dont touch cleared TAs at all
            if ($this->cleared_at) {
                return false;
            }

            if ($this->invoice_id === null) {
                if ($this->status === self::STATUS_TO_BILL) {
                    // we should bill this TA
                    return true;
                }
                // nothing to do
                return false;

                // a sanity checks required to fix old data...
            } elseif($this->status === self::STATUS_TO_BILL) {

                $this->_cleanToBillWithInvoiceId();

                // time to bill this TA now
                return true;

                // we are a relation of all invoices, but we will only be billed in one. If this is the one, we continue, else its not our business
            } elseif ($this->invoice_id != $invoice->getId()) {
                return false;
            }

            // did the status change? or anything else?
            if ($this->status !== self::STATUS_BILLED || $this->last_modified_time->isLater($invoice->creation_time)) {
                return true;
            }

            // we just assign all unassigned TS to our invoice silently and gracefully
            $filter = new Timetracker_Model_TimesheetFilter(array(
                array('field' => 'is_cleared', 'operator' => 'equals', 'value' => FALSE),
                array('field' => 'is_billable', 'operator' => 'equals', 'value' => TRUE),
            ), 'AND');
            $filter->addFilter(new Tinebase_Model_Filter_Text(
                array('field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $this->getId())
            ));
            $filter->addFilter(new Tinebase_Model_Filter_Text(
                array('field' => 'invoice_id', 'operator' => 'not', 'value' => $invoice->getId())
            ));
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' TS Filter: ' . print_r($filter->toArray(), true));
            Timetracker_Controller_Timesheet::getInstance()->updateMultiple($filter, array('invoice_id' => $invoice->getId()));

            return false;
        }

        $filter = new Timetracker_Model_TimesheetFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Text(
            array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice->getId())
        ));
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' TS Filter: ' . print_r($filter->toArray(), true));
        $timesheets = Timetracker_Controller_Timesheet::getInstance()->search($filter);
        $timesheets->setTimezone(Tinebase_Core::getUserTimezone());
        foreach($timesheets as $timesheet)
        {
            if ($timesheet->last_modified_time && $timesheet->last_modified_time->isLater($invoice->creation_time)) {
                return true;
            }
        }

        return false;
    }
}
