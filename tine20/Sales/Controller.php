<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Sales Controller (composite)
 * 
 * @package Sales
 * @subpackage  Controller
 */
class Sales_Controller extends Tinebase_Controller_Event
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Sales';
    
    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'Sales_Model_Contract';
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct()
    {
    }

    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * holds self
     * @var Sales_Controller
     */
    private static $_instance = NULL;
    
    /**
     * Valid config keys for this application
     * @var array
     */
    private static $_configKeys;
    
    /**
     * config defaults
     * @var array
     */
    private static $_configKeyDefaults;
    
    /**
     * singleton
     *
     * @return Sales_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Sales_Controller();
        }
        return self::$_instance;
    }
    
  
    /**
     * returns the config for this app
     * 
     * @return array
     */
    public function getConfig()
    {
        if (! Tinebase_Core::getUser()->hasRight('Sales', 'admin')) {
            throw new Tinebase_Exception_AccessDenied(_('You do not have admin rights on Sales'));
        }
        
        $properties = Sales_Config::getProperties();
        
        $result = array();
        
        foreach ($properties as $propertyName => $propertyOptions) {
            if (isset($propertyOptions['setByAdminModule'])) {
                $result[$propertyName] = Sales_Config::getInstance()->get($propertyName, $propertyOptions['default']);
            }
        }
        
        return $result;
    }

    
    /**
     * save Sales settings
     *
     * @param array config
     * @return array
     */
    public function setConfig($config)
    {
        if (! Tinebase_Core::getUser()->hasRight('Sales', 'admin')) {
            throw new Tinebase_Exception_AccessDenied(_('You do not have admin rights on Sales'));
        }

        Sales_Controller_Customer::validateCurrencyCode($config['ownCurrency']);
        
        $properties = Sales_Config::getProperties();
        
        foreach ($config as $configName => $configValue) {
            if (!isset($properties[$configName])) {
                continue;
            }
            
            if (!isset($properties[$configName]['setByAdminModule'])) {
                continue;
            }
            
            Sales_Config::getInstance()->set($configName, $configValue);
        }
        
        return $this->getConfig();
    }

    /**
     * get core data for this application
     *
     * @return Tinebase_Record_RecordSet
     */
    public function getCoreDataForApplication()
    {
        $result = parent::getCoreDataForApplication();
        $application = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        
        $result->addRecord(new CoreData_Model_CoreData(array(
            'id' => 'cs_boilerplate',
            'application_id' => $application,
            'model' => 'Sales_Model_Boilerplate',
            'label' => 'Boilerplate' // _('Boilerplate')
        )));

        return $result;
    }
    
    public function createUpdatePostalAddress($contact)
    {
        $relations = Tinebase_Relations::getInstance()->getRelations(Addressbook_Model_Contact::class,'Sql', $contact->getId());
        $customer = $relations->filter('type', 'CONTACTCUSTOMER')->getFirstRecord();

        if ($customer) {
            $postal = Sales_Controller_Address::getInstance()->search(Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Address::class, [
                ['field' => Sales_Model_Address::FLD_CUSTOMER_ID, 'operator' => 'equals', 'value' => $customer->related_id],
                ['field' => Sales_Model_Address::FLD_TYPE, 'operator' => 'equals', 'value' => 'postal'],
            ]))->getFirstRecord();

            if ($postal) {
                Sales_Controller_Address::getInstance()->contactToCustomerAddress($postal, $contact);
            } else {
                $defaultLang = $this->getContactDefaultLanguage($contact);
                
                $postal = new Sales_Model_Address(array(
                    'customer_id' => $customer->related_id,
                    'name' => $contact->n_given ?: $customer->related_record->name,
                    'street' =>  $contact->adr_one_street,
                    'postalcode' => $contact->adr_one_postalcode,
                    'locality' => $contact->adr_one_locality,
                    'countryname' => $contact->adr_one_countryname,
                    'prefix1' => $customer->related_record->name == $contact->n_fn ? '' : $contact->n_fn,
                    'language' => $defaultLang,
                ));

                Sales_Controller_Address::getInstance()->create($postal);
            }
        }
    }
    
    public function updateBillingAddress($contact)
    {
        $contactRelations = Tinebase_Relations::getInstance()->getRelations('Addressbook_Model_Contact', 'Sql', $contact->getId());
        $billingAddress = $contactRelations->filter('type', 'CONTACTADDRESS');

        if (count($billingAddress) >= 1) {
            //This contact already has billing address relations
            foreach ($billingAddress as $address) {
                Sales_Controller_Address::getInstance()->contactToCustomerAddress($address->related_record, $contact);
            }
        }
    }
    
    public function getContactDefaultLanguage($contact)
    {
        $defaultLang = Sales_Config::getInstance()->{Sales_Config::LANGUAGES_AVAILABLE}->default;
        foreach (Sales_Config::getInstance()->{Sales_Config::LANGUAGES_AVAILABLE}->records as $language) {
            if ($contact->language == $language->id) {
                $defaultLang = $contact->language;
            }
        }
        return $defaultLang;
    }

    /**
     * event handler function
     *
     * all events get routed through this function
     *
     * @param Tinebase_Event_Abstract $_eventObject the eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        switch (get_class($_eventObject)) {
            case Addressbook_Event_CreateContact::class:
                $this->createUpdatePostalAddress($_eventObject->createdContact);
                break;
            case Addressbook_Event_InspectContactAfterUpdate::class:
                $this->createUpdatePostalAddress($_eventObject->updatedContact);
                $this->updateBillingAddress($_eventObject->updatedContact);
                break;
        }
    }
}
