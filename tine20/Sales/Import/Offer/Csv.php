<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christian Feitl<c.feitl@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * csv import class for the Sales
 *
 * @package     Sales
 * @subpackage  Import
 *
 */
class Sales_Import_Offer_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * additional config options
     *
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id' => '',
    );

    /**
     * add some more values (container id)
     *
     * @return array
     */
    protected function _addData()
    {
        $result['container_id'] = $this->_options['container_id'];
        return $result;
    }

    /**
     * @param array $_data
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _doConversions($_data)
    {
        $result = parent::_doConversions($_data);
        $result = $this->_setCustomers($result);
        
        return $result;
    }

    /**
     * @param $result
     * @return mixed
     * @throws Tinebase_Exception_InvalidArgument
     * Resolve customers 
     */
    protected function _setCustomers($result)
    {
        if (!empty($result['customer'])) {
            $customers = Sales_Controller_Customer::getInstance()->getAll();
            foreach ($customers as $customer) {
                if ($customer['name'] == $result['customer']) {
                    $customer_id = $customer['id'];
                    $result['relations'] = array(
                        array(
                            'own_model' => 'Sales_Model_Offer',
                            'own_backend' => Tasks_Backend_Factory::SQL,
                            'own_id' => NULL,
                            'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
                            'related_model' => 'Sales_Model_Customer',
                            'related_backend' => Tasks_Backend_Factory::SQL,
                            'related_id' => $customer_id,
                            'type' => 'OFFER'
                        ));
                }
            }
        }
        return $result;
    }
}