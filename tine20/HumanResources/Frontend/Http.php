<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * This class handles all Http requests for the HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Frontend
 */
class HumanResources_Frontend_Http extends Tinebase_Frontend_Http_Abstract
{
    protected $_applicationName = 'HumanResources';

    /**
     * export employee
     *
     * @param string $filter JSON encoded string with employee ids for multi export or employee filter
     * @param string $options format or export definition id
     */
    public function exportEmployees($filter, $options)
    {
        $decodedFilter = Zend_Json::decode($filter);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Export filter: ' . print_r($decodedFilter, TRUE));
    
        if (! is_array($decodedFilter)) {
            $decodedFilter = array(array('field' => 'id', 'operator' => 'equals', 'value' => $decodedFilter));
        }
    
        $filter = new HumanResources_Model_EmployeeFilter($decodedFilter);
        parent::_export($filter, Zend_Json::decode($options), HumanResources_Controller_Employee::getInstance());
    }
}
