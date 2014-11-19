<?php
/**
 * customer controller for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * customer controller class for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Invoice extends Sales_Controller_NumberableAbstract
{
    protected $_cachedProducts = NULL;
    
    /**
     * the contract which is handled by _createAutoInvoicesForContract
     * 
     * @var Sales_Model_Contract
     */
    protected $_currentBillingContract = NULL;
    
    /**
     * the date which is used in _createAutoInvoicesForContract
     * 
     * @var Tinebase_DateTime
     */
    protected $_currentBillingDate = NULL;
    
    /**
     * the costcenter of the contract which is handled by _createAutoInvoicesForContract
     * 
     * @var Sales_Model_CostCenter
     */
    protected $_currentBillingCostCenter = NULL;
    
    /**
     * the customer of the contract which is handled by _createAutoInvoicesForContract
     *
     * @var Sales_Model_Customer
     */
    protected $_currentBillingCustomer = NULL;
    
    /**
     * holds the limit the iterator should have
     * 
     * @var integer
     */
    protected $_autoInvoiceIterationLimit = 25;
    
    /**
     * the number gets prefixed zeros until this amount of chars is reached
     * 
     * @var integer
     */
    protected $_numberZerofill = 5;
    
    /**
     * the prefix for the invoice
     * 
     * @var string
     */
    protected $_numberPrefix = 'R-';
    
    /**
     * holds the ids of all created invoices
     * 
     * @var array
     */
    protected $_autoInvoiceIterationResults = NULL;
    
    /**
     * holds the failures caught on a run
     * 
     * @var array
     */
    protected $_autoInvoiceIterationFailures = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Sales';
        $this->_backend = new Sales_Backend_Invoice();
        $this->_modelName = 'Sales_Model_Invoice';
        $this->_doContainerACLChecks = FALSE;
        $this->_cachedProducts = new Tinebase_Record_RecordSet('Sales_Model_Product');
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var Sales_Controller_Invoice
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Sales_Controller_Invoice
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }

    /**
     * processAutoInvoiceIteration
     * 
     * @param Tinebase_Record_RecordSet $contracts
     * @param Tinebase_DateTime $currentDate
     */
    public function processAutoInvoiceIteration($contracts, $currentDate)
    {
        Timetracker_Controller_Timeaccount::getInstance()->resolveCustomfields(FALSE);
        Timetracker_Controller_Timesheet::getInstance()->resolveCustomfields(FALSE);
        
        Sales_Controller_Contract::getInstance()->resolveCustomfields(FALSE);
        Sales_Controller_Contract::getInstance()->setHandleDependentRecords(FALSE);
        Sales_Controller_ProductAggregate::getInstance()->resolveCustomfields(FALSE);
        
        $contracts->setTimezone(Tinebase_Core::getUserTimezone());
        
        foreach ($contracts as $contract) {
            
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
            
            try {
                $this->_createAutoInvoicesForContract($contract, clone $currentDate);
                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            } catch (Exception $e) {
                Tinebase_TransactionManager::getInstance()->rollBack();
                
                $failure = 'Could not create auto invoice for contract "' . $contract->title . '" Exception: ' . $e->getCode() . ' has been thrown: "' . $e->getMessage() . '".';
                $this->_autoInvoiceIterationFailures[] = $failure;
                Tinebase_Exception::log($e, FALSE);
            }
        }
        
        Sales_Controller_Contract::getInstance()->setHandleDependentRecords(TRUE);
        Sales_Controller_Contract::getInstance()->resolveCustomfields(TRUE);
        Sales_Controller_ProductAggregate::getInstance()->resolveCustomfields(TRUE);
        
        Timetracker_Controller_Timeaccount::getInstance()->resolveCustomfields(TRUE);
        Timetracker_Controller_Timesheet::getInstance()->resolveCustomfields(TRUE);
    }
    
    /**
     * relation defaults
     * 
     * @return array
     */
    protected function _getRelationDefaults()
    {
        return array(
            'own_model'              => 'Sales_Model_Invoice',
            'own_backend'            => Tasks_Backend_Factory::SQL,
            'own_id'                 => NULL,
            'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_backend'        => Tasks_Backend_Factory::SQL,
            'type'                   => 'INVOICE_ITEM'
        );
    }
    
    /**
     * finds the costcenter of $this->_currentContract
     * 
     * @return Sales_Model_CostCenter|NULL
     */
    protected function _findCurrentCostCenter()
    {
        $this->_currentBillingCostCenter = NULL;
        
        foreach ($this->_currentBillingContract->relations as $relation) {
            if ($relation->type == 'LEAD_COST_CENTER' && $relation->related_model == 'Sales_Model_CostCenter') {
                $this->_currentBillingCostCenter = $relation->related_record;
            }
        }
    }
    
    /**
     * finds the customer of $this->_currentContract
     * 
     * @return Sales_Model_Customer|NULL
     */
    protected function _findCurrentCustomer()
    {
        $this->_currentBillingCustomer = NULL;
            
        foreach ($this->_currentBillingContract->relations as $relation) {
            if ($relation->type == 'CUSTOMER' && $relation->related_model == 'Sales_Model_Customer') {
                $this->_currentBillingCustomer = $relation->related_record;
            }
        } 
    }
    
    /**
     * validates $this->_currentContract and sets $this->_current...
     * 
     * @param Sales_Model_Contract
     * @return boolean
     */
    protected function _validateContract(Sales_Model_Contract $contract)
    {
        $this->_currentBillingContract = $contract;
        
        $this->_findCurrentCostCenter();
        $this->_findCurrentCustomer();
        
        // find address, otherwise do not bill this contract
        if (! $contract->billing_address_id) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                $failure = 'Could not create auto invoice for contract "' . $contract->number . '", because no billing address could be found!';
                $this->_autoInvoiceIterationFailures[] = $failure;
                Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' ' . $failure, Zend_Log::INFO);
            }
            
            $this->_currentBillingContract = NULL;
        }
        
        if (! $this->_currentBillingCostCenter) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                $failure = 'Could not create auto invoice for contract "' . $contract->number . '", because no costcenter could be found!';
                $this->_autoInvoiceIterationFailures[] = $failure;
                Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' ' . $failure, Zend_Log::INFO);
            }
            
            $this->_currentBillingContract = NULL;
        }
        
        if (! $this->_currentBillingCustomer) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                $failure = 'Could not create auto invoice for contract "' . $contract->number . '", because no customer could be found!';
                $this->_autoInvoiceIterationFailures[] = $failure;
                Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' ' . $failure, Zend_Log::INFO);
            }
            
            $this->_currentBillingContract = NULL;
        }
        
        return ($this->_currentBillingContract != NULL);
    }
    
    /**
     * finds product aggregates for $this->_currentBillingContract
     * 
     * @return Tinebase_Record_RecordSet
     */
    protected function _findProductAggregates()
    {
        $filter = new Sales_Model_ProductAggregateFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(
                array('field' => 'contract_id', 'operator' => 'equals', 'value' => $this->_currentBillingContract->getId())
        ));
        
        $productAggregates = Sales_Controller_ProductAggregate::getInstance()->search($filter);
        $productAggregates->setTimezone(Tinebase_Core::getUserTimezone());
        
        return $productAggregates;
    }
    
    /**
     * fire event to allow other applications do some work before billing
     */
    protected function _firePrebillEvent()
    {
        $event = new Sales_Event_BeforeBillContract();
        $event->contract = $this->_currentBillingContract;
        $event->date     = $this->_currentBillingDate;
        
        Tinebase_Event::fireEvent($event);
    }
    
    /**
     * prepares the relations and finds all billable accountables for the invoice
     * 
     * @param Tinebase_Record_RecordSet $productAggregates
     * @return array
     */
    protected function _prepareInvoiceRelationsAndFindBillableAccountables($productAggregates)
    {
        $modelsToBill = array();
        $simpleProductsToBill = array();
        $modelsToSkip = array();
        
        // iterate product aggregates to get the billing definition for the models
        foreach ($productAggregates as $productAggregate) {
            // is null, if this is the first time to bill the product aggregate
            $lastBilled = $productAggregate->last_autobill == NULL ? NULL : clone $productAggregate->last_autobill;
        
            // if the product has been billed already, add the interval
            if ($lastBilled) {
                $nextBill = clone $lastBilled;
                $nextBill->addMonth($productAggregate->interval);
            } else {
                // it hasn't been billed already, so take the start_date of the contract as date
                $nextBill = clone $this->_currentBillingContract->start_date;
        
                // add interval, if the billing point is at the end of the interval
                if ($productAggregate->billing_point == 'end') {
                    $nextBill->addMonth($productAggregate->interval);
                }
            }
        
            // assure creating the last bill if a contract has been terminated
            if (($this->_currentBillingContract->end_date !== NULL) && $nextBill->isLater($this->_currentBillingContract->end_date)) {
                $nextBill = clone $this->_currentBillingContract->end_date;
            }
        
            $nextBill->setTime(0,0,0);
        
            $product = $this->_cachedProducts->getById($productAggregate->product_id);
        
            if (! $product) {
                $product = Sales_Controller_Product::getInstance()->get($productAggregate->product_id);
                $this->_cachedProducts->addRecord($product);
            }
            
            // find out if this model has to be billed or skipped
            if ($this->_currentBillingDate->isLaterOrEquals($nextBill)) {
                if (($product->accountable == 'Sales_Model_Product') || ($product->accountable == '')) {
                    $simpleProductsToBill[] = array('pa' => $productAggregate, 'ac' => $productAggregate);
                } else {
                    $modelsToBill[$product->accountable] = array();
                    $modelsToBill[$product->accountable]['pa'] = $productAggregate;
                }
            } else {
                $modelsToSkip[] = $product->accountable;
            }
        }
        
        // this holds all relations for the invoice
        $relations            = array();
        $billableAccountables = array();
        
        // iterate relations, look for accountables, prepare relations
        foreach ($this->_currentBillingContract->relations as $relation) {
            // use productaggregate definition, if it has been found
            if (array_key_exists($relation->related_model, $modelsToBill) && (! in_array($relation->related_model, $modelsToSkip))) {
                $relations[] = array_merge(array(
                    'related_model'  => get_class($relation->related_record),
                    'related_id'     => $relation->related_id,
                    'related_record' => $relation->related_record->toArray(),
                ), $this->_getRelationDefaults());
        
        
                $billableAccountables[] = array('ac' => $relation->related_record, 'pa' => $modelsToBill[$relation->related_model]['pa']);
        
            } elseif ((! in_array($relation->related_model, $modelsToSkip)) && in_array('Sales_Model_Accountable_Interface', class_implements($relation->related_model))) {
                // no product aggregate definition has been found -> use default values
                $relations[] = array_merge(array(
                    'related_model'  => get_class($relation->related_record),
                    'related_id'     => $relation->related_id,
                    'related_record' => $relation->related_record->toArray(),
                ), $this->_getRelationDefaults());
        
                $billableAccountables[] = array(
                    'ac' => $relation->related_record, 
                    'pa' => $relation->related_record->getDefaultProductAggregate($this->_currentBillingContract)
                );
            }
        }
        
        foreach ($simpleProductsToBill as $product) {
            $relations[] = array_merge(array(
                    'related_model'  => 'Sales_Model_ProductAggregate',
                    'related_id'     => $product['pa']->getId(),
                    'related_record' => $product['pa']->toArray(),
            ), $this->_getRelationDefaults());
        
            $billableAccountables[] = $product;
        }
        
        // add contract relation
        $relations[] = array(
            'own_model'              => 'Sales_Model_Invoice',
            'own_backend'            => Tasks_Backend_Factory::SQL,
            'own_id'                 => NULL,
            'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'          => 'Sales_Model_Contract',
            'related_backend'        => Tasks_Backend_Factory::SQL,
            'related_id'             => $this->_currentBillingContract->getId(),
            'related_record'         => $this->_currentBillingContract->toArray(),
            'type'                   => 'CONTRACT',
        );
        
        // add customer relation
        $relations[] = array(
            'own_model'              => 'Sales_Model_Invoice',
            'own_backend'            => Tasks_Backend_Factory::SQL,
            'own_id'                 => NULL,
            'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_model'          => 'Sales_Model_Customer',
            'related_backend'        => Tasks_Backend_Factory::SQL,
            'related_id'             => $this->_currentBillingCustomer['id'],
            'related_record'         => $this->_currentBillingCustomer,
            'type'                   => 'CUSTOMER'
        );
        
        return array($relations, $billableAccountables);
    }
    
    /**
     * 
     * @param array $billableAccountables
     * @return array
     */
    protected function _findInvoicePositionsAndInvoiceInterval($billableAccountables)
    {
        // put each position into
        $invoicePositions = new Tinebase_Record_RecordSet('Sales_Model_InvoicePosition');
        $earliestStartDate = $latestEndDate = NULL;
        
        foreach ($billableAccountables as $ba) {
            if (! $ba['ac']->isBillable($this->_currentBillingDate, $this->_currentBillingContract, $ba['pa'])) {
                continue;
            }
        
            $ba['ac']->loadBillables($this->_currentBillingDate, $ba['pa']);
            $billables = $ba['ac']->getBillables($this->_currentBillingDate, $ba['pa']);
        
            if (empty($billables)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' No efforts for the accountable ' . $ba['ac']->getId() . ' of contract "' . $this->_currentBillingContract->number . '" could be found.', Zend_Log::INFO);
                }
                continue;
            }
        
            $invoicePositions = $invoicePositions->merge($this->_getInvoicePositionsFromBillables($billables, $ba['ac']));
        
            list($startDate, $endDate) = $ba['ac']->getInterval();
        
            if (! $latestEndDate) {
                $latestEndDate = $endDate;
            } elseif ($endDate > $latestEndDate) {
                $latestEndDate = clone $endDate;
            }
            if (! $earliestStartDate) {
                $earliestStartDate = clone $startDate;
            } elseif ($startDate < $earliestStartDate) {
                $earliestStartDate = clone $startDate;
            }
        }
        
        return array($invoicePositions, $earliestStartDate, $latestEndDate);
    }
    
    /**
     * create auto invoices for one contract
     * 
     * @param Sales_Model_Contract $contract
     * @param Tinebase_DateTime $currentDate
     */
    protected function _createAutoInvoicesForContract(Sales_Model_Contract $contract, Tinebase_DateTime $currentDate)
    {
        // set this current billing date (user timezone)
        $this->_currentBillingDate     = $currentDate;
        
        // check all prerequisites needed for billing of the contract
        if (! $this->_validateContract($contract)) {
            return false;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Processing contract "' . $this->_currentBillingContract->number . '"');
        }
        
        // fire event to allow other applications do some work before billing
        $this->_firePrebillEvent();
        
        // find product aggregates of the current contract
        $productAggregates = $this->_findProductAggregates();
        
        // prepare relations and find all billable accountables of the current contract
        list($relations, $billableAccountables) = $this->_prepareInvoiceRelationsAndFindBillableAccountables($productAggregates);
        
        // find invoice positions and the first start date and last end date of all billables
        list($invoicePositions, $earliestStartDate, $latestEndDate) = $this->_findInvoicePositionsAndInvoiceInterval($billableAccountables);
        
        // if there are no positions, no bill will be created, but the last_autobill info is set, if the current date is later 
        if ($invoicePositions->count() == 0) {
        
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' No efforts for the contract "' . $this->_currentBillingContract->title . '" could be found.', Zend_Log::INFO);
            }
            
            return false;
        }
        
        // prepare invoice
        $invoice = new Sales_Model_Invoice(array(
            'is_auto'       => TRUE,
            'description'   => $this->_currentBillingContract->title . ' (' . $this->_currentBillingDate->toString() . ')',
            'type'          => 'INVOICE',
            'address_id'    => $this->_currentBillingContract->billing_address_id,
            'credit_term'   => $this->_currentBillingCustomer['credit_term'],
            'customer_id'   => $this->_currentBillingCustomer['id'],
            'costcenter_id' => $this->_currentBillingCostCenter->getId(),
            'start_date'    => $earliestStartDate,
            'end_date'      => $latestEndDate,
            'positions'     => $invoicePositions->toArray(),
            'date'          => NULL,
            'sales_tax'     => 19
        ));
        
        $invoice->relations = $relations;
        
        $invoice->setTimezone('UTC', TRUE);

        // create invoice
        $invoice = $this->create($invoice);
        $this->_autoInvoiceIterationResults[] = $invoice->getId();
        
        $paToUpdate = array();
        
        // conjunct billables with invoice, find out which productaggregates to update
        foreach($billableAccountables as $ba) {
            $ba['ac']->conjunctInvoiceWithBillables($invoice);
            if ($ba['pa']->getId()) {
                $paToUpdate[$ba['pa']->getId()] = $ba['pa'];
            }
        }
        
        foreach($paToUpdate as $paId => $productAggregate) {
            $firstBill = (! $productAggregate->last_autobill);
            
            $lab = $productAggregate->last_autobill ? clone $productAggregate->last_autobill : ($productAggregate->start_date ? clone $productAggregate->start_date : clone $this->_currentBillingContract->start_date);
            $lab->setTimezone(Tinebase_Core::getUserTimezone());
            $lab->setTime(0,0,0);
            
            if (! $firstBill) {
                $lab->addMonth($productAggregate->interval);
            } else {
                if ($productAggregate->billing_point == 'end') {
                    // if first bill, add interval on billing_point end
                    $lab->addMonth($productAggregate->interval);
                }
            }

            $productAggregate->last_autobill = $lab;
            $productAggregate->setTimezone('UTC');
            
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updating last_autobill of "' . $productAggregate->getId() . '": ' . $lab->__toString());
            }
            
            Sales_Controller_ProductAggregate::getInstance()->update($productAggregate);
        }
    }
    
    /**
     * creates the auto invoices, gets called by cli
     * 
     * @param Tinebase_DateTime $currentDate
     * @param Sales_Model_Contract $contract
     */
    public function createAutoInvoices(Tinebase_DateTime $currentDate, Sales_Model_Contract $contract = NULL)
    {
        $this->_autoInvoiceIterationResults  = array();
        $this->_autoInvoiceIterationFailures = array();
        
        $contractBackend = new Sales_Backend_Contract();
        $ids = $contract ? array($contract->getId()) : $contractBackend->getBillableContractIds($currentDate);
        
        $filter = new Sales_Model_ContractFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'id', 'operator' => 'in', 'value' => $ids)));
        
        $iterator = new Tinebase_Record_Iterator(array(
            'iteratable' => $this,
            'controller' => Sales_Controller_Contract::getInstance(),
            'filter'     => $filter,
            'options'    => array(
                'getRelations' => TRUE,
                'limit' => $this->_autoInvoiceIterationLimit
            ),
            'function'   => 'processAutoInvoiceIteration',
        ));
        
        $iterator->iterate($currentDate);
        
        $result = array(
            'failures'       => $this->_autoInvoiceIterationFailures,
            'failures_count' => count($this->_autoInvoiceIterationFailures),
            'created'        => $this->_autoInvoiceIterationResults,
            'created_count'  => count($this->_autoInvoiceIterationResults)
        );
        
        return $result;
    }
    
    /**
     * creates invoice positions by the billables per each month
     * 
     * @param array $billables
     * @param Sales_Model_Accountable_Interface $accountable
     * @return Tinebase_Record_RecordSet
     */
    protected function _getInvoicePositionsFromBillables(array $billables, Sales_Model_Accountable_Interface $accountable)
    {
        $invoicePositions = new Tinebase_Record_RecordSet('Sales_Model_InvoicePosition');
        
        foreach($billables as $month => $billablesPerMonth) {
            
            if ($accountable->sumBillables()) {
                $sumQuantity = 0.0;
                
                foreach($billablesPerMonth as $billable) {
                    $qty = $billable->getQuantity();
                    $sumQuantity = $sumQuantity + $qty;
                }
                
                $pos = array(
                    'month' => $month,
                    'model' => get_class($accountable),
                    'accountable_id' => $accountable->getId(),
                    'title' => $accountable->getTitle(),
                    'quantity' => $sumQuantity,
                    'unit' => $billable->getUnit()
                );
            
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' Create invoice position ' . print_r($pos, 1), Zend_Log::DEBUG);
                }
                
                $invoicePositions->addRecord(new Sales_Model_InvoicePosition($pos));
                
            } else {
                foreach($billablesPerMonth as $billable) {
                    $pos = array(
                        'month' => $month,
                        'model' => get_class($accountable),
                        'accountable_id' => $accountable->getId(),
                        'title' => $accountable->getTitle(),
                        'quantity' => $billable->getQuantity(),
                        'unit' => $billable->getUnit()
                    );
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' Create invoice position ' . print_r($pos, 1), Zend_Log::DEBUG);
                    }
                    
                    $invoicePositions->addRecord(new Sales_Model_InvoicePosition($pos));
                }
            }
        }
        
        return $invoicePositions;
    }
    
    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        if ($_record->is_auto) {
            $this->_checkCleared($_record);
        }
        
        if (! empty($_record->number)) {
            if (! Tinebase_Core::getUser()->hasRight('Sales', Sales_Acl_Rights::SET_INVOICE_NUMBER)) {
                throw new Tinebase_Exception_AccessDenied('You have no right to set the invoice number!');
            }
            
            $this->_setNextNumber($_record);
        }
    }
    
    /**
     * checks cleared state and sets the date to the current date, also sets all billables billed
     * 
     * @param Tinebase_Record_Interface $record
     * @param Tinebase_Record_Interface $oldRecord
     */
    protected function _checkCleared(Tinebase_Record_Interface &$record, Tinebase_Record_Interface $oldRecord = NULL)
    {
        $foundCustomer = NULL;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Invoice: ' . print_r($record->toArray(), true));
        
        if (is_array($record->relations)) {
            foreach ($record->relations as $relation) {
                if ($relation['related_model'] == 'Sales_Model_Customer') {
                    $foundCustomer = $relation['related_record'];
                    break;
                }
                if ($relation['related_model'] == 'Sales_Model_Contract') {
                    $foundContractRecord = Sales_Controller_Contract::getInstance()->get($relation['related_record']['id']);
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                        . ' Contract: ' . print_r($foundContractRecord->toArray(), true));
                    foreach ($foundContractRecord->relations as $relation) {
                        if ($relation['related_model'] == 'Sales_Model_Customer') {
                            $foundCustomer = $relation['related_record'];
                            break;
                        }
                    }
                }
            }
        }
        
        if (empty($record->address_id) && $foundCustomer) {
            $json = new Sales_Frontend_Json();
            $resolved = $json->getCustomer($foundCustomer->getId());
            if (! empty($resolved['billing'])) {
                $record->address_id = $resolved['billing'][0]['id'];
            } else {
                throw new Tinebase_Exception_Data('You have to set a billing address!');
            }
        }
        
        // if the record hasn't been cleared before, clear billables
        if ($record->cleared == 'CLEARED' && (! $oldRecord || $oldRecord->cleared != 'CLEARED')) {
            
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' Clearing Invoice ' . print_r($record->toArray(), 1), Zend_Log::INFO);
            }
            
            if (! $record->date) {
                $record->date = new Tinebase_DateTime();
            }
            
            $this->_setNextNumber($record, isset($oldRecord));
            
            $address = Sales_Controller_Address::getInstance()->get(is_string($record->address_id) ? $record->address_id : $record->address_id.id);
            
            $string = $foundCustomer['name'] . PHP_EOL;
            $string .= $address->prefix1 ? $address->prefix1 . "\n" : '';
            $string .= $address->prefix2 ? $address->prefix2 . "\n" : '';
            $string .= $address->pobox   ? $address->pobox   . "\n" : '';
            
            $string .= $address->street  ? $address->street   . "\n" : '';
            
            $poloc  = $address->postalcode ? $address->postalcode . " " : '';
            $poloc .= $address->locality ? $address->locality : '';
            
            if (! empty($poloc)) {
                $string .= $poloc . PHP_EOL;
            }
            
            $string .= $address->countryname ? $address->countryname : '';
            
            $record->fixed_address = $string;
            
            // clear all billables
            if (! empty($record->relations)) {
                foreach($record->relations as $relation) {
                    if (in_array('Sales_Model_Accountable_Interface', class_implements($relation['related_model']))) {
                        
                        if (is_array($relation['related_record'])) {
                            $rr = new $relation['related_model']($relation['related_record']);
                        } else {
                            $rr = $relation['related_record'];
                        }

                        $rr->clearBillables($record);
                        
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                            Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' Clearing billables ' . print_r($rr->toArray(), 1), Zend_Log::INFO);
                        }
                    }
                }
            }
        }
    }
    
    /**
     * inspects delete action
     *
     * @param array $_ids
     * @return array of ids to actually delete
     */
    protected function _inspectDelete(array $_ids)
    {
        $records = $this->_backend->getMultiple($_ids);
        $records->setTimezone(Tinebase_Core::getUserTimezone());
        
        $invoicePositionController = Sales_Controller_InvoicePosition::getInstance();
        $contractController = Sales_Controller_Contract::getInstance();
        
        foreach ($records as $record) {
            if (! $record->is_auto) {
                continue;
            }
            
            if ($record->cleared == 'CLEARED') {
                // cleared invoices must not be deleted
                throw new Sales_Exception_InvoiceAlreadyClearedDelete();
                
            } else {
                // try to find a invoice after this one
                
                // there should be a contract
                $contractRelation = Tinebase_Relations::getInstance()->getRelations(
                    'Sales_Model_Invoice',
                    'Sql', $record->getId(),
                    NULL,
                    array(),
                    TRUE,
                    array('Sales_Model_Contract')
                )->getFirstRecord();
                
                if ($contractRelation) {
                    $contract = $contractRelation->related_record;
                    $contract->setTimezone(Tinebase_Core::getUserTimezone());
                    
                    // get all invoices related to this contract. throw exception if a follwing invoice has been found
                    $invoiceRelations = Tinebase_Relations::getInstance()->getRelations('Sales_Model_Contract', 'Sql', $contract->getId(), NULL, array(), TRUE, array('Sales_Model_Invoice'));
                    foreach($invoiceRelations as $invoiceRelation) {
                        $invoiceRelation->related_record->setTimezone(Tinebase_Core::getUserTimezone());
                        if ($record->getId() !== $invoiceRelation->related_record->getId() && $record->creation_time < $invoiceRelation->related_record->creation_time) {
                            throw new Sales_Exception_DeletePreviousInvoice();
                        }
                    }
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . ' Could not find contract relation -> skip contract handling');
                    $contract = null;
                }
                
                // remove invoice_id from billables
                $filter = new Sales_Model_InvoicePositionFilter(array());
                $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $record->getId())));
                $invoicePositions = $invoicePositionController->search($filter);
                
                $allModels = array_unique($invoicePositions->model);

                foreach($allModels as $model) {
                    
                    if ($model == 'Sales_Model_ProductAggregate') {
                        continue;
                    }
                    
                    $filteredInvoicePositions = $invoicePositions->filter('model', $model);
                    
                    $billableControllerName = $model::getBillableControllerName();
                    $billableFilterName     = $model::getBillableFilterName();
                    
                    $filterInstance = new $billableFilterName(array());
                    $filterInstance->addFilter(new Tinebase_Model_Filter_Text(
                        array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $record->getId())
                    ));
                    
                    $billableControllerName::getInstance()->updateMultiple($filterInstance, array('invoice_id' => NULL));
                    
                    // set invoice ids of the timeaccounts
                    if ($model == 'Timetracker_Model_Timeaccount') {
                        $filterInstance = new Timetracker_Model_TimeaccountFilter(array());
                        $filterInstance->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $record->getId())));
                        
                        Timetracker_Controller_Timeaccount::getInstance()->updateMultiple($filterInstance, array('invoice_id' => NULL));
                    }
                }
                
                // delete invoice positions
                $invoicePositionController->delete($invoicePositions->getId());
                
                // set last_autobill a period back
                if ($contract) {
                    // check product aggregates
                    $filter = new Sales_Model_ProductAggregateFilter(array());
                    $filter->addFilter(new Tinebase_Model_Filter_Text(
                        array('field' => 'contract_id', 'operator' => 'equals', 'value' => $contract->getId())
                    ));
                    
                    $paController = Sales_Controller_ProductAggregate::getInstance();
                    $productAggregates = $paController->search($filter);
                    $productAggregates->setTimezone(Tinebase_Core::getUserTimezone());
                    
                    foreach($productAggregates as $productAggregate) {
                        if ($productAggregate->last_autobill) {
                            $lab = clone $productAggregate->last_autobill;
                            $add = 0 - (int) $productAggregate->interval;
                            $productAggregate->last_autobill = $lab->addMonth($add);
                            $productAggregate->last_autobill->setTime(0,0,0);
                            
                            // last_autobill may not be before aggregate starts (may run into this case if interval has been resized)
                            if (! $productAggregate->start_date || $productAggregate->last_autobill < $productAggregate->start_date) {
                                $productAggregate->last_autobill = NULL;
                            }
                        }
                        
                        $productAggregate->setTimezone('UTC');
                        $paController->update($productAggregate);
                    }
                }
            }
        }
        
        return $_ids;
    }

    /**
     * inspect creation of one record (after create)
     *
     * @param   Tinebase_Record_Interface $_createdRecord
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     *
     * @todo $_record->contracts should be a Tinebase_Record_RecordSet
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        $config = $_record::getConfiguration()->recordsFields;
        foreach (array_keys($config) as $property) {
            $this->_createDependentRecords($_createdRecord, $_record, $property, $config[$property]['config']);
        }
    }
    
    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        if ($_record->number != $_oldRecord->number) {
            if (! Tinebase_Core::getUser()->hasRight('Sales', Sales_Acl_Rights::SET_INVOICE_NUMBER)) {
                throw new Tinebase_Exception_AccessDenied('You have no right to set the invoice number!');
            }
            $this->_setNextNumber($_record);
        }
        
        if (! $_record->is_auto) {
            return;
        }
        
        if ($_oldRecord->cleared == 'CLEARED') {
            $diff = $_record->diff($_oldRecord);
            $diff = $diff['diff'];
            
            $allowChange = array('relations', 'notes', 'tags', 'attachments', 'description', 'created_by', 'creation_time',
                'last_modified_by', 'last_modified_time', 'is_deleted', 'deleted_by', 'deleted_time', 'date', 'start_date', 'end_date', 'seq'
            );
            
            foreach($allowChange as $field) {
                unset($diff[$field]);
            }
            
            if (! empty($diff)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Differences found: ' . print_r($diff, 1));
                }
                throw new Sales_Exception_InvoiceAlreadyClearedEdit();
            }
        }
        $this->_checkCleared($_record, $_oldRecord);
        
        $config = $_record::getConfiguration()->recordsFields;
        
        foreach (array_keys($config) as $p) {
            $this->_updateDependentRecords($_record, $_oldRecord, $p, $config[$p]['config']);
        }
    }
    
    /**
     * check if user has the right to manage invoices
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
                if (! Tinebase_Core::getUser()->hasRight('Sales', Sales_Acl_Rights::MANAGE_INVOICES)) {
                    throw new Tinebase_Exception_AccessDenied("You don't have the right to manage invoices!");
                }
                break;
            default;
            break;
        }
    }
}
