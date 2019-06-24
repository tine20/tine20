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
class Sales_Import_Invoice_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * additional config options
     *
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id' => '',
        'dates'        => array('date','start_date','end_date')
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

        $result = $this->_setRelation($result);
        $result = $this->_setCostCenter($result);

        return $result;
    }

    /**
     * @param $result
     * @return mixed
     * @throws Tinebase_Exception_InvalidArgument
     * resolve and set the relations
     */
    protected function _setRelation($result)
    {
        if (!empty($result['contract'])) {
            $contracts = Sales_Controller_Contract::getInstance()->getAll();
            foreach ($contracts as $contract) {
                if ($contract['title'] == $result['contract']) {
                    $result['relations'] = array(
                        array(
                            'own_model' => 'Sales_Model_Invoice',
                            'own_backend' => Tasks_Backend_Factory::SQL,
                            'own_id' => NULL,
                            'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
                            'related_model' => 'Sales_Model_Contract',
                            'related_backend' => Tasks_Backend_Factory::SQL,
                            'related_id' => $contract['id'],
                            'type' => 'CONTRACT'
                        ));
                    $result['address_id'] = $contract['billing_address_id'];
                }
            }
            $addresses = Sales_Controller_Address::getInstance()->getAll();
            foreach ($addresses as $address) {
                if ($address['id'] == $result['address_id']) {
                    $result['relations'][] = array(
                        'own_model' => 'Sales_Model_Invoice',
                        'own_backend' => Tasks_Backend_Factory::SQL,
                        'own_id' => NULL,
                        'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
                        'related_model' => 'Sales_Model_Customer',
                        'related_backend' => Tasks_Backend_Factory::SQL,
                        'related_id' => $address['customer_id'],
                        'type' => 'CUSTOMER'
                    );
                }
            }
        }
        return $result;
    }

    /**
     * @param $result
     * @return mixed
     * @throws Tinebase_Exception_InvalidArgument
     * set the costcenter
     */
    protected function _setCostCenter($result)
    {
        if (!empty($result['costcenter'])) {
            $costCenters = Sales_Controller_CostCenter::getInstance()->getAll();
            foreach ($costCenters as $costCenter) {
                if ($costCenter['remark'] == $result['costcenter']) {
                    $result['costcenter_id'] = $costCenter['id'];
                }
            }
        }
        return $result;
    }
}