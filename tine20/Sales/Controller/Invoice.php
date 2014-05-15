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
    /**
     * the number gets prefixed zeros until this amount of chars is reached
     * 
     * @var integer
     */
    protected $_numberZerofill = 6;
    
    /**
     * the prefix for the invoice
     * 
     * @var string
     */
    protected $_numberPrefix = 'R-';
    
    /**
     * 
     * @var Tinebase_Record_RecordSet
     */
    protected $_autoInvoiceIterationResults = NULL;
    
    /**
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

    public function processAutoInvoiceIteration($contracts, $currentDate)
    {
        $failures = array();
        $created = new Tinebase_Record_RecordSet('Sales_Model_Invoice');
        
        $relationDefaults = array(
            'own_model'              => 'Sales_Model_Invoice',
            'own_backend'            => Tasks_Backend_Factory::SQL,
            'own_id'                 => NULL,
            'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_backend'        => Tasks_Backend_Factory::SQL,
            'type'                   => 'INVOICE_ITEM'
        );
        
        $contractController          = Sales_Controller_Contract::getInstance();
        $productAggregateController  = Sales_Controller_ProductAggregate::getInstance();
        
        $dateBig = clone $currentDate;
        $dateBig->addSecond(2);
        
        $dateSmall = clone $currentDate;
        $dateSmall->subSecond(2);
        
        foreach($contracts as $contract) {
            
            $filter = new Sales_Model_ProductAggregateFilter(array());
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'contract_id', 'operator' => 'equals', 'value' => $contract->getId())));
            $products = $productAggregateController->search($filter);
        
        
            // if there aren't any products, and the interval of the contract is 0, don't handle contract
            if ($products->count() == 0 && $contract->interval == 0) {
                continue;
            }
        
            // contract has been terminated and last bill has been created already
            if ($contract->end_date && $contract->last_autobill > $contract->end_date) {
                continue;
            }
        
            $nextBill = $contractController->getNextBill($contract);
        
            if ($nextBill->isLater($dateBig)) {
                // don't handle, if contract don't have to be billed and there aren't any products
                if ($products->count() == 0) {
                    continue;
                } else {
                    $billIt = FALSE;
                    // otherwise iterate products
                    foreach($products as $product) {
                        // is null, if this is the first time to bill the contract
                        $lastBilled = ($product->last_autobill === NULL) ? NULL : clone $product->last_autobill;
        
                        // if the contract has been billed already, add the interval
                        if ($lastBilled) {
                            $nextBill = $lastBilled->addMonth($product->interval);
                        } else {
                            // it hasn't been billed already, so take the start_date of the contract as date
                            $nextBill = clone $contract->start_date;
                        }
        
                        // assure creating the last bill bill if a contract has bee terminated
                        if (($contract->end_date !== NULL) && $nextBill->isLater($contract->end_date)) {
                            $nextBill = clone $contract->end_date;
                        }
        
                        $nextBill->setTime(0,0,0);
                        // there is a product to bill, so stop to iterate
                        if ($nextBill->isLater($dateBig)) {
                            $billIt = TRUE;
                            break;
                        }
        
                    }
        
                    if (! $billIt) {
                        continue;
                    }
                }
            }
        
            $contract->products = $products->count() ? $products->toArray() : NULL;
        
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Processing contract ' . $contract->title);
            }
        
            // this holds all relations for the invoice
            $relations        = array();
            $invoicePositions = array();
        
            $customer = $costcenter = NULL;
        
            $addressId = $contract->billing_address_id;
            $volatileBilled = FALSE;
            $earliestStartDate = $latestEndDate = NULL;
        
            if (! $addressId) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    $failure = 'Could not create auto invoice for contract "' . $contract->title . '", because no billing address could be found!';
                    $this->_autoInvoiceIterationFailures[] = $failure;
                    Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' ' . $failure, Zend_Log::INFO);
                }
                continue;
            }
        
            $billableAccountables = array();
        
            // iterate relations, look for customer, cost center and accountables
            foreach ($contract->relations as $relation) {
        
                switch ($relation->type) {
                    case 'CUSTOMER':
                        $customer = $relation->related_record;
                        continue /* foreach */;
                    case 'LEAD_COST_CENTER':
                        $costcenter = $relation->related_record;
                        continue /* foreach */;
                }
        
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Checking relation ' . $relation->related_model);
        
                // find accountables
                if (in_array('Sales_Model_Accountable_Interface', class_implements($relation->related_record))) {
        
                    $billIt = FALSE;
        
                    // if the related record is volatile, it does not know when billed last
                    if ($relation->related_record->isVolatile() && $relation->related_record->isBillable($currentDate)) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Found volatile & billable accountable');
        
                        $referenceDate = $contract->last_autobill ? clone $contract->last_autobill : clone $contract->start_date;
        
                        $referenceDate->subSecond(10);
        
                        if ($contract->billing_point == 'end') {
                            $referenceDate->addMonth($contract->interval);
                        }
        
                        if ($referenceDate->isEarlier($currentDate)) {
                            $billIt = TRUE;
                            // this is true even if there are no efforts to bill
                            $volatileBilled = TRUE;
                        }
        
                    } else if ($relation->related_record->isBillable($currentDate)) {
                        $billIt = TRUE;
                    }
        
                    if ($billIt) {
                        $relations[] = array_merge(array(
                            'related_model'  => get_class($relation->related_record),
                            'related_id'     => $relation->related_id,
                            'related_record' => $relation->related_record->toArray(),
                        ), $relationDefaults);
        
                        $billableAccountables[] = $relation->related_record;
                    }
                }
            }
        
            if (! $customer) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    $failure = 'Could not create auto invoice for contract "' . $contract->title . '", because no customer could be found!';
                    $this->_autoInvoiceIterationFailures[] = $failure;
                    Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' ' . $failure, Zend_Log::INFO);
                }
                continue;
            }
        
            if (! $costcenter) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    $failure = 'Could not create auto invoice for contract "' . $contract->title . '", because no costcenter could be found!';
                    $this->_autoInvoiceIterationFailures[] = $failure;
                    Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' ' . $failure, Zend_Log::INFO);
                }
                continue;
            }
        
            // iterate products (they are non volatile)
            if ($contract->products && is_array($contract->products) && ! empty($contract->products)) {
                $productAggregates = new Tinebase_Record_RecordSet('Sales_Model_ProductAggregate', $contract->products);
        
                foreach($productAggregates as $productAggregate) {
        
                    if ($productAggregate->isBillable($currentDate, $contract)) {
                        $relations[] = array_merge(array(
                            'related_model'          => 'Sales_Model_ProductAggregate',
                            'related_id'             => $productAggregate->getId(),
                            'related_record'         => $productAggregate->toArray(),
                        ), $relationDefaults);
        
                        $billableAccountables[] = $productAggregate;
                    }
                }
            }
        
            // put each position into
            $invoicePositions = new Tinebase_Record_RecordSet('Sales_Model_InvoicePosition');
        
            foreach ($billableAccountables as $accountable) {
                $accountable->loadBillables($currentDate);
                $billables = $accountable->getBillables();
        
                if (empty($billables)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                        Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' '
                            . 'No efforts for the accountable ' . $accountable->getId() . ' of contract with the id "'
                                . $contract->title . '" could be found.', Zend_Log::INFO);
                    }
                    continue;
                }
        
                $invoicePositions = $invoicePositions->merge($this->_getInvoicePositionsFromBillables($billables, $accountable));
        
                list($startDate, $endDate) = $accountable->getInterval();
        
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
        
            // if there are no positions, no bill will be created, but the last_autobill info is set
            if ($invoicePositions->count() == 0) {
        
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' '
                        . 'No efforts for the contract "' . $contract->title . '" could be found.', Zend_Log::INFO);
                }
        
                if ($volatileBilled) {
                    $contractController->updateLastBilledDate($contract);
                }
                continue;
            }
        
            // prepare invoice
            $invoice = new Sales_Model_Invoice(array(
                'is_auto'       => TRUE,
                'description'   => $contract->title . ' (' . $currentDate->toString() . ')',
                'type'          => 'INVOICE',
                'address_id'    => $addressId,
                'credit_term'   => $customer['credit_term'],
                'customer_id'   => $customer['id'],
                'costcenter_id' => $costcenter->getId(),
                'start_date'    => $earliestStartDate,
                'end_date'      => $latestEndDate,
                'positions'     => $invoicePositions->toArray(),
                'date'          => NULL
            ));
        
            // add contract relation
            $relations[] = array(
                'own_model'              => 'Sales_Model_Invoice',
                'own_backend'            => Tasks_Backend_Factory::SQL,
                'own_id'                 => NULL,
                'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
                'related_model'          => 'Sales_Model_Contract',
                'related_backend'        => Tasks_Backend_Factory::SQL,
                'related_id'             => $contract->getId(),
                'related_record'         => $contract->toArray(),
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
                'related_id'             => $customer['id'],
                'related_record'         => $customer,
                'type'                   => 'CUSTOMER'
            );
        
            $invoice->relations = $relations;
        
            $invoice->setTimezone('UTC');
        
            // create invoice
            $this->_autoInvoiceIterationResults->addRecord($this->create($invoice));
        
            // update global last autobill date (for timeaccounts and volatile efforts) only if there are any
            if ($volatileBilled) {
                $contractController->updateLastBilledDate($contract);
            }
        
            // update last autobill info of the product
            foreach($billableAccountables as $accountable) {
                if (! $accountable->isVolatile()) {
                    $accountable->updateLastBilledDate();
                }
        
                $accountable->conjunctInvoiceWithBillables($invoice);
            }
        }
    }
    
    /**
     * creates the auto invoices, gets called by cli
     * 
     * @param Tinebase_DateTime $currentDate
     */
    public function createAutoInvoices(Tinebase_DateTime $currentDate)
    {
        $this->_autoInvoiceIterationResults = new Tinebase_Record_RecordSet('Sales_Model_Invoice');
        $this->_autoInvoiceIterationFailures = array();
        
        $contractBackend = new Sales_Backend_Contract();
        $ids = $contractBackend->getBillableContractIds($currentDate);
        
        $filter = new Sales_Model_ContractFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'id', 'operator' => 'in', 'value' => $ids)));
        
        
        $iterator = new Tinebase_Record_Iterator(array(
            'iteratable' => $this,
            'controller' => Sales_Controller_Contract::getInstance(),
            'filter'     => $filter,
            'options'    => array('getRelations' => TRUE),
            'function'   => 'processAutoInvoiceIteration',
        ));
        
        $iterator->iterate($currentDate);
        
        $result = array(
            'failures'       => $this->_autoInvoiceIterationFailures,
            'failures_count' => count($this->_autoInvoiceIterationFailures),
            'created'        => $this->_autoInvoiceIterationResults,
            'created_count'  => $this->_autoInvoiceIterationResults->count()
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
                'unit' => $accountable->getUnit()
            );
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' Create invoice position ' . print_r($pos, 1), Zend_Log::DEBUG);
            }
            
            $invoicePositions->addRecord(new Sales_Model_InvoicePosition($pos));
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
        $this->_checkCleared($_record);
    }
    
    /**
     * checks cleared state and sets the date to the current date, also sets all billables billed
     * 
     * @param Tinebase_Record_Interface $record
     */
    protected function _checkCleared(Tinebase_Record_Interface &$record)
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
        
        // if the record hasn't been cleared before, no date is set
        if ($record->cleared == 'CLEARED' && $record->date == NULL) {
            
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' Clearing Invoice ' . print_r($record->toArray(), 1), Zend_Log::INFO);
            }
            
            if (! $record->date) {
                $record->date = new Tinebase_DateTime();
            }
            
            $this->_setNextNumber($record);
            
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
            if (is_array($record->relations)) {
                foreach($record->relations as $relation) {
                    $relatedModel = $relation['related_model'];
                    $relatedRecord = new $relatedModel($relation['related_record']);
                    
                    if (in_array('Sales_Model_Accountable_Interface', class_implements($relatedRecord))) {
                        $relatedRecord->clearBillables($record);
                        
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                            Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' Clearing billables ' . print_r($relation['related_record'], 1), Zend_Log::INFO);
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
        
        foreach($records as $record) {
            if ($record->cleared == 'CLEARED') {
                throw new Sales_Exception_InvoiceAlreadyClearedDelete();
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
        if ($_oldRecord->cleared == 'CLEARED') {
            $diff = $_record->diff($_oldRecord);
            $diff = $diff['diff'];
            
            $allowChange = array('relations', 'notes', 'tags', 'attachments', 'description', 'created_by', 'creation_time',
                'last_modified_by', 'last_modified_time', 'is_deleted', 'deleted_by', 'deleted_time'
            );
            
            foreach($allowChange as $field) {
                unset($diff[$field]);
            }
            
            if (! empty($diff)) {
                throw new Sales_Exception_InvoiceAlreadyClearedEdit();
            }
        }
        $this->_checkCleared($_record);
        
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
