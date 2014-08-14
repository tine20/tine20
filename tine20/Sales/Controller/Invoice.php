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
        
        $contracts->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        
        $cfg = Sales_Config::getInstance();
        $interval = $cfg->get(Sales_Config::AUTO_INVOICE_CONTRACT_INTERVAL);
        
        foreach ($contracts as $contract) {
            
            if ($contract->interval > $interval) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' The Interval is longer than the configured AUTO_INVOICE_CONTRACT_INTERVAL');
                continue;
            }
            
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
            
            try {
                $this->_createAutoInvoicesForContract($contract, clone $currentDate);
                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            } catch (Exception $e) {
                Tinebase_TransactionManager::getInstance()->rollBack();
                
                $failure = 'Could not create auto invoice for contract "' . $contract->title . '" Exception: ' . $e->getCode() . ' has been thrown: "' . $e->getMessage() . '".';
                $this->_autoInvoiceIterationFailures[] = $failure;
                Tinebase_Exception::log($e);
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
     * create auto invoices for one contract
     * 
     * @param Sales_Model_Contract $contract
     * @param Tinebase_DateTime $currentDate
     */
    protected function _createAutoInvoicesForContract(Sales_Model_Contract $contract, Tinebase_DateTime $currentDate)
    {
        $filter = new Sales_Model_ProductAggregateFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'contract_id', 'operator' => 'equals', 'value' => $contract->getId())));
        
        $products = Sales_Controller_ProductAggregate::getInstance()->search($filter);
        $products->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        
        if ($products->count() == 0) {
            if ($contract->interval == 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' There aren\'t any products, and the interval of the contract is 0 -> don\'t handle contract');
                return false;
            }
            
            // check max interval
            $cfg = Sales_Config::getInstance();
            $maxInterval = $cfg->get(Sales_Config::AUTO_INVOICE_CONTRACT_INTERVAL);
            
            if ($contract->interval > $maxInterval) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' There aren\'t any products, and the interval of the contract is bigger than the value defined as 0 "auto_invoice_contract_interval in config.inc.php" -> don\'t handle contract');
                return false;
            }
        }
        
        if ($contract->end_date && $contract->last_autobill >= $contract->end_date) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Contract has been terminated and last bill has been created already');
            return false;
        }
        
        $nextBill = Sales_Controller_Contract::getInstance()->getNextBill($contract);

        if ($nextBill->isLaterOrEquals($currentDate)) {
            if ($products->count() == 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Don\'t handle - contract does not have to be billed and there aren\'t any products');
                return;
            } else {
                $billIt = FALSE;
                // otherwise iterate products
                foreach ($products as $product) {
                    // is null, if this is the first time to bill the contract
                    $lastBilled = $product->last_autobill == NULL ? NULL : clone $product->last_autobill;
                    
                    // if the product has been billed already, add the interval
                    if ($lastBilled) {
                        $nextBill = $lastBilled->addMonth($product->interval);
                    } else {
                        // it hasn't been billed already, so take the start_date of the contract as date
                        $nextBill = clone $contract->start_date;
                    }
                    
                    // assure creating the last bill if a contract has bee terminated
                    if (($contract->end_date !== NULL) && $nextBill->isLaterOrEquals($contract->end_date)) {
                        $nextBill = clone $contract->end_date;
                    }
                    
                    $nextBill->setTime(0,0,0);
                    
                    // there is a product to bill, so stop to iterate
                    if ($nextBill->isLaterOrEquals($currentDate)) {
                        $billIt = TRUE;
                        break;
                    }
                    
                }
                
                if (! $billIt) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Products don\'t have to be billed now.');
                    return false;
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
            return false;
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
        
            $contractLastAutobill = $contract->last_autobill ? clone $contract->last_autobill : clone $contract->start_date;
            
            if ($contract->billing_point == 'end') {
                $contractLastAutobill->addMonth($contract->interval);
            }
            
            // find accountables
            if (in_array('Sales_Model_Accountable_Interface', class_implements($relation->related_record))) {
        
                $billIt = FALSE;
        
                // if the related record is volatile, it does not know when billed last
                if ($relation->related_record->isVolatile() && $relation->related_record->isBillable($currentDate, $contract)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' Found volatile & billable accountable');
        
                    if ($contractLastAutobill->isEarlierOrEquals($currentDate)) {
                        $billIt = TRUE;
                    }
        
                } else if ($relation->related_record->isBillable($currentDate, $contract)) {
                    $billIt = TRUE;
                }
        
                if ($billIt) {
                    $relations[] = array_merge(array(
                        'related_model'  => get_class($relation->related_record),
                        'related_id'     => $relation->related_id,
                        'related_record' => $relation->related_record->toArray(),
                    ), $this->_getRelationDefaults());
        
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
            return false;
        }
        
        if (! $costcenter) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                $failure = 'Could not create auto invoice for contract "' . $contract->title . '", because no costcenter could be found!';
                $this->_autoInvoiceIterationFailures[] = $failure;
                Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' ' . $failure, Zend_Log::INFO);
            }
            return false;
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
                    ), $this->_getRelationDefaults());
        
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
        
        // if there are no positions, no bill will be created, but the last_autobill info is set, if the current date is later
        if ($invoicePositions->count() == 0) {
        
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' '
                        . 'No efforts for the contract "' . $contract->title . '" could be found.', Zend_Log::INFO);
            }
            
            if ($contractLastAutobill < $currentDate->subMonth($contract->interval)) {
                Sales_Controller_Contract::getInstance()->updateLastBilledDate($contract);
            }
            return false;
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
        
        $invoice->setTimezone('UTC', TRUE);
        
        // create invoice
        $invoice = $this->create($invoice, FALSE);
        $this->_autoInvoiceIterationResults[] = $invoice->getId();
        
        
        // always update global last autobill date (for timeaccounts and volatile efforts)
        Sales_Controller_Contract::getInstance()->updateLastBilledDate($contract);
        
        // update last autobill info of the product
        foreach($billableAccountables as $accountable) {
            if (! $accountable->isVolatile()) {
                $accountable->updateLastBilledDate();
            }
        
            $accountable->conjunctInvoiceWithBillables($invoice);
        }
        
        return true;
    }
    
    /**
     * creates the auto invoices, gets called by cli
     * 
     * @param Tinebase_DateTime $currentDate
     */
    public function createAutoInvoices(Tinebase_DateTime $currentDate)
    {
        $this->_autoInvoiceIterationResults  = array();
        $this->_autoInvoiceIterationFailures = array();
        
        $contractBackend = new Sales_Backend_Contract();
        $ids = $contractBackend->getBillableContractIds($currentDate);
        
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
        $records->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        
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
                    $contract->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
                    
                    // get all invoices related to this contract. throw exception if a follwing invoice has been found
                    $invoiceRelations = Tinebase_Relations::getInstance()->getRelations('Sales_Model_Contract', 'Sql', $contract->getId(), NULL, array(), TRUE, array('Sales_Model_Invoice'));
                    foreach($invoiceRelations as $invoiceRelation) {
                        $invoiceRelation->related_record->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
                        if ($record->getId() !== $invoiceRelation->related_record->getId() && $record->start_date < $invoiceRelation->related_record->start_date) {
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
                        array('field' => 'invoice_id', 'operator' => 'in', 'value' => $record->getId())
                    ));
                    
                    $billableControllerName::getInstance()->updateMultiple($filterInstance, array('invoice_id' => NULL));
                    
                    // set invoice ids of the timeaccounts
                    if ($model = 'Timetracker_Model_Timeaccount') {
                        $billableModelName      = $model::getBillableModelName();
                        
                        $filterInstance = new Timetracker_Model_TimeaccountFilter(array());
                        $filterInstance->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'invoice_id', 'operator' => 'in', 'value' => $record->getId())));
                        
                        Timetracker_Controller_Timeaccount::getInstance()->updateMultiple($filterInstance, array('invoice_id' => NULL));
                    }
                }
                
                // delete invoice positions
                $invoicePositionController->delete($invoicePositions->getId());
                
                // set last_autobill a period back
                if ($contract) {
                    if ($contract->last_autobill) {
                        $lab = clone $contract->last_autobill;
                        
                        $contract->last_autobill = $lab->subMonth($contract->interval);
                        $contract->last_autobill->setTime(0,0,0);
                        // do not try to update dependent records (products)
                        $contract->products = NULL;
                        $contract->setTimezone('UTC');
                        $contractController->update($contract);
                    }
                    
                    // check product aggregates
                    $filter = new Sales_Model_ProductAggregateFilter(array());
                    $filter->addFilter(new Tinebase_Model_Filter_Text(
                        array('field' => 'contract_id', 'operator' => 'equals', 'value' => $contract->getId())
                    ));
                    
                    $paController = Sales_Controller_ProductAggregate::getInstance();
                    $productAggregates = $paController->search($filter);
                    $productAggregates->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
                    
                    foreach($productAggregates as $productAggregate) {
                        $lab = clone $productAggregate->last_autobill;
                    
                        if ($lab) {
                            $productAggregate->last_autobill = $lab->addMonth(- (int) $productAggregate->interval);
                            $productAggregate->last_autobill->setTime(0,0,0);
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
