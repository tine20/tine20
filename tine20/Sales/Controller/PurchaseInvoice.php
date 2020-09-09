<?php
/**
 * customer controller for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2015-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * customer controller class for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_PurchaseInvoice extends Sales_Controller_NumberableAbstract
{
    protected $_applicationName      = 'Sales';
    protected $_modelName            = 'Sales_Model_PurchaseInvoice';
    protected $_doContainerACLChecks = false;
    protected $_duplicateCheckOnUpdate = true;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_backend = new Sales_Backend_PurchaseInvoice();
        $this->_duplicateCheckFields = array(
            array('number'),
            array('date', 'price_total'),
        );
        // TODO this should be done automatically if model has customfields (hasCustomFields)
        $this->_resolveCustomFields = true;
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var Sales_Controller_PurchaseInvoice
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Sales_Controller_PurchaseInvoice
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    /**
     * import one purchase invoice file
     * 
     * a new invoice with default data will be created and the invoice file will be attached
     * 
     * @param string $name  name of the invoice
     * @param resource $data  binary data of the invoice (aka a pdf)
     * @throws Sabre\DAV\Exception\Forbidden
     * @return Sales_Model_PurchaseInvoice
     */
    public function importPurchaseInvoice($name, $data)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(
            __METHOD__ . '::' . __LINE__ . ' Creating purchase invoice for imported file ' . $name);

        $purchaseInvoice = new Sales_Model_PurchaseInvoice(array(
            'number'      => '',
            'description' => '',
            'date'        => Tinebase_DateTime::now(),
            'discount'    => 0,
            'due_in'      => 0,
            'due_at'      => Tinebase_DateTime::now(),
            'price_gross' => 0,
            'price_net'   => 0,
            'price_tax'   => 0,
            'sales_tax'   => 0
        ));
        
        // Don't use duplicate check for this import. The default data makes them duplicate by default.
        $this->create($purchaseInvoice, /* duplicateCheck = */ false);
        
        // attach invoice file (aka a pdf)
        $attachmentPath = Tinebase_FileSystem_RecordAttachments::getInstance()->getRecordAttachmentPath($purchaseInvoice, TRUE);

        $deleteFile = !Tinebase_FileSystem::getInstance()->fileExists($attachmentPath . '/' . $name);
        try {
            $handle = Tinebase_FileSystem::getInstance()->fopen($attachmentPath . '/' . $name, 'w');

            if (!is_resource($handle)) {
                throw new Sabre\DAV\Exception\Forbidden('Permission denied to create file:' . $attachmentPath . '/' . $name );
            }

            if (is_resource($data)) {
                stream_copy_to_stream($data, $handle);
            }

            Tinebase_FileSystem::getInstance()->fclose($handle);

        } catch (Exception $e) {
            if ($deleteFile) {
                Tinebase_FileSystem::getInstance()->unlink($attachmentPath . '/' . $name);
            }
            throw $e;
        }
        
        return $this->get($purchaseInvoice);
    }
    
    /**
     * relation defaults
     * 
     * @return array
     */
    protected function _getRelationDefaults()
    {
        return array(
            'own_model'       => 'Sales_Model_PurchaseInvoice',
            'own_backend'     => Tasks_Backend_Factory::SQL,
            'own_id'          => NULL,
            'related_degree'  => Tinebase_Model_Relation::DEGREE_SIBLING,
            'related_backend' => Tasks_Backend_Factory::SQL,
            'type'            => 'PURCHASE_INVOICE'
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
                if (! Tinebase_Core::getUser()->hasRight('Sales', Sales_Acl_Rights::MANAGE_PURCHASE_INVOICES)) {
                    throw new Tinebase_Exception_AccessDenied("You don't have the right to manage purchase invoices!");
                }
                break;
            default;
            break;
        }

        parent::_checkRight($_action);
    }
}
