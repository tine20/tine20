<?php declare(strict_types=1);

/**
 * Order Document controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Order Document controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Document_Order extends Sales_Controller_Document_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    protected $_documentStatusConfig = Sales_Config::DOCUMENT_ORDER_STATUS;
    protected $_documentStatusTransitionConfig = Sales_Config::DOCUMENT_ORDER_STATUS_TRANSITIONS;
    protected $_documentStatusField = Sales_Model_Document_Order::FLD_ORDER_STATUS;
    protected $_oldRecordBookWriteableFields = [
        Sales_Model_Document_Order::FLD_ORDER_STATUS,
        Sales_Model_Document_Order::FLD_COST_CENTER_ID,
        Sales_Model_Document_Order::FLD_COST_BEARER_ID,
        Sales_Model_Document_Order::FLD_DESCRIPTION,
        Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID,
        Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID,
        'tags', 'attachments', 'relations',
    ];

    protected $_bookRecordRequiredFields = [
        Sales_Model_Document_Order::FLD_CUSTOMER_ID,
        Sales_Model_Document_Order::FLD_RECIPIENT_ID,
    ];


    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Sales_Model_Document_Order::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Sales_Model_Document_Order::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Sales_Model_Document_Order::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param   Sales_Model_Document_Abstract $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        // the recipient address is not part of a customer, we enforce that here
        if (!empty($_record->{Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID})) {
            $_record->{Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID}
                ->{Sales_Model_Address::FLD_CUSTOMER_ID} = null;
        }
        if (!empty($_record->{Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID})) {
            $_record->{Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID}
                ->{Sales_Model_Address::FLD_CUSTOMER_ID} = null;
        }

        parent::_inspectBeforeCreate($_record);
    }

    /**
     * @param Sales_Model_Document_Abstract $_record
     * @param Sales_Model_Document_Abstract $_oldRecord
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        if (!empty($_record->{Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID})) {
            // the recipient address is not part of a customer, we enforce that here
            $_record->{Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID}
                ->{Sales_Model_Address::FLD_CUSTOMER_ID} = null;

            // if the recipient address is a denormalized customer address, we denormalize it again from the original address
            if ($address = Sales_Controller_Document_Address::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Document_Address::class, [
                    ['field' => 'id', 'operator' => 'equals', 'value' => $_record->{Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID}->getId()],
                    ['field' => 'document_id', 'operator' => 'equals', 'value' => null],
                    ['field' => 'customer_id', 'operator' => 'not', 'value' => null],
                ]))->getFirstRecord()) {
                $_record->{Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID}->setId($address->{Sales_Model_Address::FLD_ORIGINAL_ID});
                $_record->{Sales_Model_Document_Order::FLD_DELIVERY_RECIPIENT_ID}->{Sales_Model_Address::FLD_ORIGINAL_ID} = null;
            }
        }
        if (!empty($_record->{Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID})) {
            // the recipient address is not part of a customer, we enforce that here
            $_record->{Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID}
                ->{Sales_Model_Address::FLD_CUSTOMER_ID} = null;

            // if the recipient address is a denormalized customer address, we denormalize it again from the original address
            if ($address = Sales_Controller_Document_Address::getInstance()->search(
                Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Document_Address::class, [
                    ['field' => 'id', 'operator' => 'equals', 'value' => $_record->{Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID}->getId()],
                    ['field' => 'document_id', 'operator' => 'equals', 'value' => null],
                    ['field' => 'customer_id', 'operator' => 'not', 'value' => null],
                ]))->getFirstRecord()) {
                $_record->{Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID}->setId($address->{Sales_Model_Address::FLD_ORIGINAL_ID});
                $_record->{Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID}->{Sales_Model_Address::FLD_ORIGINAL_ID} = null;
            }
        }

        parent::_inspectBeforeUpdate($_record, $_oldRecord);
    }
}
