<?php
/**
 * customer controller for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * the date of the month that needs to be billed next for the current contract
     * 
     * @var Tinebase_DateTime
     */
    protected $_currentMonthToBill = NULL;
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
     * holds the records of all created invoices
     *
     * @var array
     */
    protected $_autoInvoiceIterationDetailResults = NULL;

    /**
     * holds the recreation invoice mapping
     *
     * @var array
     */
    protected $_autoInvoiceRecreationResults = NULL;

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
    private function __construct()
    {
        $this->_applicationName = 'Sales';
        $this->_backend = new Sales_Backend_Invoice();
        $this->_modelName = 'Sales_Model_Invoice';
        $this->_doContainerACLChecks = FALSE;
        $this->_cachedProducts = new Tinebase_Record_RecordSet('Sales_Model_Product');
        // TODO this should be done automatically if model has customfields (hasCustomFields)
        $this->_resolveCustomFields = true;
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
     * @param boolean $merge
     */
    public function processAutoInvoiceIteration($contracts, $currentDate, $merge)
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
                $this->_createAutoInvoicesForContract($contract, clone $currentDate, $merge);
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
            'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
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
     * @param Sales_Model_Invoice $invoice
     *
     * @return NULL|Sales_Model_Customer
     */
    protected function _getCustomerFromInvoiceRelations(Sales_Model_Invoice $invoice) {
       if (null === $invoice->relations) {
           return null;
       }

       $customers = $invoice->relations->filter('type', 'CUSTOMER');
       
       return count($customers) > 0 ? $customers->getFirstRecord()->related_record : null;
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
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                $failure = 'Could not create auto invoice for contract "' . $contract->number . '", because no billing address could be found!';
                $this->_autoInvoiceIterationFailures[] = $failure;
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $failure);
            }
            
            $this->_currentBillingContract = NULL;
        }
        
        if (! $this->_currentBillingCostCenter) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                $failure = 'Could not create auto invoice for contract "' . $contract->number . '", because no costcenter could be found!';
                $this->_autoInvoiceIterationFailures[] = $failure;
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $failure);
            }
            
            $this->_currentBillingContract = NULL;
        }
        
        if (! $this->_currentBillingCustomer) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                $failure = 'Could not create auto invoice for contract "' . $contract->number . '", because no customer could be found!';
                $this->_autoInvoiceIterationFailures[] = $failure;
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $failure);
            }
            
            $this->_currentBillingContract = NULL;
        }

        if(! $contract->start_date) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) {
                $failure = 'Could not create auto invoice for contract "' . $contract->number . '", because no start date is set!';
                $this->_autoInvoiceIterationFailures[] = $failure;
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $failure);
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
        $billedRelations = array();
        $simpleProductsToBill = array();
        $modelsToSkip = array();
        // this holds all relations for the invoice
        $relations            = array();
        $billableAccountables = array();

        
        // iterate product aggregates to get the billing definition for the models
        foreach ($productAggregates as $productAggregate) {
            // is null, if this is the first time to bill the product aggregate
            $lastBilled = $productAggregate->last_autobill == NULL ? NULL : clone $productAggregate->last_autobill;
            $productEnded = false;
            $endDate = NULL;
            
            if (NULL != $productAggregate->end_date)
                $endDate = clone $productAggregate->end_date;
            if ($this->_currentBillingContract->end_date != NULL && (NULL === $endDate || $endDate->isLater($this->_currentBillingContract->end_date)))
                $endDate = clone $this->_currentBillingContract->end_date;
            
            // if the product has been billed already, add the interval
            if ($lastBilled) {
                $nextBill = $lastBilled;
                $nextBill->setDate($nextBill->format('Y'), $nextBill->format('m'), 1);
                $nextBill->addMonth($productAggregate->interval);
                if (NULL !== $endDate && $endDate->isEarlier($nextBill)) {
                    if ($productAggregate->billing_point == 'end') {
                        if ($productAggregate->last_autobill->isEarlier($endDate)) {
                            // ok, fix nextBill to be close to endDate
                            $nextBill = $endDate;
                            $nextBill->setDate($nextBill->format('Y'), $nextBill->format('m'), 1);
                            $nextBill->addMonth(1);
                        } else {
                            // not ok, ignore
                            $productEnded = true;
                        }
                    } else {
                        // not ok, ignore
                        $productEnded = true;
                    }
                }
            } else {
                // it hasn't been billed already
                if (NULL != $productAggregate->start_date && $productAggregate->start_date->isLaterOrEquals($this->_currentBillingContract->start_date)) {
                    $nextBill = clone $productAggregate->start_date;
                } else {
                    $nextBill = clone $this->_currentBillingContract->start_date;
                }
                $nextBill->setDate($nextBill->format('Y'), $nextBill->format('m'), 1);
                
                // add interval, if the billing point is at the end of the interval
                if ($productAggregate->billing_point == 'end') {
                    $nextBill->addMonth($productAggregate->interval);
                    if (NULL !== $endDate && $endDate->isEarlier($nextBill))
                    {
                        // ok, fix nextBill to be close to endDate
                        $nextBill = $endDate;
                        $nextBill->setDate($nextBill->format('Y'), $nextBill->format('m'), 1);
                        $nextBill->addMonth(1);
                    }
                }
            }
            
            $nextBill->setTime(0,0,0);
            
            $product = $this->_cachedProducts->getById($productAggregate->product_id);
            if (! $product) {
                $product = Sales_Controller_Product::getInstance()->get($productAggregate->product_id);
                $this->_cachedProducts->addRecord($product);
            }
            
            // find out if this model has to be billed or skipped
            if (! $productEnded && $this->_currentMonthToBill->isLaterOrEquals($nextBill)) {
                if (($product->accountable == 'Sales_Model_Product') || ($product->accountable == '')) {
                    $simpleProductsToBill[] = array('pa' => $productAggregate, 'ac' => $productAggregate);
                } else {

                    if ($productAggregate->json_attributes && isset($productAggregate->json_attributes['assignedAccountables']) &&
                        is_array($productAggregate->json_attributes['assignedAccountables']) && count($productAggregate->json_attributes['assignedAccountables'])) {

                        foreach ($productAggregate->json_attributes['assignedAccountables'] as $relation) {
                            $cRelation = $this->_currentBillingContract->relations
                                ->filter('related_id', $relation['id'])->find('related_model', $relation['model']);
                            if (null !== $cRelation) {
                                $billedRelations[$cRelation->getId()] = true;
                                $record = $cRelation->related_record;
                            } else {
                                $controller = Tinebase_Core::getApplicationInstance($relation['model']);
                                $record = $controller->get($relation['id']);
                            }

                            $relations[] = array_merge(array(
                                'related_model'  => $relation['model'],
                                'related_id'     => $relation['id'],
                                'related_record' => $record->toArray(),
                            ), $this->_getRelationDefaults());

                            $billableAccountables[] = array(
                                'ac' => $record,
                                'pa' => $productAggregate
                            );
                        }
                    } else {
                        $modelsToBill[$product->accountable] = $productAggregate;
                    }
                }
            } else {
                $modelsToSkip[] = $product->accountable;
            }
        }
        

        
        // iterate relations, look for accountables, prepare relations
        foreach ($this->_currentBillingContract->relations as $relation) {
            if (isset($billedRelations[$relation->id]) ||
                    !$relation->related_record instanceof Tinebase_Record_Interface) {
                continue;
            }
            // use productaggregate definition, if it has been found
            if (isset($modelsToBill[$relation->related_model]) && (! in_array($relation->related_model, $modelsToSkip))) {
                $relations[] = array_merge(array(
                    'related_model'  => $relation->related_model,
                    'related_id'     => $relation->related_id,
                    'related_record' => $relation->related_record->toArray(),
                ), $this->_getRelationDefaults());

                $billableAccountables[] = array(
                    'ac' => $relation->related_record,
                    'pa' => $modelsToBill[$relation->related_model]
                );
        
            } elseif ((! in_array($relation->related_model, $modelsToSkip)) && in_array('Sales_Model_Accountable_Interface', class_implements($relation->related_model))) {
                // no product aggregate definition has been found -> use default values
                $relations[] = array_merge(array(
                    'related_model'  => $relation->related_model,
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
            'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
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
            'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
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
    protected function _findInvoicePositionsAndInvoiceInterval(&$billableAccountables)
    {
        // put each position into
        $invoicePositions = new Tinebase_Record_RecordSet('Sales_Model_InvoicePosition');
        $earliestStartDate = $latestEndDate = NULL;
        
        foreach ($billableAccountables as &$ba) {
            $ba['partOfInvoice'] = false;
            if (! $ba['ac']->isBillable($this->_currentMonthToBill, $this->_currentBillingContract, $ba['pa'])) {
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
                    Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' isBillable failed for the accountable ' . $ba['ac']->getId() . ' of contract "' . $this->_currentBillingContract->number . '"');
                }
                continue;
            }
        
            $ba['ac']->loadBillables($this->_currentMonthToBill, $ba['pa']);
            $billables = $ba['ac']->getBillables($this->_currentMonthToBill, $ba['pa']);
        
            if (empty($billables)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' No efforts for the accountable ' . $ba['ac']->getId() . ' of contract "' . $this->_currentBillingContract->number . '" could be found.', Zend_Log::INFO);
                }
                continue;
            } elseif (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
                Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' billables: ' . count($billables, true) . ' found for the accountable ' . $ba['ac']->getId() . ' of contract "' . $this->_currentBillingContract->number . '"');
            }

            $ba['partOfInvoice'] = true;
        
            $invoicePositions = $invoicePositions->merge($this->_getInvoicePositionsFromBillables($billables, $ba['ac']));
        
            list($startDate, $endDate) = $ba['ac']->getInterval();
        
            if (! $latestEndDate) {
                $latestEndDate = $endDate;
            } elseif ($endDate > $latestEndDate) {
                $latestEndDate = $endDate;
            }
            if (! $earliestStartDate) {
                $earliestStartDate = $startDate;
            } elseif ($startDate < $earliestStartDate) {
                $earliestStartDate = $startDate;
            }
        }
        
        return array($invoicePositions, $earliestStartDate, $latestEndDate);
    }

    public function checkForContractOrInvoiceUpdates(Sales_Model_Contract $contract = null)
    {
        $contractController = Sales_Controller_Contract::getInstance();

        //get ids of invoices of which the contract was changed
        $ids = $this->getInvoicesWithChangedContract((null!==$contract?$contract->getId():null));
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' found ' . count($ids) . ' invoices with a contract change after creation time');
        }
        $excludeIds = array();
        $contracts = array();
        $result = array();
        foreach ($ids as $row)
        {
            $excludeIds[$row[0]] = true;
            if (!isset($contracts[$row[1]])) $contracts[$row[1]] = array($row[0]);
            else $contracts[$row[1]][] = $row[0];
        }
        foreach($contracts as $contractId => $ids)
        {
            $tmpContract = $contractController->get($contractId);
            if (!$tmpContract) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' could not get contract with id: ' . $contractId);
                continue;
            }
            try {
                $this->checkForRecreation($ids, $tmpContract);
            } catch (Exception $e) {
                $failure = 'Could not create auto invoice for contract "' . $contract->title . '" Exception: ' . $e->getCode() . ' has been thrown: "' . $e->getMessage() . '".';
                $this->_autoInvoiceIterationFailures[] = $failure;
                Tinebase_Exception::log($e, FALSE);
                continue;
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' checkForRecreation result: ' . print_r($this->_autoInvoiceIterationResults, 1));
            }
            $result = array_merge($result, $this->_autoInvoiceIterationResults);
        }


        //get ids of invoices that are not billed and which are not part of the above list
        $tmp = array(
            array('field' => 'is_auto', 'operator' => 'equals', 'value' => TRUE),
            array('field' => 'cleared', 'operator' => 'not', 'value' => 'CLEARED'),
        );
        if ($contract && $contract->last_modified_time) {
            $tmp[] = array('field' => 'creation_time', 'operator' => 'after', 'value' => $contract->last_modified_time);
        }
        $f = new Sales_Model_InvoiceFilter($tmp, 'AND');

        if ($contract) {
            $subf = new Tinebase_Model_Filter_ExplicitRelatedRecord(array('field' => 'contract', 'operator' => 'AND', 'value' => array(array(
                'field' =>  ':id', 'operator' => 'equals', 'value' => $contract->getId()
            )), 'options' => array(
                'controller'        => 'Sales_Controller_Contract',
                'filtergroup'       => 'Sales_Model_ContractFilter',
                'own_filtergroup'   => 'Sales_Model_InvoiceFilter',
                'own_controller'    => 'Sales_Controller_Invoice',
                'related_model'     => 'Sales_Model_Contract',
            )));
            $f->addFilter($subf);
        }

        // ASC is important, we want to update the oldest invoice first! otherwise a newer invoice might take away data from an older one! Though not severely bad, we want to maintain order, do we?
        $p = new Tinebase_Model_Pagination(array('sort' => 'creation_time', 'dir' => 'ASC'));

        $invoices = $this->search($f, $p, /* $_getRelations = */ false, /* only ids */ true);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' found ' . count($invoices) . ' invoices which are not yet cleared');
        }

        foreach($invoices as $id)
        {
            if (!isset($excludeIds[$id])) {
                try {
                    $result = array_merge($result, $this->checkForUpdate($id));
                } catch (Exception $e) {
                    $contractTitle = $contract ? $contract->title : 'unknown';
                    $failure = 'Could not create auto invoice for contract "' . $contractTitle
                        . '" Exception: ' . $e->getCode() . ' has been thrown: "' . $e->getMessage() . '".';
                    $this->_autoInvoiceIterationFailures[] = $failure;
                    Tinebase_Exception::log($e, FALSE);
                    continue;
                }
            }
        }

        return $result;
    }

    public function getInvoicesWithChangedContract($contractId = NULL)
    {
        return $this->_backend->getInvoicesWithChangedContract($contractId);
    }

    public function checkForUpdate($id)
    {
        $invoice = $this->get($id);
        $result = array();
        if (!$invoice) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' can not ::get invoice with id: ' . $id);
            return $result;
        }

        $this->_currentBillingContract = NULL;
        foreach($invoice->relations as $relation) {
            if ('Sales_Model_Contract' === $relation->related_model) {
                $this->_currentBillingContract = Sales_Controller_Contract::getInstance()->get($relation->related_id);
                break;
            }
        }
        if (NULL === $this->_currentBillingContract) {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' can not find contract for invoice with id: ' . $id);
            return $result;
        }
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' found contract ' . $this->_currentBillingContract->getId() . ' for: ' . $id);
        }

        $invoice->setTimezone(Tinebase_Core::getUserTimezone());
        $this->_currentBillingContract->setTimezone(Tinebase_Core::getUserTimezone());
        // date seems not to have a tz, so after the clone, the tz is UTC!! we need to reset it
        $this->_currentMonthToBill = clone $invoice->date;
        $this->_currentMonthToBill->setTimezone(Tinebase_Core::getUserTimezone());


        //find billableAccountables that need to be checked for update
        $productAggregates = $this->_findProductAggregates();
        $billableAccountables = array();
        $modelsToBill = array();

        foreach ($productAggregates as $productAggregate) {
            $product = $this->_cachedProducts->getById($productAggregate->product_id);
            if (! $product) {
                $product = Sales_Controller_Product::getInstance()->get($productAggregate->product_id);
                $this->_cachedProducts->addRecord($product);
            }

            if (($product->accountable != 'Sales_Model_Product') && ($product->accountable != '')) {
                $modelsToBill[$product->accountable] = $productAggregate;
            }
        }

        // iterate relations, look for accountables
        foreach ($this->_currentBillingContract->relations as $relation) {
            if (empty($relation->related_record)) {
                continue;
            }
            // use productaggregate definition, if it has been found
            if (isset($modelsToBill[$relation->related_model])) {
                $billableAccountables[] = array(
                    'ac' => $relation->related_record,
                    'pa' => $modelsToBill[$relation->related_model]
                );

            } elseif (in_array('Sales_Model_Accountable_Interface', class_implements($relation->related_model))) {
                // no product aggregate definition has been found -> use default values
                $billableAccountables[] = array(
                    'ac' => $relation->related_record,
                    'pa' => $relation->related_record->getDefaultProductAggregate($this->_currentBillingContract)
                );
            }
        }


        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' found ' . count($billableAccountables) . ' accountables that need to be checked for: ' . $id);
        }


        // check if an accountable wants the invoice to be recreated
        foreach($billableAccountables as $ba) {
            if ($ba['ac']->needsInvoiceRecreation($this->_currentMonthToBill, $ba['pa'], $invoice, $this->_currentBillingContract)) {
                $this->checkForRecreation(array($id), $this->_currentBillingContract);
                return $this->_autoInvoiceIterationResults;
            }
        }

        // this function should not return positions
        // if it does, the positions will only contain what is not contained in the current existing invoice
        // we cant just replace the positions, we have to join them
        list($invoicePositions, $earliestStartDate, $latestEndDate) = $this->_findInvoicePositionsAndInvoiceInterval($billableAccountables);

        if ($invoicePositions->count() > 0 ) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' found ' . $invoicePositions->count() . ' updates for: ' . $id);
            }
            if ($invoice->start_date->isLater($earliestStartDate)) {
                $invoice->start_date = $earliestStartDate;
            }
            if ($invoice->end_date->isEarlier($latestEndDate)) {
                $invoice->end_date = $latestEndDate;
            }

            // get the existing invoice positions
            $ipc = Sales_Controller_InvoicePosition::getInstance();
            $f = new Sales_Model_InvoicePositionFilter(array(
                array('field' => 'invoice_id', 'operator' => 'AND', 'value' => array(
                    array('field' => 'id', 'operator' => 'equals', 'value' => $invoice->getId()),
                )),
            ));
            $positions = $ipc->search($f);
            $positions->setTimezone(Tinebase_Core::getUserTimezone());

            $relations = null;

            foreach ($invoicePositions as $position)
            {
                $found = false;
                //find existing position in invoice to merge into
                foreach($positions as $oldPosition)
                {
                    if ($oldPosition->accountable_id == $position->accountable_id && $oldPosition->month == $position->month)
                    {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' updating invoice position: ' . $oldPosition->id . ' with model: ' . $oldPosition->model . ' and accountable_id: ' . $oldPosition->accountable_id . ' in month: ' . $oldPosition->month . ' for invoice: ' . $id);
                        }
                        //update the $invoice->price_net, price_gross too?!?
                        $oldPosition->quantity += $position->quantity;
                        $ipc->update($oldPosition);
                        $found = true;
                        break;
                    }
                }
                // add a new invoice position
                if (!$found) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' adding invoice position with model: ' . $position->model . ' and accountable_id: ' . $position->accountable_id . ' in month: ' . $position->month . ' for invoice: ' . $id);
                    }
                    $position->invoice_id = $invoice->getId();
                    $ipc->create($position);

                    if (null === $relations) {
                        $relations = $invoice->relations->toArray();
                    }
                    foreach ($relations as $relation) {
                        if ($relation['related_id'] === $position->accountable_id) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $relations[] = array_merge([
                            'related_model' => $position->model,
                            'related_id'    => $position->accountable_id,
                        ], $this->_getRelationDefaults());
                    }
                }
            }

            if (null !== $relations) {
                $invoice->relations = $relations;
            }

            $this->update($invoice);

            // mark the invoiced accountables as accounted / invoiced
            foreach($billableAccountables as $ba) {
                if ($ba['partOfInvoice']) {
                    $ba['ac']->conjunctInvoiceWithBillables($invoice);
                }
            }

        } elseif (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' no updates found for: ' . $id);
        }

        return $result;
    }

    public function checkForRecreation(array $ids, $contract)
    {
        //we should delete from recent to old
        //we should create from old to recent
        //then compare correctly...
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            $forTrace = $contract->id . ' ' . print_r($ids, true);
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' for: ' . $forTrace);
        }

        $this->_autoInvoiceIterationDetailResults = array();
        $this->_autoInvoiceIterationResults = array();
        $this->_autoInvoiceRecreationResults = array();
        $oldInvoices = array();
        $oldPositions = array();
        $somethingChanged = false;
        $failed = false;

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        $invoicePositionController = Sales_Controller_InvoicePosition::getInstance();

        foreach ($ids as $id) {
            $invoice = $this->get($id);
            if (!$invoice) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' can not ::get invoice with id: ' . $id);
                continue;
            }

            $invoice->setTimezone(Tinebase_Core::getUserTimezone());
            $oldInvoices[] = $invoice;
            $filter = new Sales_Model_InvoicePositionFilter(array());
            $filter->addFilter(new Tinebase_Model_Filter_Text(
                array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $invoice->getId())
            ));
            $oldPositions[$invoice->getId()] = $invoicePositionController->search($filter);

            try {
                $this->delete(array($invoice));
            } catch (Sales_Exception_DeletePreviousInvoice $sedpi) {
                $failed = true;
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' could not delete invoice with id: ' . $id);
                break;
            }
            //is $invoice still valid?!?!?
        }

        if (true === $failed) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            return;
        }

        // reload relations as they may have changed as we deleted the invoices above
        // TODO: could be made more efficient as we just need to reload releationsa actually and not the whole contract.
        $contract = Sales_Controller_Contract::getInstance()->get($contract->getId());

        $this->_currentBillingContract = $contract;
        $this->_currentBillingContract->setTimezone(Tinebase_Core::getUserTimezone());

        // the newest invoice!
        $date = clone $oldInvoices[0]->date;
        // date seems not to have a tz, so after the clone, the tz is UTC!! we need to reset it
        $date->setTimezone(Tinebase_Core::getUserTimezone());


        $this->_createAutoInvoicesForContract($this->_currentBillingContract, $date);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' deleted ' . count($oldInvoices) . ' and recreated ' . count($this->_autoInvoiceIterationDetailResults) . ' invoices for: ' . $forTrace);
        }

        if (count($oldInvoices) !== count($this->_autoInvoiceIterationDetailResults)) {
            // something changed for sure. fine, commit => done
            $somethingChanged = true;
        } else {

            // WE NEED TO DIFF POSITIONS TOO! diff on invoice does not do a diff on the positions!
            // if diff on invoice is negative, then check the positions

            foreach ($this->_autoInvoiceIterationDetailResults as $newInvoice) {
                $diff = null;
                foreach ($oldInvoices as $oldInvoice) {
                    if ($newInvoice->date->equals($oldInvoice->date)) {
                        $diff = $newInvoice->diff($oldInvoice, array('description', 'id', 'relations', 'contract', 'customer', 'created_by', 'creation_time', 'last_modified_by', 'last_modified_time'));
                        //if nothing changed, check the invoice positions
                        if ($diff->isEmpty()) {
                            $filter = new Sales_Model_InvoicePositionFilter(array());
                            $filter->addFilter(new Tinebase_Model_Filter_Text(
                                array('field' => 'invoice_id', 'operator' => 'equals', 'value' => $newInvoice->getId())
                            ));
                            $newPositions = $invoicePositionController->search($filter);
                            $i = 0;
                            foreach($oldPositions[$invoice->getId()] as $oldPosition)
                            {
                                if ($i + 1 > $newPositions->count()) {
                                    $diff = null;
                                    break;
                                }
                                $newPosition = $newPositions->getByIndex($i++);
                                $diff = $newPosition->diff($oldPosition, array('id', 'invoice_id'));
                                if (!$diff->isEmpty()) {
                                    break;
                                }
                            }

                        }
                        break;
                    }
                }
                // null === $diff means that we could not match the new Invoice to the old one, though the count of invoices seems not to have changed
                if (null === $diff || !$diff->isEmpty()) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
                        Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' something changed with $diff = ' . (null===$diff?'null':print_r($diff->toArray(),true)) . ' for: ' . $forTrace);
                    }
                    $somethingChanged = true;
                    break;
                }
            }
        }

        if (true === $somethingChanged) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' something changed for: ' . $forTrace);
            }
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

            //create mapping of old to new invoices
            foreach ($this->_autoInvoiceIterationDetailResults as $newInvoice) {
                foreach ($oldInvoices as $oldInvoice) {
                    if ($newInvoice->date->equals($oldInvoice->date)) {
                        $this->_autoInvoiceRecreationResults[$oldInvoice->getId()] = $newInvoice->getId();
                    }
                }
            }

        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' nothing changed for: ' . $forTrace);
            }
            Tinebase_TransactionManager::getInstance()->rollBack();
        }
    }

    /**
     * create auto invoices for one contract
     * 
     * @param Sales_Model_Contract $contract
     * @param Tinebase_DateTime $currentDate
     * @param boolean $merge
     */
    protected function _createAutoInvoicesForContract(Sales_Model_Contract $contract, Tinebase_DateTime $currentDate, $merge = false)
    {
        // set this current billing date (user timezone)
        $this->_currentBillingDate = clone $currentDate;
        $this->_currentBillingDate->setDate($this->_currentBillingDate->format('Y'), $this->_currentBillingDate->format('m'), 1);
        $this->_currentBillingDate->setTime(0,0,0);
        
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
        
        // find month that needs to be billed next (note: _currentMonthToBill is the 01-01 00:00:00 of the next month, its the border, like last_autobill)
        $this->_currentMonthToBill = null;
        foreach ($productAggregates as $productAggregate) {
            if ( null != $productAggregate->last_autobill ) {
                $tmp = clone $productAggregate->last_autobill;
                $tmp->setDate($tmp->format('Y'), $tmp->format('m'), 1);
                $tmp->setTime(0,0,0);
                if ( null == $this->_currentMonthToBill || $tmp->isLater($this->_currentMonthToBill) ) {
                    $this->_currentMonthToBill = $tmp;
                }
            }
        }
        
        // this contract has no productAggregates, maybe just time accounts? use last invoice to find already billed month
        if ( null == $this->_currentMonthToBill ) {
            // find newest invoice of contract (probably can be done more efficient!)
            $invoiceRelations = Tinebase_Relations::getInstance()->getRelations('Sales_Model_Contract', 'Sql', $contract->getId(), NULL, array(), TRUE, array('Sales_Model_Invoice'));
            // do not modify $newestInvoiceTime!!!! it does NOT get cloned!
            $newestInvoiceTime = null;
            $newestInvoice = null;
            foreach($invoiceRelations as $invoiceRelation) {
                $invoiceRelation->related_record->setTimezone(Tinebase_Core::getUserTimezone());
                if ( null == $newestInvoiceTime || $invoiceRelation->related_record->creation_time->isLater($newestInvoiceTime) ) {
                    $newestInvoiceTime = $invoiceRelation->related_record->creation_time;
                    $newestInvoice = $invoiceRelation->related_record;
                }
            }
            
            if ( null != $newestInvoice ) {
                // we can only take the end_date because there are no product aggregates (that have a last_autobill set) in this contract, otherwise it might be one interval ahead!
                $this->_currentMonthToBill = clone $newestInvoice->end_date;
                $this->_currentMonthToBill->addDay(4);
                $this->_currentMonthToBill->subMonth(1);
                //$this->_currentMonthToBill->setTimezone(Tinebase_Core::getUserTimezone());
            }
        }
        
        $_addMonth = true;
        if ( null == $this->_currentMonthToBill ) {
            $this->_currentMonthToBill = clone $contract->start_date;
            $_addMonth = false;
        }
        $this->_currentMonthToBill->setTimezone(Tinebase_Core::getUserTimezone());
        $this->_currentMonthToBill->setDate($this->_currentMonthToBill->format('Y'), $this->_currentMonthToBill->format('m'), 1);
        $this->_currentMonthToBill->setTime(0,0,0);
        if ($_addMonth) {
            $this->_currentMonthToBill->addMonth(1);
        }
        
        $doSleep = false;
        
        if ( ($merge || $contract->merge_invoices) && $this->_currentMonthToBill->isEarlier($this->_currentBillingDate) ) {
            $this->_currentMonthToBill = clone $this->_currentBillingDate;
        }
        
        while ( $this->_currentMonthToBill->isEarlierOrEquals($this->_currentBillingDate) ) {
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' $this->_currentMonthToBill: ' . $this->_currentMonthToBill
                    . ' $this->_currentBillingDate ' . $this->_currentBillingDate);
                foreach ($productAggregates as $productAggregate) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $productAggregate->id . ' ' . $productAggregate->last_autobill . ' ' . $productAggregate->interval);
                }
            }
            
            //required to have one sec difference in the invoice creation_time, can be optimized to look for milliseconds
            if ( $doSleep ) {
                sleep(1);
                $doSleep = false;
            }
            // prepare relations and find all billable accountables of the current contract
            list($relations, $billableAccountables) = $this->_prepareInvoiceRelationsAndFindBillableAccountables($productAggregates);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' count $billableAccountables: ' . count($billableAccountables));
                foreach ($billableAccountables as $ba) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' accountable: ' . get_class($ba['ac']) . ' id: ' . $ba['ac']->getId());
                }
            }
            
            // find invoice positions and the first start date and last end date of all billables
            list($invoicePositions, $earliestStartDate, $latestEndDate) = $this->_findInvoicePositionsAndInvoiceInterval($billableAccountables);
            
            /**** TODO ****/
            // if there are no positions, no more bills need to be created,
            // but the last_autobill info is set, if the current date is later
            if ($invoicePositions->count() > 0 ) {
                // clean up relations
                foreach ($billableAccountables as $ba) {
                    if (!$ba['partOfInvoice']) {
                        foreach ($relations as $key => $relation) {
                            if ($relation['related_id'] === $ba['ac']->getId()) {
                                unset($relations[$key]);
                                break;
                            }
                        }
                    }
                }
                // prepare invoice
                $invoice = new Sales_Model_Invoice(array(
                    'is_auto'       => TRUE,
                    'description'   => $this->_currentBillingContract->title . ' (' . $this->_currentMonthToBill->toString() . ')',
                    'type'          => 'INVOICE',
                    'address_id'    => $this->_currentBillingContract->billing_address_id,
                    'credit_term'   => $this->_currentBillingCustomer['credit_term'],
                    'customer_id'   => $this->_currentBillingCustomer['id'],
                    'costcenter_id' => $this->_currentBillingCostCenter->getId(),
                    'start_date'    => $earliestStartDate,
                    'end_date'      => $latestEndDate,
                    'positions'     => $invoicePositions->toArray(),
                    'date'          => clone $this->_currentMonthToBill,
                    'sales_tax'     => 19
                ));
                
                $invoice->relations = $relations;
                
                $invoice->setTimezone('UTC', TRUE);
        
                // create invoice
                $invoice = $this->create($invoice);
                $this->_autoInvoiceIterationResults[] = $invoice->getId();
                $this->_autoInvoiceIterationDetailResults[] = $invoice;
                
                $paToUpdate = array();
                
                // conjunct billables with invoice, find out which productaggregates to update
                foreach($billableAccountables as $ba) {
                    if ($ba['partOfInvoice']) {
                        $ba['ac']->conjunctInvoiceWithBillables($invoice);
                        if ($ba['pa']->getId()) {
                            $paToUpdate[$ba['pa']->getId()] = $ba['pa'];
                        }
                    }
                }

                /** @var Sales_Model_ProductAggregate $productAggregate */
                foreach($paToUpdate as $paId => $productAggregate) {
                    $firstBill = (! $productAggregate->last_autobill);
                    
                    $lab = $productAggregate->last_autobill ? clone $productAggregate->last_autobill : ($productAggregate->start_date ? clone $productAggregate->start_date : clone $this->_currentBillingContract->start_date);
                    $lab->setTimezone(Tinebase_Core::getUserTimezone());
                    $lab->setDate($lab->format('Y'), $lab->format('m'), 1);
                    $lab->setTime(0,0,0);
                    
                    if (! $firstBill) {
                        $lab->addMonth($productAggregate->interval);
                    } else {
                        if ($productAggregate->billing_point == 'end') {
                            // if first bill, add interval on billing_point end
                            $lab->addMonth($productAggregate->interval);
                        }
                    }
                    
                    while ($this->_currentMonthToBill->isLater($lab)) {
                        $lab->addMonth($productAggregate->interval);
                    }
                    if ($lab->isLater($this->_currentMonthToBill)) {
                        $lab->subMonth($productAggregate->interval);
                    }
                    
                    $productAggregate->last_autobill = $lab;
                    $productAggregate->setTimezone('UTC');
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updating last_autobill of "' . $productAggregate->getId() . '": ' . $lab->__toString());
                    }
                    
                    Sales_Controller_ProductAggregate::getInstance()->update($productAggregate);

                    $productAggregate->runConvertToRecord();
                    $productAggregate->setTimezone(Tinebase_Core::getUserTimezone());
                }
                
                $doSleep = true;
            } elseif (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' $invoicePositions->count() == false');
            }
            
            $this->_currentMonthToBill->addMonth(1);
        }
    }
    
    /**
     * creates the auto invoices, gets called by cli
     * 
     * @param Tinebase_DateTime $currentDate
     * @param Sales_Model_Contract $contract
     * @param boolean $merge
     */
    public function createAutoInvoices(Tinebase_DateTime $currentDate, Sales_Model_Contract $contract = NULL, $merge = false)
    {
        $this->_autoInvoiceIterationResults  = array();
        $this->_autoInvoiceIterationDetailResults = array();
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
        
        $iterator->iterate($currentDate, $merge);

        unset($this->_autoInvoiceIterationDetailResults);

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
                    Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' Create invoice position ' . print_r($pos, 1) . ' for contract: ' . $this->_currentBillingContract->getId(), Zend_Log::DEBUG);
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
                    $this->_currentBillingContract = $contract;
                    $productAggregates = $this->_findProductAggregates();
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . ' Could not find contract relation -> skip contract handling');
                    $contract = null;
                    $productAggregates = array();
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
                        $filterInstance->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'status', 'operator' => 'equals', 'value' => Timetracker_Model_Timeaccount::STATUS_BILLED)));
                        $filterInstance->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'cleared_at', 'operator' => 'isnull', 'value' => '')));
                        
                        Timetracker_Controller_Timeaccount::getInstance()->updateMultiple($filterInstance, array('invoice_id' => NULL, 'status' => Timetracker_Model_Timeaccount::STATUS_TO_BILL));
                    }
                }
                
                // delete invoice positions
                $invoicePositionController->delete($invoicePositions->getId());
                
                // set last_autobill a period back
                if ($contract) {
                    //find the month of each productAggregate we have to set it back to
                    $undoProductAggregates = array();
                    $paController = Sales_Controller_ProductAggregate::getInstance();
                    
                    foreach($invoicePositions as $inPos)
                    {
                        if ($inPos->model != 'Sales_Model_ProductAggregate')
                            continue;
                        
                        //if we didnt find a month for the productAggreagte yet or if the month found is greater than the one we have at hands
                        if ( !isset($undoProductAggregates[$inPos->accountable_id]) || strcmp($undoProductAggregates[$inPos->accountable_id], $inPos->month) > 0 )
                        {
                            $undoProductAggregates[$inPos->accountable_id] = $inPos->month;
                        }
                    }
                    
                    foreach($productAggregates as $productAggregate) {
                        
                        if (!$productAggregate->last_autobill)
                            continue;
                        
                        if ( !isset($undoProductAggregates[$productAggregate->id]) ) {
                            $product = $this->_cachedProducts->getById($productAggregate->product_id);
                            if (! $product) {
                                $product = Sales_Controller_Product::getInstance()->get($productAggregate->product_id);
                                $this->_cachedProducts->addRecord($product);
                            }
                            if ($product->accountable == 'Sales_Model_Product' || ($record->date != null && $record->date->isLater($productAggregate->last_autobill))) {
                                continue;
                            }
                            
                            $productAggregate->last_autobill->subMonth($productAggregate->interval);
                        } else {
                            
                            $productAggregate->last_autobill = new Tinebase_DateTime($undoProductAggregates[$productAggregate->id] . '-01 00:00:00', Tinebase_Core::getUserTimezone());
                            if ($productAggregate->billing_point == 'begin') {
                                $productAggregate->last_autobill->subMonth($productAggregate->interval);
                            }
                            if ( $productAggregate->start_date && $productAggregate->last_autobill < $productAggregate->start_date) {
                                $tmp = clone $productAggregate->start_date;
                                $tmp->setTimezone(Tinebase_Core::getUserTimezone());
                                $tmp->setDate($tmp->format('Y'), $tmp->format('m'), 1);
                                $tmp->setTime(0,0,0);
                                if ($productAggregate->last_autobill < $tmp || ($productAggregate->billing_point == 'end' && $productAggregate->last_autobill == $tmp)) {
                                    $productAggregate->last_autobill = NULL;
                                }
                            }
                        }
                        $productAggregate->setTimezone('UTC');
                        $paController->update($productAggregate);
                        $productAggregate->setTimezone(Tinebase_Core::getUserTimezone());
                    }
                }
            }
        }
        
        return $_ids;
    }

    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record the update record
     * @param   Tinebase_Record_Interface $_oldRecord the current persistent record
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
    }

    /**
     * check if user has the right to manage invoices
     *
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_AreaLocked
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

        parent::_checkRight($_action);
    }

    /**
     * returns _autoInvoiceRecreationResults
     * @return array
     */
    public function getAutoInvoiceRecreationResults()
    {
        return $this->_autoInvoiceRecreationResults;
    }

    /**
     * @param Sales_Model_Invoice $invoice
     * @return array|Tinebase_Record_RecordSet
     */
    protected function _getTimeaccountPositionsForInvoice(Sales_Model_Invoice $invoice) {
        return Sales_Controller_InvoicePosition::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Sales_Model_InvoicePosition::class,
            [
                [
                    'field' => 'invoice_id',
                    'operator' => 'AND',
                    'value' => [
                        [
                            'field' => ':id',
                            'operator' => 'equals',
                            'value' => $invoice->getId()
                        ]
                    ]
                ],
                ['field' => 'model', 'operator' => 'equals', 'value' => Timetracker_Model_Timeaccount::class]
            ])
        );
    }
    
    /**
     * @param $invoiceId Invoice ID
     * @return bool|Sales_Model_Invoice|Tinebase_Record_Interface
     * @throws Tinebase_Exception
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws Exception
     */
    public function createTimesheetFor($invoiceId)
    {
        try {
            /* @var $invoice Sales_Model_Invoice */
            $invoice = $this->get($invoiceId);
        } catch (Tinebase_Exception_AccessDenied $e) {
            return false;
        }

        $customer = $this->_getCustomerFromInvoiceRelations($invoice);
        
        $timeaccountPositions = $this->_getTimeaccountPositionsForInvoice($invoice);

        // no timeaccounts, no fun
        if ($timeaccountPositions->count() === 0) {
            return false;
        }

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            Sales_Model_Invoice:: class,
            ['field' => 'id', 'operator' => 'equals', 'value' => $invoice->getId()]
        );

        $definition = Tinebase_ImportExportDefinition::getInstance()->search(new Tinebase_Model_ImportExportDefinitionFilter([
            'model' => Sales_Model_Invoice::class,
            'name' => 'invoice_timesheet_xlsx'
        ]))->getFirstRecord()->getId();


        $pdfFiles = [];
        $xlsxFiles = [];
        
        /* @var $timeaccountPositions Sales_Model_InvoicePosition[] */
        foreach ($timeaccountPositions as $timeaccountPosition) {
            $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->get($timeaccountPosition->accountable_id);
            $export = new Sales_Export_TimesheetTimeaccount($filter, null,
                [
                    'definitionId' => $definition,
                    'timeaccount' => $timeaccount,
                    'invoice' => $invoice
                ]
            );
            
            // @todo caching breaks it all, we should fix caching instead of busting it
            $export->registerTwigExtension(new Tinebase_Export_TwigExtensionCacheBust(
                Tinebase_Record_Abstract::generateUID()));


            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' Creating export ...');

            $export->generate();
            
            $xlsx = Tinebase_TempFile::getTempPath();
            $export->save($xlsx);

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
                __METHOD__ . '::' . __LINE__ . ' ... xlsx created: ' . $xlsx);
            
            $xlsxFiles[$timeaccount->getTitle()] = $xlsx;
            $pdfFiles[] = $export->convertToPdf($xlsx, ['ods']);
        }
        
        foreach ($xlsxFiles as $account => $file) {
            Tinebase_FileSystem_RecordAttachments::getInstance()->addRecordAttachment(
                $invoice,
                sprintf(
                    '%s_%s_%s_timesheet_%s.xlsx',
                    $customer->name_shorthand,
                    $account,
                    $invoice->number,
                    (new Tinebase_DateTime())->setTimezone(Tinebase_Core::getUserTimezone())->format('Y-m-d_H:i')
                ),
                fopen($file, 'rb')
            );
        }

        Tinebase_FileSystem_RecordAttachments::getInstance()->addRecordAttachment(
            $invoice,
            sprintf(
                '%s_%s_timesheet_%s.pdf',
                $customer->name_shorthand,
                $invoice->number,
                (new Tinebase_DateTime())->setTimezone(Tinebase_Core::getUserTimezone())->format('Y-m-d_H:i')
            ),
            fopen($this->_mergePdfFiles($pdfFiles), 'rb')
        );

        return $invoice;
    }


    /**
     * @param $files
     * @return string
     * @throws Tinebase_Exception
     */
    protected function _mergePdfFiles($files) {
        $mergedPdf = Tinebase_TempFile::getTempPath();
        file_put_contents($mergedPdf, Tinebase_Core::getPreviewService()->mergePdfFiles($files, true));

        return $mergedPdf;
    }
}
