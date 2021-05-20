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
class Sales_Controller_Customer extends Sales_Controller_NumberableAbstract
{
    /**
     * delete or just set is_delete=1 if record is going to be deleted
     * - legacy code -> remove that when all backends/applications are using the history logging
     *
     * @var boolean
     */
    protected $_purgeRecords = FALSE;

    /**
     * duplicate check fields / if this is NULL -> no duplicate check
     *
     * @var array
     */
    protected $_duplicateCheckFields = array(array('name'));

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'Sales';
        $this->_backend = new Sales_Backend_Customer();
        $this->_modelName = 'Sales_Model_Customer';
        $this->_doContainerACLChecks = FALSE;
        // TODO this should be done automatically if model has customfields (hasCustomFields)
        $this->_resolveCustomFields = true;
    }

    /**
     * holds the instance of the singleton
     *
     * @var Sales_Controller_Customer
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return Sales_Controller_Customer
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * validates if the given code is a valid ISO 4217 code
     *
     * @param string $code
     * @throws Sales_Exception_UnknownCurrencyCode
     */
    public static function validateCurrencyCode($code)
    {
        try {
            $currency = new Zend_Currency($code, 'en_GB');
        } catch (Zend_Currency_Exception $e) {
            throw new Sales_Exception_UnknownCurrencyCode();
        }
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->_setNextNumber($_record);
        $this->_resolvePostalAddress($_record);
        self::validateCurrencyCode($_record->currency);
    }

    /**
     * inspect creation of one record (after create)
     *
     * @param Tinebase_Record_Interface $_createdRecord
     * @param Tinebase_Record_Interface $_record
     * @return  void
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _inspectAfterCreate($_createdRecord, $_record)
    {
        // record finally have id here , create postal address needs record_id.
        $this->_resolvePostalAddress($_record);
        $this->_handleAddresses($_record);
    }

    /**
     * resolves all virtual fields for the customer
     *
     * @param array $customer
     * @return array with property => value
     */
    public function resolveVirtualFields($customer)
    {
        $addressController = Sales_Controller_Address::getInstance();
        $filter = new Sales_Model_AddressFilter(array(array('field' => 'type', 'operator' => 'equals', 'value' => 'postal')));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'customer_id', 'operator' => 'equals', 'value' => $customer['id'])));

        $postalAddressRecord = $addressController->search($filter)->getFirstRecord();
        
        if ($postalAddressRecord) {
            $customer['postal_id'] = $postalAddressRecord->toArray();
            foreach ($postalAddressRecord as $field => $value) {
                $customer[('adr_' . $field)] = $value;
            }
        }
        
        return $customer;
    }

    /**
     * @param array $resultSet
     *
     * @return array
     */
    public function resolveMultipleVirtualFields($resultSet)
    {
        foreach ($resultSet as &$result) {
            $result = $this->resolveVirtualFields($result);
        }

        return $resultSet;
    }

    /**
     * inspect update of one record (before update)
     *
     * @param Tinebase_Record_Interface $_record the update record
     * @param Tinebase_Record_Interface $_oldRecord the current persistent record
     * @return  void
     *
     * @todo $_record->contracts should be a Tinebase_Record_RecordSet
     * @todo use getMigration()
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        $this->handleExternAndInternId($_record);
        $this->_resolvePostalAddress($_record);
        
        self::validateCurrencyCode($_record->currency);

        if ($_record->number != $_oldRecord->number) {
            $this->_setNextNumber($_record, TRUE);
        }
    }

    /**
     * inspect update of one record (before update)
     *
     * @param Tinebase_Record_Interface $updatedRecord
     * @param Tinebase_Record_Interface $record
     * @param Tinebase_Record_Interface $currentRecord
     * @return  void
     *
     * @throws Sales_Exception_DuplicateNumber
     * @throws Sales_Exception_UnknownCurrencyCode
     * @todo $_record->contracts should be a Tinebase_Record_RecordSet
     * @todo use getMigration()
     */
    protected function _inspectAfterUpdate($updatedRecord, $record, $currentRecord)
    {
        $this->handleExternAndInternId($record);
        $this->_handleAddresses($record);
    }

    /**d
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
                if (!Tinebase_Core::getUser()->hasRight('Sales', Sales_Acl_Rights::MANAGE_CUSTOMERS)) {
                    throw new Tinebase_Exception_AccessDenied("You don't have the right to manage customers!");
                }
                break;
            default;
                break;
        }

        parent::_checkRight($_action);
    }

    /**
     * handleExternAndInternId
     *
     * @param Tinebase_Record_Interface $_record the record
     * @return  Tinebase_Record_Interface
     *
     */
    protected function handleExternAndInternId($_record) {
        //its only for the occasion after resolveVirtualFields
        foreach (array('cpextern_id', 'cpintern_id') as $prop) {
            if (isset($_record[$prop]) && is_array($_record[$prop])) {
                $_record[$prop] = $_record[$prop]['id'];
            }
        }
        
        return $_record;
    }
    
    /**
     * handle address
     * save properties in record
     * @param Tinebase_Record_Interface $_record the record
     * @return  Tinebase_Record_Interface
     *
     */
    protected function _resolvePostalAddress($_record) {
        $postalAddress = [];
        
        foreach( $_record as $field => $value) {
            if (strpos($field, 'adr_') !== FALSE && ! empty($value)) {
                $postalAddress[substr($field, 4)] = $value;
            }
        }
        //its only for the occasion after resolveVirtualFields
        if (!isset($postalAddress['seq']) && isset($_record['postal_id']) && isset($_record['postal_id']['seq'])) {
            $postalAddress['seq'] = $_record['postal_id']['seq'];
        }

        $postalAddress['customer_id'] = isset($_record['id']) ? $_record['id'] : $_record['name'];
        $postalAddress['type'] = 'postal';

        $_record['postal_id'] = $postalAddress;
        return $_record;
    }

    /**
     * create postal address record after creat customer
     * 
     * - create billing address adter postal address created
     * - billing address equal to postal address
     *
     * @param Tinebase_Record_Interface $_record
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _handleAddresses($_record)
    {
        $filter = new Sales_Model_AddressFilter(array(array('field' => 'type', 'operator' => 'equals', 'value' => 'postal')));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'customer_id', 'operator' => 'equals', 'value' => $_record['id'])));

        $postalAddressRecord = Sales_Controller_Address::getInstance()->search($filter)->getFirstRecord();

        // create if none has been found
        if (! $postalAddressRecord) {
            $postalAddressRecord = Sales_Controller_Address::getInstance()->create(new Sales_Model_Address($_record['postal_id']));
            //create billing address once the postal address created
            if ($postalAddressRecord) {
                $billingAddress = [
                    'customer_id' => $postalAddressRecord['customer_id'],
                    'type' => 'billing',
                    'prefix1' => $postalAddressRecord['prefix1'],
                    'prefix2' => $postalAddressRecord['prefix2'],
                    'street' => $postalAddressRecord['street'],
                    'pobox' => $postalAddressRecord['pobox'],
                    'postalcode' => $postalAddressRecord['postalcode'],
                    'locality' => $postalAddressRecord['locality'],
                    'region' => $postalAddressRecord['region'],
                    'countryname' => $postalAddressRecord['countryname'],
                    'custom1' => Tinebase_Record_Abstract::generateUID(5),
                ];

                Sales_Controller_Address::getInstance()->create(new Sales_Model_Address($billingAddress));
            }
            
        } else {
            // update if it has changed
            $postalAddress = $_record['postal_id'];
            $postalAddress['id'] = $postalAddressRecord->getId();
            $postalAddressRecordToUpdate = new Sales_Model_Address($postalAddress);
            $diff = $postalAddressRecord->diff($postalAddressRecordToUpdate);
            if (! empty($diff)) {
                $postalAddressRecord = Sales_Controller_Address::getInstance()->update($postalAddressRecordToUpdate);
            }
        }
    }
    
}
