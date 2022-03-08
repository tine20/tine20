<?php
/**
 * address controller for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * address controller class for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Address extends Tinebase_Controller_Record_Abstract
{
    /**
     * delete or just set is_delete=1 if record is going to be deleted
     * - legacy code -> remove that when all backends/applications are using the history logging
     *
     * @var boolean
     */
    protected $_purgeRecords = FALSE;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
        $this->_applicationName = 'Sales';
        $this->_backend = new Sales_Backend_Address();
        $this->_modelName = 'Sales_Model_Address';
        $this->_doContainerACLChecks = FALSE;
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var Sales_Controller_Address
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Sales_Controller_Address
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    /**
     * resolves all virtual fields for the address
     *
     * @param array $address
     * @return array with property => value
     */
    public function resolveVirtualFields($address)
    {
        if (! isset($address['type'])) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' Invalid address for resolving: ' . print_r($address, true));
            
            return $address;
        }
        
        $ft = '';
        
        $i18n = Tinebase_Translation::getTranslation($this->_applicationName)->getAdapter();
        $type = $address['type'];
        
        $ft .= !empty($address['name_shorthand']) ? $address['name_shorthand'] : '';
        $ft .= !empty($address['name_shorthand']) ? ' => ' : '';
        $ft .= !empty($address['name']) ? $address['name'] : '';
        $ft .= !empty($address['name']) ? ' ' : '';
        $ft .= !empty($address['email']) ? $address['email'] : '';
        $ft .= !empty($address['email']) ? ' ' : '';
        $ft .= !empty($address['prefix1']) ? $address['prefix1'] : '';
        $ft .= !empty($address['prefix1']) && !empty($address['prefix2']) ? ' ' : '';
        $ft .= !empty($address['prefix2']) ? $address['prefix2'] : '';
        $ft .= !empty($address['prefix1']) || !empty($address['prefix2']) ? ', ' : '';
        
        $ft .= !empty($address['postbox']) ? $address['postbox'] : (!empty($address['street']) ? $address['street'] : '');
        $ft .= !empty($address['postbox']) || !empty($address['street']) ? ', ' : '';
        $ft .= !empty($address['postalcode']) ? $address['postalcode'] . ' ' : '';
        $ft .= !empty($address['locality']) ? $address['locality'] : '';
        $ft .= ' (';
        
        $ft .= $i18n->_($type);
        
        if ($type == 'billing') {
            $ft .= ' - ' . $address['custom1'];
        }
        
        $ft .= ')';
        
        $address['fulltext'] = $ft;
        
        return $address;
    }
    
    /**
     * @todo make this better, faster
     *
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
     * inspects delete action
     *
     * @param array $_ids
     * @return array of ids to actually delete
     * @throws Sales_Exception_DeleteUsedBillingAddress
     */
    protected function _inspectDelete(array $_ids)
    {
        $cc = Sales_Controller_Contract::getInstance();

        $filter = new Sales_Model_ContractFilter(array(array('field' => 'billing_address_id', 'operator' => 'in', 'value' => $_ids)));

        $contracts = $cc->search($filter);
    
        if ($contracts->count()) {
            $e = new Sales_Exception_DeleteUsedBillingAddress();
            $e->setContracts($contracts);
    
            throw $e;
        }
    
        return $_ids;
    }
    
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

        //Do not update Address Records with a relation to a contact from type CONTACTADDRESS
        $addressFields = [
            'name',
            'street',
            'postalcode',
            'locality',
            'countryname',
            'prefix1',
            'language',
        ];
        $relations = $_record->relations;

        if (!$relations) {
            return;
        }
        foreach ($relations as $relation) {
            if ($relation['type'] == 'CONTACTADDRESS') {
                $diff = $_record->diff($_oldRecord)->diff;
                foreach ($diff as $key => $value) {
                    if (in_array($key, $addressFields) && $value !== null) {
                        throw new Tinebase_Exception_AccessDenied('It is not allowed to change an address that is linked to a contact.');
                    }
                }
            }
        }
    }


    /**
     * handle address
     * save properties in record
     * @param Tinebase_Record_Interface $_record the record
     * @return  Tinebase_Record_Interface
     *
     */
    public function resolvePostalAddress($_record) {
        $postalAddress = [];
        
        foreach( $_record as $field => $value) {
            if (strpos($field, 'adr_') !== FALSE) {
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

        $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Address::class, array(array('field' => 'type', 'operator' => 'equals', 'value' => 'postal')));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'customer_id', 'operator' => 'equals', 'value' => $_record['id'])));

        $postalAddressRecord = Sales_Controller_Address::getInstance()->search($filter)->getFirstRecord();

        // create if none has been found
        if (! $postalAddressRecord) {
            $postalAddressRecord = Sales_Controller_Address::getInstance()->create(new Sales_Model_Address($_record['postal_id']));
        } else {
            // update if it has changed
            $recordData = $_record->toArray();
            foreach ($postalAddressRecord as $field => $value) {
                if (array_key_exists("adr_$field", $recordData)) {
                    $postalAddressRecord[$field] = $recordData["adr_$field"];
                }
            }

            $postalAddressRecord = Sales_Controller_Address::getInstance()->update($postalAddressRecord);
        }
        
        return $_record;
    }

    /**
     * Update a Sales Address with data from a Contact
     * 
     * @param Sales_Model_Address $address
     * @param Addressbook_Model_Contact $contact
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_NotFound
     */
    public function contactToCustomerAddress(Sales_Model_Address $address, Addressbook_Model_Contact $contact)
    {
        $language = Sales_Controller::getInstance()->getContactDefaultLanguage($contact);
        $customer = Sales_Controller_Customer::getInstance()->get($address->customer_id);
        
        //Update Address
        $address->name =  $customer->name;
        $address->street = $contact->adr_one_street;
        $address->postalcode  = $contact->adr_one_postalcode;
        $address->locality = $contact->adr_one_locality;
        $address->region = $contact->adr_one_region;
        $address->countryname = $contact->adr_one_countryname;
        $address->prefix1 = $customer->name == $contact->n_fn ? '' : $contact->n_fn;
        $address->prefix2 = $contact->org_name;
        $address->language = $language;

        return Sales_Controller_Address::getInstance()->update($address);
    }
}
