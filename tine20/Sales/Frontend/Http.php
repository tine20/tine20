<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * This class handles all Http requests for the Sales application
 *
 * @package     Sales
 * @subpackage  Frontend
 */
class Sales_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    /**
     * application name
     * 
     * @var string
     */
    protected $_applicationName = 'Sales';
    
    /**
     * export customers
     *
     * @param string $filter JSON encoded string with employee ids for multi export or employee filter
     * @param string $options format or export definition id
     */
    public function exportCustomers($filter, $options)
    {
        $decodedFilter = Zend_Json::decode($filter);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Export filter: ' . print_r($decodedFilter, TRUE));
        }
    
        if (! is_array($decodedFilter)) {
            $decodedFilter = array(array('field' => 'id', 'operator' => 'equals', 'value' => $decodedFilter));
        }
    
        $filter = new Sales_Model_CustomerFilter($decodedFilter);
        parent::_export($filter, Zend_Json::decode($options), Sales_Controller_Customer::getInstance());
    }
    
    /**
     * export invoices
     *
     * @param string $filter JSON encoded string with employee ids for multi export or employee filter
     * @param string $options format or export definition id
     */
    public function exportInvoices($filter, $options)
    {
        $decodedFilter = Zend_Json::decode($filter);
    
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Export filter: ' . print_r($decodedFilter, TRUE));
        }
    
        if (! is_array($decodedFilter)) {
            $decodedFilter = array(array('field' => 'id', 'operator' => 'equals', 'value' => $decodedFilter));
        }
    
        $filter = new Sales_Model_InvoiceFilter($decodedFilter);
        parent::_export($filter, Zend_Json::decode($options), Sales_Controller_Invoice::getInstance());
    }
    

    /**
     * export product aggregates
     *
     * This is not a default Export! This exports the invoice positions holding product aggregates.
     *
     * @param string $filter JSON encoded string with ids for multi export or filter
     * @param string $options format or export definition id
     */
    public function exportProductAggregates($filter, $options)
    {
        $decodedFilter = Zend_Json::decode($filter);
    
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Export filter: ' . print_r($decodedFilter, TRUE));
        }
    
        if (! is_array($decodedFilter)) {
            $decodedFilter = array(array('field' => 'id', 'operator' => 'equals', 'value' => $decodedFilter));
        }
    
        $filter = new Sales_Model_InvoicePositionFilter($decodedFilter);
        parent::_export($filter, Zend_Json::decode($options), Sales_Controller_InvoicePosition::getInstance());
    }
}
