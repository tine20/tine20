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
    private function __construct() {
        $this->_applicationName = 'Sales';
        $this->_backend = new Sales_Backend_Customer();
        $this->_modelName = 'Sales_Model_Customer';
        $this->_doContainerACLChecks = FALSE;
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
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->_setNextNumber($_record);
        
        self::validateCurrencyCode($_record->currency);
    }
    
    /**
     * inspects delete action
     *
     * @param array $_ids
     * @return array of ids to actually delete
     */
    protected function _inspectDelete(array $_ids)
    {
        $filter = new Sales_Model_AddressFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'customer_id', 'operator' => 'in', 'value' => $_ids)));
        
        $addressController = Sales_Controller_Address::getInstance();
        $addressController->delete($addressController->search($filter, NULL, FALSE, TRUE));

        return $_ids;
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
            foreach($postalAddressRecord as $field => $value) {
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
        foreach($resultSet as &$result) {
            $result = $this->resolveVirtualFields($result);
        }
        
        return $resultSet;
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
     *
     * @todo $_record->contracts should be a Tinebase_Record_RecordSet
     * @todo use getMigration()
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        self::validateCurrencyCode($_record->currency);
        
        $config = $_record::getConfiguration()->recordsFields;
        
        if ($_record->number != $_oldRecord->number) {
            $this->_setNextNumber($_record);
        }
        
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
                if (! Tinebase_Core::getUser()->hasRight('Sales', Sales_Acl_Rights::MANAGE_CUSTOMERS)) {
                    throw new Tinebase_Exception_AccessDenied("You don't have the right to manage customers!");
                }
                break;
            default;
            break;
        }
    }
}
