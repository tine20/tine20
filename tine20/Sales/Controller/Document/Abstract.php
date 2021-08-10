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
        parent::_inspectBeforeCreate($_record);

        // this maybe null for offers! model validation will take care of that, we just deal with what we get or not get
        if (null !== $_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID}) {
            if (!$_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID} instanceof Sales_Model_Document_Customer) {
                throw new Tinebase_Exception_UnexpectedValue(Sales_Model_Document_Abstract::FLD_CUSTOMER_ID .
                    ' is not instance of ' . Sales_Model_Document_Customer::class);
            }
            $_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID}->setId(null);
            $_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID} = Sales_Controller_Document_Customer::getInstance()
                ->create($_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID})->getId();
        }
    }
}
