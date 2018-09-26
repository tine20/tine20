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
class Sales_Import_OrderConfirmation_Csv extends Tinebase_Import_Csv_Abstract
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
        $result = $this->_setOffer($result);
        $result = $this->_setContract($result);
        return $result;
    }

    /**
     * @param $result
     * @return mixed
     * @throws Tinebase_Exception_InvalidArgument
     * Resolve customers
     */
    protected function _setOffer($result)
    {
        if (!empty($result['offer'])) {
            $offers = Sales_Controller_Offer::getInstance()->getAll();
            foreach ($offers as $offer) {
                if ($offer['title'] == $result['offer']) {
                    $offer_id = $offer['id'];
                    $result['relations'] = array(
                        array(
                            'own_model' => 'Sales_Model_OrderConfirmation',
                            'own_backend' => Tasks_Backend_Factory::SQL,
                            'own_id' => NULL,
                            'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
                            'related_model' => 'Sales_Model_Offer',
                            'related_backend' => Tasks_Backend_Factory::SQL,
                            'related_id' => $offer_id,
                            'type' => 'OFFER'
                        ));
                }
            }
        }
        return $result;
    }

    protected function _setContract($result)
    {
        if (!empty($result['contract'])) {
            $contracts = Sales_Controller_Contract::getInstance()->getAll();
            foreach ($contracts as $contract) {
                if ($contract['title'] == $result['contract']) {
                    $contract_id = $contract['id'];
                    $result['relations'][] = 
                        array(
                            'own_model' => 'Sales_Model_OrderConfirmation',
                            'own_backend' => Tasks_Backend_Factory::SQL,
                            'own_id' => NULL,
                            'related_degree' => Tinebase_Model_Relation::DEGREE_SIBLING,
                            'related_model' => 'Sales_Model_Contract',
                            'related_backend' => Tasks_Backend_Factory::SQL,
                            'related_id' => $contract_id,
                            'type' => 'CONTRACT'
                        );
                }
            }
        }
        return $result;
    }
}