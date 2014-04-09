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
    const ZEROFILL = 6;
    
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
     * creates the auto invoices, gets called by cli
     * 
     * @param Tinebase_DateTime $date
     */
    public function createAutoInvoices(Tinebase_DateTime $date)
    {
        $contractController = Sales_Controller_Contract::getInstance();
        $contractsToBill = $contractController->getBillableContracts($date);
        $timesheetController = Timetracker_Controller_Timesheet::getInstance();
        $productAggregateController = Sales_Controller_ProductAggregate::getInstance();
        
        $failures = array();
        $created = new Tinebase_Record_RecordSet('Sales_Model_Invoice');
        $customer = $costcenter = NULL;
        
        // this holds all relations for the invoice
        $relations = array();
        $relationDefaults = array(
            'own_model'              => 'Sales_Model_Invoice',
            'own_backend'            => Tasks_Backend_Factory::SQL,
            'own_id'                 => NULL,
            'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_backend'        => Tasks_Backend_Factory::SQL,
            'type'                   => 'INVOICE_ITEM'
        );
        $billVolatileModels = array(
            'WebAccounting_Model_StoragePath',
            'WebAccounting_Model_BackupPath',
            'WebAccounting_Model_CertificateDomain',
            'WebAccounting_Model_Dreg',
            'WebAccounting_Model_MailAccount',
            'WebAccounting_Model_IPNet'
        );
        
        foreach($contractsToBill as $contract) {
            
            $relations = array();
            
            $customer = NULL;
            $addressId = $contract->billing_address_id;
            
            if (! $addressId) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    $failure = 'Could not create auto invoice for contract "' . $contract->title . '", because no billing address could be found!';
                    $failures[] = $failure;
                    Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' ' . $failure, Zend_Log::INFO);
                }
                continue;
            }
            
            // if the contract gets billed the first time, no last_autobill date will be set
            $startDate = $contract->last_autobill ? clone $contract->last_autobill : NULL;
            
            if ($startDate === NULL) {
                $startDate = clone $contract->start_date;
            } else {
                if ($contract->billing_point != 'end') {
                    $startDate->addMonth($contract->interval);
                }
            }
            
            $endDate = clone $startDate;
            $endDate->addMonth($contract->interval)->subSecond(1);
            
            if ($contract->end_date !== NULL && $endDate->isLater($contract->end_date)) {
                $endDate = clone $contract->end_date;
                $endDate->subSecond(1);
            }
            
            $billVolatile       = array();
            $billProducts       = array();
            $billTimeaccounts   = array();
            $updateTimeaccounts = array();
            
            foreach($contract->relations as $relation) {
                if ($relation->type == 'CUSTOMER' && $relation->related_model == 'Sales_Model_Customer') {
                    $customer = $relation->related_record;
                    
                } elseif ($relation->type == 'LEAD_COST_CENTER' && $relation->related_model == 'Sales_Model_CostCenter') {
                    $costcenter = $relation->related_record;
                    
                } elseif ($relation->related_model == 'Timetracker_Model_Timeaccount') {
                    // if this has a budget and status is "to clear", bill it
                    if (intval($relation->related_record->budget) > 0 && $relation->related_record->status == 'to bill') {
                        $billTimeaccounts[]   = $relation->related_id;
                        
                        $relations[] = array_merge(array(
                            'related_model'  => 'Timetracker_Model_Timeaccount',
                            'related_id'     => $relation->related_id,
                            'related_record' => $relation->related_record->toArray(),
                        ), $relationDefaults);
                        
                    } elseif (intval($relation->related_record->budget) == 0) {
                        // if this is not budgeted, show for timesheets in this period
                        $filter = new Timetracker_Model_TimesheetFilter(array(
                            array('field' => 'start_date', 'operator' => 'before', 'value' => $endDate),
                            array('field' => 'start_date', 'operator' => 'after', 'value' => $startDate)
                            
                        ));
                        
                        $filter->addFilter(new Tinebase_Model_Filter_Text(
                            array('field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $relation->related_id)
                        ));
                        
                        $timesheets = $timesheetController->search($filter);
                        
                        $updateTimeaccounts[] = $relation->related_id;
                        
                        if ($timesheets->count() > 0)  {
                            $billTimeaccounts[] = $relation->related_id;
                            
                            $relations[] = array_merge(array(
                                'related_model'          => 'Timetracker_Model_Timeaccount',
                                'related_id'             => $relation->related_record->getId(),
                                'related_record'         => $relation->related_record->toArray(),
                            ), $relationDefaults);
                        }
                    }
                } elseif (in_array($relation->related_model, $billVolatileModels)) {
                    $billVolatile[] = array($relation->related_model, $relation->related_id);
                    $relations[] = array_merge(array(
                        'related_model'          => $relation->related_model,
                        'related_id'             => $relation->related_record->getId(),
                        'related_record'         => $relation->related_record->toArray(),
                    ), $relationDefaults);
                }
            }
            
            if (! $customer) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    $failure = 'Could not create auto invoice for contract "' . $contract->title . '", because no customer could be found!';
                    $failures[] = $failure;
                    Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' ' . $failure, Zend_Log::INFO);
                }
                continue;
            }
            
            if (! $costcenter) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    $failure = 'Could not create auto invoice for contract "' . $contract->title . '", because no costcenter could be found!';
                    $failures[] = $failure;
                    Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' ' . $failure, Zend_Log::INFO);
                }
                continue;
            }
            
            $earliestStartDate = clone $startDate;
            $latestEndDate     = clone $endDate;
            
            if ($contract->products && is_array($contract->products) && ! empty($contract->products)) {
                // find out max interval of any billable
                if (empty($billVolatile) && empty($billTimeaccounts)) {
                    $earliestStartDate = $latestEndDate = NULL;
                }
                
                foreach($contract->products as $productAggregate) {
                    $prodStartDate = $productAggregate['last_autobill'] ? new Tinebase_DateTime($productAggregate['last_autobill']) : NULL;
                    
                    if ($prodStartDate === NULL) {
                        $prodStartDate = clone $contract->start_date;
                    }
                    
                    $prodEndDate = clone $prodStartDate;
                    $prodEndDate->addMonth($productAggregate['interval'])->subSecond(1);
                    
                    if ($contract->end_date !== NULL && $prodEndDate->isLater($contract->end_date)) {
                        $prodEndDate = clone $contract->end_date;
                        $prodEndDate->subSecond(1);
                    }
                    
                    if ($prodStartDate <= $date && $prodEndDate >= $date) {
                        $paRecord = new Sales_Model_ProductAggregate($productAggregate, TRUE);

                        if ($earliestStartDate == NULL || $earliestStartDate > $prodStartDate) {
                            $earliestStartDate = $prodStartDate;
                        }
                        
                        if ($latestEndDate == NULL || $latestEndDate < $prodEndDate) {
                            $latestEndDate = $prodEndDate;
                        }
                        
                        $relations[] = array_merge(array(
                            'related_model'          => 'Sales_Model_ProductAggregate',
                            'related_id'             => $paRecord->getId(),
                            'related_record'         => $paRecord->toArray(),
                        ), $relationDefaults);
                        
                        $billProducts[] = $paRecord;
                    }
                }
            }
            
            // skip invoice if there aren't any efforts
            if (empty($billVolatile) && empty($billProducts) && empty($billTimeaccounts)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
                    Tinebase_Core::getLogger()->log(__METHOD__ . '::' . __LINE__ . ' ' . 'No efforts for the contract with the id "' . $contract->getId() . '" could have been found.', Zend_Log::INFO);
                }
                
                // but update last billed date if there are timeaccounts to bill but having no timesheets
                if (! empty($updateTimeaccounts)) {
                    $contractController->updateLastBilledDate($contract);
                }
                
                continue;
            }
            // prepare invoice
            $invoice = new Sales_Model_Invoice(array(
                'is_auto'       => TRUE,
                'description'   => $date->toString() . ' ' . $contract->title,
                'type'          => 'INVOICE',
                'address_id'    => $addressId,
                'credit_term'   => $customer['credit_term'],
                'customer_id'   => $customer['id'],
                'costcenter_id' => $costcenter->getId(),
                'start_date'    => $earliestStartDate,
                'end_date'      => $latestEndDate,
            ));
            
            // add relations
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
            
            // create invoice
            $created->addRecord($this->create($invoice));
            
            // update global last autobill date (for timeaccounts and volatile efforts) only if there are any
            if (! (empty($billTimeaccounts) && empty($billVolatile))) {
                $contractController->updateLastBilledDate($contract);
            }
            
            // update last autobill info of the product
            foreach($billProducts as $productAggregate) {
                $productAggregateController->updateLastBilledDate($productAggregate, $contract);
            }
        }
        
        $result = array(
            'failures'       => $failures,
            'failures_count' => count($failures),
            'created'        => $created,
            'created_count'  => $created->count()
        );
        
        return $result;
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
     * returns the formatted invoice number
     * 
     * @param integer $number
     * @return string
     */
    protected function _formatNumber($number)
    {
        return 'R-' . str_pad((string) $number, self::ZEROFILL, '0', STR_PAD_LEFT);
    }
    
    /**
     * checks cleared state and sets the date to the current date
     * 
     * @param Tinebase_Record_Interface $record
     */
    protected function _checkCleared(Tinebase_Record_Interface &$record)
    {
        $foundCustomer = FALSE;
        $customerCalculated = FALSE;
        
        foreach($record->relations as $relation) {
            if ($relation['related_model'] == 'Sales_Model_Customer') {
                $foundCustomer = $relation['related_record'];
                break;
            }
        }
        
        $foundContract = FALSE;
        
        foreach($record->relations as $relation) {
            if ($relation['related_model'] == 'Sales_Model_Contract') {
                $foundContract = $relation['related_record'];
                
                if (! $foundCustomer) {
                    
                    $foundContractRecord = Sales_Controller_Contract::getInstance()->get($foundContract['id']);
                    
                    foreach($foundContractRecord->relations as $relation) {
                        if ($relation['related_model'] == 'Sales_Model_Customer') {
                            $foundCustomer = $relation['related_record'];
                            $customerCalculated = TRUE;
                            break;
                        }
                    }
                }
                break;
            }
        }
        
        if (! $foundCustomer) {
            throw new Tinebase_Exception_Data('You have to set a customer!');
        } else {
            if ($customerCalculated) {
                $record->relations = array_merge($record->relations, array(array(
                    "own_model"              => "Sales_Model_Invoice",
                    "own_backend"            => Tasks_Backend_Factory::SQL,
                    'own_degree'             => Tinebase_Model_Relation::DEGREE_SIBLING,
                    'related_model'          => 'Sales_Model_Customer',
                    'related_backend'        => Tasks_Backend_Factory::SQL,
                    'related_id'             => $foundCustomer->getId(),
                    'related_record'         => $foundCustomer->toArray(),
                    'type'                   => 'CUSTOMER'
                )));
            }
        }
        
        if (empty($record->address_id)) {
            $json = new Sales_Frontend_Json();
            $resolved = $json->getCustomer($foundCustomer->getId());
            if (! empty($resolved['billing'])) {
                $record->address_id = $resolved['billing'][0]['id'];
            } else {
                throw new Tinebase_Exception_Data('You have to set a billing address!');
            }
        }
        
        if ($record->cleared == 'CLEARED' && $record->date == NULL) {
            if (! $record->date) {
                $record->date = new Tinebase_DateTime();
            }
            
            if (empty($record->number)) {
                // create number
                $this->_addNextNumber($record);
                $record->number = $this->_formatNumber($record->number);
            } else {
                // check uniquity if not autogenerated
                try {
                    $this->_checkNumberUniquity($record, false);
                    $this->_setLastNumber($record);
                    $record->number = $this->_formatNumber($record->number);
                } catch (Tinebase_Exception_Duplicate $e) {
                    throw new Sales_Exception_DuplicateNumber();
                }
            }
            
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
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
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
