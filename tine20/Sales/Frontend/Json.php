<?php
/**
 * Tine 2.0
 * @package     Sales
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @todo        add functions again (__call interceptor doesn't work because of the reflection api)
 * @todo        check if we can add these functions to the reflection without implementing them here
 */

/**
 *
 * This class handles all Json requests for the Sales application
 *
 * @package     Sales
 * @subpackage  Frontend
 */
class Sales_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * @see Tinebase_Frontend_Json_Abstract
     * 
     * @var string
     */
    protected $_applicationName = 'Sales';

    /**
     * @see Tinebase_Frontend_Json_Abstract
     */
    protected $_relatableModels = array(
        'Sales_Model_Contract',
        'Sales_Model_CostCenter',
        'Sales_Model_Customer',
        'Sales_Model_Offer',
        'Sales_Model_Address',
        'Sales_Model_ProductAggregate',
    );

    /**
     * All configured models
     *
     * @var array
     */
    protected $_configuredModels = array(
        Sales_Model_Document_Address::MODEL_NAME_PART,
        Sales_Model_Document_Customer::MODEL_NAME_PART,
        Sales_Model_Document_Boilerplate::MODEL_NAME_PART,
        Sales_Model_Product::MODEL_NAME_PART,
        Sales_Model_ProductLocalization::MODEL_NAME_PART,
        Sales_Model_SubProductMapping::MODEL_NAME_PART,
        Sales_Model_Document_Offer::MODEL_NAME_PART,
        Sales_Model_DocumentPosition_Offer::MODEL_NAME_PART,
        Sales_Model_Document_Order::MODEL_NAME_PART,
        Sales_Model_DocumentPosition_Order::MODEL_NAME_PART,
        Sales_Model_Document_Delivery::MODEL_NAME_PART,
        Sales_Model_DocumentPosition_Delivery::MODEL_NAME_PART,
        Sales_Model_Document_Invoice::MODEL_NAME_PART,
        Sales_Model_DocumentPosition_Invoice::MODEL_NAME_PART,
        'Contract',
        'CostCenter',
        'Customer',
        'Offer',
        'Address',
        'ProductAggregate',
        'Boilerplate'
    );
    
    /**
     * the constructor
     */
    public function __construct()
    {
        if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_INVOICES_MODULE)) {
            $this->_relatableModels[]  = 'Sales_Model_Invoice';
            $this->_configuredModels[] = 'InvoicePosition';
            $this->_configuredModels[] = 'Invoice';
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Invoices module disabled');
            }
        }
        if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_OFFERS_MODULE)) {
            $this->_relatableModels[]  = 'Sales_Model_Offer';
            $this->_configuredModels[] = 'Offer';
        }
        if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_SUPPLIERS_MODULE)) {
            $this->_relatableModels[]  = 'Sales_Model_Supplier';
            $this->_configuredModels[] = 'Supplier';
        }
        if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_PURCHASE_INVOICES_MODULE)) {
            $this->_relatableModels[]  = 'Sales_Model_PurchaseInvoice';
            $this->_configuredModels[] = 'PurchaseInvoice';
        }
        if (Sales_Config::getInstance()->featureEnabled(Sales_Config::FEATURE_ORDERCONFIRMATIONS_MODULE)) {
            $this->_relatableModels[]  = 'Sales_Model_OrderConfirmation';
            $this->_configuredModels[] = 'OrderConfirmation';
        }
    }
    
   /**
     * Returns registry data of the application.
     *
     * Each application has its own registry to supply static data to the client.
     * Registry data is queried only once per session from the client.
     *
     * This registry must not be used for rights or ACL purposes. Use the generic
     * rights and ACL mechanisms instead!
     *
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        $sharedContainer = Sales_Controller_Contract::getSharedContractsContainer();
        $sharedContainer->resolveGrantsAndPath();
        return array(
            'defaultContractContainer' => $sharedContainer->toArray(),
        );
    }
    
    /**
     * Sets the config for Sales
     * @param array $config
     */
    public function setConfig($config)
    {
        return Sales_Controller::getInstance()->setConfig($config);
    }
    
    /**
     * Get Config for Sales
     * @return array
     */
    public function getConfig()
    {
        return Sales_Controller::getInstance()->getConfig();
    }
    
    /*************************** contracts functions *****************************/


    /**
     * rebills an invoice
     *
     * @param string $id
     * @param string $date
     */
    public function billContract($id, $date)
    {
        $contract = Sales_Controller_Contract::getInstance()->get($id);
        
        $date = new Tinebase_DateTime($date);
        $date->setTimezone(Tinebase_Core::getUserTimezone());
    
        return Sales_Controller_Invoice::getInstance()->createAutoInvoices($date, $contract);
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchContracts($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_Contract::getInstance(), 'Sales_Model_ContractFilter',
            /* $_getRelations */ array('Sales_Model_Customer', 'Addressbook_Model_Contact', 'Sales_Model_CostCenter'));
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getContract($id)
    {
        $contract = $this->_get($id, Sales_Controller_Contract::getInstance());
        if (! empty($contract['billing_address_id'])) {
            $contract['billing_address_id'] = Sales_Controller_Address::getInstance()->resolveVirtualFields($contract['billing_address_id']);
        }
        // TODO: resolve this in controller
        if (! empty($contract['products']) && is_array($contract['products'])) {
            $cc = Sales_Controller_Product::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Product::class, array()));
            for ($i = 0; $i < count($contract['products']); $i++) {
                $costCenter = $cc->filter('id', $contract['products'][$i]['product_id'])->getFirstRecord();
                if ($costCenter) {
                    $contract['products'][$i]['product_id'] = $costCenter->toArray();
                }
                if (Tinebase_Application::getInstance()->isInstalled('WebAccounting')) {
                    if (isset($contract['products'][$i]['json_attributes']['assignedAccountables'])) {
                        $contract['products'][$i]['json_attributes']['assignedAccountables'] =
                            $this->_resolveAssignedAccountables(
                                $contract['products'][$i]['json_attributes']['assignedAccountables']);
                    }
                }
            }
        }
        
        return $contract;
    }

    /**
     * @param array $assignedAccountables
     * @return array
     *
     * TODO support other models + make this generic
     */
    protected function _resolveAssignedAccountables(&$assignedAccountables)
    {
        $assignedAccountableIds = [];
        foreach ($assignedAccountables as $accountable) {
            $assignedAccountableIds[] = $accountable['id'];
        }
        if (count($assignedAccountableIds) > 0
            && Tinebase_Application::getInstance()->isInstalled('WebAccounting', true)
            && class_exists('WebAccounting_Controller_ProxmoxVM')
        ) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' resolving accountables: '
                . print_r($assignedAccountableIds, true));
            $model = 'WebAccounting_Model_ProxmoxVM';
            $accountables = WebAccounting_Controller_ProxmoxVM::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel($model, [
                    ['field' => 'id', 'operator' => 'in', 'value' => $assignedAccountableIds]
                ]));
            foreach ($assignedAccountables as $key => $accountableArray) {
                $accountable = $accountables->getById($accountableArray['id']);
                if ($accountable) {
                    $assignedAccountables[$key]['id'] = $accountable->toArray();
                }
            }
        }
        return $assignedAccountables;
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     * 
     * @todo remove billing_address_id sanitizing (@see 0009906: generic solution for sanitizing ids by extracting id value from array)
     */
    public function saveContract($recordData)
    {
        if (isset($recordData['billing_address_id']) && is_array($recordData['billing_address_id'])) {
            $recordData['billing_address_id'] = $recordData['billing_address_id']['id'];
        }
        
        return $this->_save($recordData, Sales_Controller_Contract::getInstance(), 'Contract');
    }

    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteContracts($ids)
    {
        return $this->_delete($ids, Sales_Controller_Contract::getInstance());
    }

    /*************************** procuct aggregate functions *************************/

    public function searchProductAggregates($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_ProductAggregate::getInstance(), 'Sales_Model_ProductAggregateFilter');
    }
    
    /*************************** costcenter functions *****************************/

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchCostCenters($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_CostCenter::getInstance(), 'Sales_Model_CostCenterFilter');
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getCostCenter($id)
    {
        return $this->_get($id, Sales_Controller_CostCenter::getInstance());
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveCostCenter($recordData)
    {
        return $this->_save($recordData, Sales_Controller_CostCenter::getInstance(), 'CostCenter');
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteCostCenters($ids)
    {
        return $this->_delete($ids, Sales_Controller_CostCenter::getInstance());
    }

    // customer methods

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchCustomers($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Sales_Controller_Customer::getInstance(), 'Sales_Model_CustomerFilter');
        return $result;
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getCustomer($id)
    {
        return $this->_get($id, Sales_Controller_Customer::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @param  boolean $duplicateCheck
     *
     * @return array created/updated record
     * 
     * @todo move code to customer controller inspect functions
     */
    public function saveCustomer($recordData, $duplicateCheck = TRUE)
    {
        $ret = $this->_save($recordData, Sales_Controller_Customer::getInstance(), 'Customer', 'id', array($duplicateCheck));
        return $this->getCustomer($ret['id']);
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteCustomers($ids)
    {
        return $this->_delete($ids, Sales_Controller_Customer::getInstance());
    }
    
    
    /*************************** supplier functions *****************************/

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchSuppliers($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Sales_Controller_Supplier::getInstance(), 'Sales_Model_SupplierFilter');
        
        for ($i = 0; $i < count($result['results']); $i++) {
            if (isset($result['results'][$i]['postal_id'])) {
                $result['results'][$i]['postal_id'] = Sales_Controller_Address::getInstance()->resolveVirtualFields($result['results'][$i]['postal_id']);
            }
        }
        
        return $result;
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getSupplier($id)
    {
        return $this->_get($id, Sales_Controller_Supplier::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @param  boolean $duplicateCheck
     *
     * @return array created/updated record
     */
    public function saveSupplier($recordData, $duplicateCheck = TRUE)
    {
        $ret = $this->_save($recordData, Sales_Controller_Supplier::getInstance(), 'Sales_Model_Supplier', 'id', array($duplicateCheck));
        return $this->getSupplier($ret['id']);
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteSuppliers($ids)
    {
        return $this->_delete($ids, Sales_Controller_Supplier::getInstance());
    }
    
    /*************************** order confirmation functions *****************************/
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchOrderConfirmations($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_OrderConfirmation::getInstance(), 'Sales_Model_OrderConfirmationFilter', array('Sales_Model_Contract'));
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getOrderConfirmation($id)
    {
        return $this->_get($id, Sales_Controller_OrderConfirmation::getInstance());
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @param  boolean $duplicateCheck
     *
     * @return array created/updated record
     */
    public function saveOrderConfirmation($recordData, $duplicateCheck)
    {
        return $this->_save($recordData, Sales_Controller_OrderConfirmation::getInstance(), 'OrderConfirmation');
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteOrderConfirmations($ids)
    {
        return $this->_delete($ids, Sales_Controller_OrderConfirmation::getInstance());
    }
    
    // invoice methods
    
    /**
     * rebills an invoice
     * 
     * @param string $id
     */
    public function rebillInvoice($id)
    {
        $invoice = Sales_Controller_Invoice::getInstance()->get($id);
        $relation = Tinebase_Relations::getInstance()->getRelations('Sales_Model_Invoice', 'Sql', $id, 'sibling', array('CONTRACT'), 'Sales_Model_Contract')->getFirstRecord();
        $contract = Sales_Controller_Contract::getInstance()->get($relation->related_id);
        
        $date = clone $invoice->creation_time;
        $date->setTimezone(Tinebase_Core::getUserTimezone());
        
        Sales_Controller_Invoice::getInstance()->delete(array($id));
        
        return Sales_Controller_Invoice::getInstance()->createAutoInvoices($date, $contract);
    }
    
    /**
     * merge an invoice
     *
     * @param string $id
     */
    public function mergeInvoice($id)
    {
        $invoice = Sales_Controller_Invoice::getInstance()->get($id);
        $relation = Tinebase_Relations::getInstance()->getRelations('Sales_Model_Invoice', 'Sql', $id, 'sibling', array('CONTRACT'), 'Sales_Model_Contract')->getFirstRecord();
        $contract = Sales_Controller_Contract::getInstance()->get($relation->related_id);
    
        $date = clone $invoice->creation_time;
        $date->setTimezone(Tinebase_Core::getUserTimezone());
    
        Sales_Controller_Invoice::getInstance()->delete(array($id));
    
        return Sales_Controller_Invoice::getInstance()->createAutoInvoices($date, $contract, true);
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchInvoices($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_Invoice::getInstance(), 'Sales_Model_InvoiceFilter', array('Sales_Model_Customer', 'Sales_Model_Contract'));
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getInvoice($id)
    {
        $invoice =  $this->_get($id, Sales_Controller_Invoice::getInstance());
        $json = new Tinebase_Convert_Json();
        $resolvedProducts = new Tinebase_Record_RecordSet('Sales_Model_Product');
        $productController = Sales_Controller_Product::getInstance();
        
        foreach ($invoice['relations'] as &$relation) {
            if ($relation['related_model'] == "Sales_Model_ProductAggregate") {
                if (! $product = $resolvedProducts->getById($relation['related_record']['product_id'])) {
                    $product = $productController->get($relation['related_record']['product_id']);
                    $resolvedProducts->addRecord($product);
                }
                $relation['related_record']['product_id'] = $json->fromTine20Model($product);
            }
        }

        if (is_array($invoice['positions']) && count($invoice['positions']) > 500) {
            // limit invoice positions to 500 to make sure browser storage quota is not exceeded
            // TODO add paging
            $invoice['positions'] = array_slice($invoice['positions'], 0, 499);
        }
        
        return $invoice;
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @param  boolean $duplicateCheck
     *
     * @return array created/updated record
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function saveInvoice($recordData, $duplicateCheck = TRUE)
    {
        // validate customer
        $foundCustomer = FALSE;
        $customerCalculated = FALSE;
        
        if (isset($recordData['relations']) && is_array($recordData['relations'])) {
            foreach($recordData['relations'] as $relation) {
                if ($relation['related_model'] == 'Sales_Model_Customer' && isset($relation['related_id'])) {
                    $foundCustomer = $relation['related_id'];
                    break;
                }
            }
        }
        // if no customer is set, try to find by contract
        if (isset($recordData['relations']) && is_array($recordData['relations']) && ! $foundCustomer) {
            foreach($recordData['relations'] as $relation) {
                if ($relation['related_model'] == 'Sales_Model_Contract') {
                    $foundContractRecord = Sales_Controller_Contract::getInstance()->get($relation['related_id']);
                    foreach($foundContractRecord->relations as $relation) {
                        if ($relation['related_model'] == 'Sales_Model_Customer') {
                            $foundCustomer = $relation['related_id'];
                            $customerCalculated = TRUE;
                            break 2;
                        }
                    }
                }
            }
        }
        
        if ($customerCalculated) {
            $recordData['relations'] = array_merge($recordData['relations'], array(array(
                "own_model"              => "Sales_Model_Invoice",
                "own_backend"            => Tasks_Backend_Factory::SQL,
                'related_degree'         => Tinebase_Model_Relation::DEGREE_SIBLING,
                'related_model'          => 'Sales_Model_Customer',
                'related_backend'        => Tasks_Backend_Factory::SQL,
                'related_id'             => $foundCustomer,
                'type'                   => 'CUSTOMER'
            )));
        }
        
        if (! $foundCustomer) {
            $translation = Tinebase_Translation::getTranslation('Sales');
            throw new Tinebase_Exception_SystemGeneric($translation->_('You have to set a customer!'));
        }
        
        if (isset($recordData['address_id']) && is_array($recordData["address_id"])) {
            $recordData["address_id"] = $recordData["address_id"]['id'];
        }
        if (isset($recordData['costcenter_id']) && is_array($recordData["costcenter_id"])) {
            $recordData["costcenter_id"] = $recordData["costcenter_id"]['id'];
        }
        // sanitize product_id
        if (isset($recordData['positions']) && is_array($recordData['positions'])) {
            for ($i = 0; $i < count($recordData['positions']); $i++) {
                if (isset($recordData['positions'][$i]['product_id']) && is_array($recordData['positions'][$i]['product_id'])) {
                    $recordData['positions'][$i]['product_id'] = $recordData['positions'][$i]['product_id']['id'];
                }
            }
        }

        /*
        if (isset($recordData['relations']) && is_array($recordData['relations'])) {
            for ($i = 0; $i < count($recordData['relations']); $i++) {
                if (isset($recordData['relations'][$i]['related_record']['product_id'])) {
        
                    if (is_array($recordData['relations'][$i]['related_record']['product_id'])) {
                        $recordData['relations'][$i]['related_record']['product_id'] = $recordData['relations'][$i]['related_record']['product_id']['id'];
                    }
                } elseif ($recordData['relations'][$i]['related_model'] == 'Sales_Model_Invoice') {
                    if (is_array($recordData['relations'][$i]['related_record']['address_id'])) {
                        $recordData['relations'][$i]['related_record']['address_id'] = $recordData['relations'][$i]['related_record']['address_id']['id'];
                    }
                }
            }
        }*/

        return $this->_save($recordData, Sales_Controller_Invoice::getInstance(), 'Invoice', 'id', array($duplicateCheck));
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteInvoices($ids)
    {
        return $this->_delete($ids, Sales_Controller_Invoice::getInstance());
    }
    
    /*************************** purchase invoice functions *****************************/
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchPurchaseInvoices($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_PurchaseInvoice::getInstance(),
            'Sales_Model_PurchaseInvoiceFilter',
            ['Sales_Model_Supplier', 'Sales_Model_CostCenter', 'Addressbook_Model_Contact']);
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getPurchaseInvoice($id)
    {
        $invoice =  $this->_get($id, Sales_Controller_PurchaseInvoice::getInstance());
        
        return $invoice;
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @param  boolean $duplicateCheck
     *
     * @return array created/updated record
     */
    public function savePurchaseInvoice($recordData, $duplicateCheck = TRUE)
    {
        // validate supplier
        $foundSupplier = FALSE;
        
        if (is_array($recordData['relations'])) {
            foreach($recordData['relations'] as $relation) {
                if ($relation['related_model'] == 'Sales_Model_Supplier') {
                    $foundSupplier = $relation['related_id'];
                    break;
                }
            }
        }
        
        if (! $foundSupplier) {
            throw new Tinebase_Exception_Data('You have to set a customer!');
        }
        
        #if (is_array($recordData["costcenter_id"])) {
        #    $recordData["costcenter_id"] = $recordData["costcenter_id"]['id'];
        #}
        /*
        if (is_array($recordData['relations'])) {
            for ($i = 0; $i < count($recordData['relations']); $i++) {
                if (isset($recordData['relations'][$i]['related_record']['product_id'])) {
        
                    if (is_array($recordData['relations'][$i]['related_record']['product_id'])) {
                        $recordData['relations'][$i]['related_record']['product_id'] = $recordData['relations'][$i]['related_record']['product_id']['id'];
                    }
                } elseif ($recordData['relations'][$i]['related_model'] == 'Sales_Model_Invoice') {
                    if (is_array($recordData['relations'][$i]['related_record']['address_id'])) {
                        $recordData['relations'][$i]['related_record']['address_id'] = $recordData['relations'][$i]['related_record']['address_id']['id'];
                    }
                }
            }
        }*/

        return $this->_save($recordData, Sales_Controller_PurchaseInvoice::getInstance(), 'PurchaseInvoice', 'id', array($duplicateCheck));
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deletePurchaseInvoices($ids)
    {
        return $this->_delete($ids, Sales_Controller_PurchaseInvoice::getInstance());
    }

    public function trackDocument(string $documentModel, string $documentId)
    {
        $resolvedIds = [];
        return Sales_Controller_Document_Abstract::createPrecursorTree($documentModel, [$documentId], $resolvedIds, [
                Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                    Sales_Model_Document_Abstract::FLD_POSITIONS => [],
                ],
            ])->toArray();
    }

    public function getApplicableBoilerplates(string $type, string $date = null, string $customerId = null, string $category = null, string $language = null)
    {
        if ($date) {
            $date = new Tinebase_DateTime($date);
        }
        $result = Sales_Controller_Boilerplate::getInstance()->getApplicableBoilerplates($type, $date, $customerId, $category, $language);
        return [
            'totalcount' => $result->count(),
            'results' => array_values($this->_multipleRecordsToJson($result)),
        ];
    }
    
    /*************************** offer functions *****************************/

    public function createPaperSlip(string $model, string $documentId)
    {
        if (!($stream = fopen('php://memory', 'w+'))) {
            throw new Tinebase_Exception_Backend('could not create memory stream');
        }

        /** @var Tinebase_Record_Interface $model */
        $docCtrl = $model::getConfiguration()->getControllerInstance();
        /** @var Sales_Model_Document_Abstract $preExportDocument */
        $preExportDocument = $docCtrl->get($documentId);

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel($model, [
            ['field' => 'id', 'operator' => 'equals', 'value' => $documentId]
        ]);
        $doc = new Sales_Export_DocumentPdf($filter, null, ['definitionId' => Tinebase_ImportExportDefinition::getInstance()->getByName(
            'document_' . strtolower(preg_replace('/^Sales_Model_Document_/', '', $model) .'_pdf'))->getId()]);
        $doc->generate();
        $doc->write($stream);
        rewind($stream);

        $transaction = Tinebase_RAII::getTransactionManagerRAII();

        /** @var Sales_Model_Document_Abstract $document */
        $document = $docCtrl->get($documentId);
        Tinebase_Record_Expander::expandRecord($document);
        if ($preExportDocument->seq < $document->seq) {
            throw new Tinebase_Exception_ConcurrencyConflict('document seq increase during export, please try again');
        }

        $name = Tinebase_Model_Tree_Node::sanitizeName(($document->{Sales_Model_Document_Abstract::FLD_DOCUMENT_DATE} ?? Tinebase_DateTime::now())->format('Y-m-d') . '_' .
            ($document->isBooked() ? '' : 'Proforma-') .
            ($document->isBooked() || !$document->has(Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER) ?
                $document->{Sales_Model_Document_Abstract::FLD_DOCUMENT_NUMBER} :
                $document->{Sales_Model_Document_Invoice::FLD_DOCUMENT_PROFORMA_NUMBER}) .
            ($document->{Sales_Model_Document_Abstract::FLD_DOCUMENT_TITLE} ?
                '-' . $document->{Sales_Model_Document_Abstract::FLD_DOCUMENT_TITLE} : '') . '.pdf');

        if ($node = $document->attachments->find('name', $name)) {
            $document->attachments->removeRecord($node);
        }
        $document->attachments->addRecord(new Tinebase_Model_Tree_Node(['name' => $name, 'tempFile' => $stream], true));
        $result = $this->_recordToJson($docCtrl->update($document));

        $transaction->release();
        return $result;
    }

    public function createFollowupDocument(array $documentTransition): array
    {
        /** @var Sales_Model_Document_Transition $documentTransition */
        $documentTransition = $this->_jsonToRecord($documentTransition, Sales_Model_Document_Transition::class);

        return $this->_recordToJson(
            Sales_Controller_Document_Abstract::executeTransition($documentTransition)
        );
    }

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchOffers($filter, $paging)
    {
        return $this->_search($filter, $paging, Sales_Controller_Offer::getInstance(), 'Sales_Model_OfferFilter', array('Sales_Model_Customer'));
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getOffer($id)
    {
        return $this->_get($id, Sales_Controller_Offer::getInstance());
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @param  boolean $duplicateCheck
     *
     * @return array created/updated record
     */
    public function saveOffer($recordData, $duplicateCheck)
    {
        return $this->_save($recordData, Sales_Controller_Offer::getInstance(), 'Offer');
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return string
     */
    public function deleteOffers($ids)
    {
        return $this->_delete($ids, Sales_Controller_Offer::getInstance());
    }

    /**
     * @param $id Invoice Id
     * @return bool|Sales_Model_Invoice|Tinebase_Record_Interface
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function createTimesheetForInvoice($id)
    {
        $invoice = Sales_Controller_Invoice::getInstance()->createTimesheetFor($id);
        if (! $invoice) {
            throw new Tinebase_Exception_SystemGeneric('Timesheet could not be created');
        }
        return $this->getInvoice($invoice->getId());
    }
}
