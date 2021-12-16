<?php declare(strict_types=1);

/**
 * Abstract Document controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Abstract Document controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 */
abstract class Sales_Controller_Document_Abstract extends Tinebase_Controller_Record_Abstract
{

    /**
     * inspect creation of one record (before create)
     *
     * @param   Sales_Model_Document_Abstract $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        if (!empty($_record->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID})) {
            $_record->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID}
                ->{Sales_Model_Address::FLD_CUSTOMER_ID} = null;
        }
        parent::_inspectBeforeCreate($_record);
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        if (!empty($_record->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID})) {
            $_record->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID}
                ->{Sales_Model_Address::FLD_CUSTOMER_ID} = null;
            if ($address = Sales_Controller_Document_Address::getInstance()->search(
                    Tinebase_Model_Filter_FilterGroup::getFilterForModel(Sales_Model_Document_Address::class, [
                        ['field' => 'id', 'operator' => 'equals', 'value' => $_record->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID}->getId()],
                        ['field' => 'document_id', 'operator' => 'equals', 'value' => null],
                        ['field' => 'customer_id', 'operator' => 'not', 'value' => null],
                    ]))->getFirstRecord()) {
                $_record->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID}->setId($address->{Sales_Model_Address::FLD_ORIGINAL_ID});
                $_record->{Sales_Model_Document_Abstract::FLD_RECIPIENT_ID}->{Sales_Model_Address::FLD_ORIGINAL_ID} = null;
            }
        }

        parent::_inspectBeforeUpdate($_record, $_oldRecord);
    }
}
