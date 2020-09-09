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
class Sales_Import_PurchaseInvoice_Csv extends Tinebase_Import_Csv_Abstract
{
    /**
     * additional config options
     *
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id' => '',
        'dates'        => array('date','due_at','payed_at','discount_until')
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
        $result = $this->_setCostCenter($result);
        $result = $this->_setContact($result);
        $result = $this->_setSupplier($result);

        return $result;
    }
    /**
     * @param $result
     * @return mixed
     * @throws Tinebase_Exception_InvalidArgument
     * Resolve CostCenter
     */
    protected function _setCostCenter($result)
    {
        if (!empty($result['costcenter'])) {
            $costCenters = Sales_Controller_CostCenter::getInstance()->getAll();
            foreach ($costCenters as $costCenter) {
                if ($costCenter['remark'] == $result['costcenter']) {
                    $result['relations'][] =
                        array(
                            'own_model' => 'Sales_Model_PurchaseInvoice',
                            'own_backend' => Tasks_Backend_Factory::SQL,
                            'own_id' => NULL,
                            'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
                            'related_model' => 'Sales_Model_CostCenter',
                            'related_backend' => Tasks_Backend_Factory::SQL,
                            'related_id' => $costCenter['id'],
                            'type' => 'LEAD_COST_CENTER'
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
     * Set Contact for purchaseinvoice
     */
    protected function _setContact($result)
    {
        if (!empty($result['contact'])) {
            $users = Addressbook_Controller_Contact::getInstance()->getAll();
            foreach ($users as $user) {
                if ($user['n_fileas'] == $result['contact']) {
                    $result['relations'][] = array(
                        'own_model' => 'Sales_Model_PurchaseInvoice',
                        'own_backend' => Tasks_Backend_Factory::SQL,
                        'own_id' => NULL,
                        'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
                        'related_model' => 'Addressbook_Model_Contact',
                        'related_backend' => Tasks_Backend_Factory::SQL,
                        'related_id' => $user['id'],
                        'type' => 'APPROVER'
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
     * set Supplier relation
     */
    protected function _setSupplier($result)
    {
        if (!empty($result['supplier'])) {
            $suppliers = Sales_Controller_Supplier::getInstance()->getAll();
            foreach ($suppliers as $supplier) {
                if ($supplier['name'] == $result['supplier']) {
                    $result['relations'][] = array(
                        'own_model' => 'Sales_Model_PurchaseInvoice',
                        'own_backend' => Tasks_Backend_Factory::SQL,
                        'own_id' => NULL,
                        'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
                        'related_model' => 'Sales_Model_Supplier',
                        'related_backend' => Tasks_Backend_Factory::SQL,
                        'related_id' => $supplier['id'],
                        'type' => 'SUPPLIER'
                    );
                }
            }
        }
        return $result;
    }

}