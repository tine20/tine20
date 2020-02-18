<?php
/**
 * Sales Customer Ods generation class
 *
 * @package     Sales
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Sales Customer Ods generation class
 *
 * @package     Sales
 * @subpackage  Export
 *
 */
class Sales_Export_Ods_Customer extends Sales_Export_Ods_Abstract
{
    /**
     * default export definition name
     *
     * @var string
     */
    protected $_defaultExportname = 'customer_default_ods';

    /**
     * get record relations
     *
     * @var boolean
     */
    protected $_getRelations = FALSE;

    /**
     * all addresses (Sales_Model_Address) needed for the export
     *
     * @var Tinebase_Record_RecordSet
     */
    protected $_addresses = NULL;
    protected $_customerAddresses = array();
    protected $_specialFields = array('address', 'postal', 'billing', 'delivery');

    /**
     * all contacts (Addressbook_Model_Contact) needed for the export
     *
     * @var Tinebase_Record_RecordSet
     */
    protected $_contacts = NULL;
    
    /**
     * constructor (adds more values with Crm_Export_Helper)
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Controller_Record_Interface $_controller
     * @param array $_additionalOptions
     * @return void
    */
    public function __construct(Tinebase_Model_Filter_FilterGroup $_filter, Tinebase_Controller_Record_Interface $_controller = NULL, $_additionalOptions = array())
    {
        $this->_resolveAddresses($_filter, $_controller);
        parent::__construct($_filter, $_controller, $_additionalOptions);
    }

    /**
     * get export config
     *
     * @param array $_additionalOptions additional options
     * @return Zend_Config_Xml
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getExportConfig($_additionalOptions = array())
    {
        $config = parent::_getExportConfig($_additionalOptions);
        $count = $config->columns->column->count();

        foreach($this->_specialFieldDefinitions as $def) {
            $cfg = new Zend_Config(array('column' => array($count => $def)));
            $config->columns->merge($cfg);
            $count++;
        }
        
        $i18n = $this->_translate->getAdapter();
        
        // translate header
        foreach($config->columns->column as $index => $column) {
            $newConfig = $column->toArray();
            
            $newConfig['header'] = $i18n->translate($newConfig['header']);
            
            if (isset($newConfig['index']) && $newConfig['index'] > 0) {
                $newConfig['header'] .= ' (' . $newConfig['index'] . ')';
            }
            
            $cfg = new Zend_Config(array('column' => array($index => $newConfig)));
            $config->columns->merge($cfg);
        }

        return $config;
    }

    /**
     * resolve address records before setting headers, so we know how much addresses exist
     *
     * @param Sales_Model_CustomerFilter $filter
     * @param Sales_Controller_Customer $controller
     */
    protected function _resolveAddresses($filter, $controller)
    {
        $customers   = $controller->search($filter);
        $customerIds = $customers->id;
        $contactIds  = array_unique(array_merge($customers->cpextern_id, $customers->cpintern_id));
        
        unset($customers);

        $be = new Sales_Backend_Address();

        $this->_specialFieldDefinitions = array(array('header' => 'Postal Address', 'identifier' => 'postal_address', 'type' => 'postal'));

        foreach (array('billing', 'delivery') as $type) {
            $maxAddresses = $be->getMaxAddressesByType($customerIds, $type);
            $header = $type == 'billing' ? 'Billing Address' : 'Delivery Address';

            if ($maxAddresses > 0) {
                $i = 0;
                while ($i < $maxAddresses) {
                    $this->_specialFieldDefinitions[] = array('header' => $header, 'identifier' => $type . '_address' . ($i>0 ? $i+1 : ''), 'type' => $type, 'index' => $i+1);
                    $i++;
                }
            }
        }

        $filter = new Sales_Model_AddressFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(
            array('field' => 'customer_id', 'operator' => 'in', 'value' => $customerIds)
        ));
        
        $this->_addresses = $be->search($filter);
        
        $this->_contacts = Addressbook_Controller_Contact::getInstance()->getMultiple($contactIds);
    }

    /**
     * get special field value
     *
     * @param Tinebase_Record_Interface $_record
     * @param array $_param
     * @param string $_key
     * @param string $_cellType
     * @return string
     */
    protected function _getSpecialFieldValue(Tinebase_Record_Interface $_record, $_param, $_key = NULL, &$_cellType = NULL)
    {
        $customerId = $_record->getId();
        
        if (! isset($this->_customerAddresses[$customerId])) {
            $all = $this->_addresses->filter('customer_id', $customerId);
            $this->_addresses->removeRecords($all);
            $this->_customerAddresses[$customerId] = array(
                'postal'  => $all->filter('type', 'postal')->getFirstRecord(),
                'billing' => array('records' => $all->filter('type', 'billing'), 'index' => 0),
                'delivery' => array('records' => $all->filter('type', 'delivery'), 'index' => 0),
            );
        }

        switch ($_param['type']) {
            case 'postal':
                $address = $this->_customerAddresses[$customerId]['postal'];
                break;
            case 'address':
                $addresses = $this->_contacts->filter('id', $_record->{$_param['identifier']});
                return $addresses->getFirstRecord() ? $addresses->getFirstRecord()->n_fn : '';
            default:
                if (isset($this->_customerAddresses[$customerId][$_param['type']]['records'])) {
                    $address = $this->_customerAddresses[$customerId][$_param['type']]['records']->getByIndex($this->_customerAddresses[$customerId][$_param['type']]['index']);
                    $this->_customerAddresses[$customerId][$_param['type']]['index']++;
                }
        }

        return $address ? $this->_renderAddress($address, $_param['type']) : '';
    }

    /**
     * renders an address
     *
     * @param Sales_Model_Address $address
     * @param string $type
     */
    protected function _renderAddress($address, $type = NULL) {
        
        if (! $address) {
            return '';
        }
        
        $ret = array();

        foreach(array('prefix1', 'prefix2', 'street', 'postalcode', 'locality', 'region', 'countryname', 'pobox', 'custom1') as $prop) {
            if (isset($address->{$prop})) {
                $ret[] = $address->{$prop};
            }
        }
        
        return join(',', $ret);
    }
}
