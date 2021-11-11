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
       /* if (null !== $_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID}) {
            if (!$_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID} instanceof Sales_Model_Document_Customer) {
                throw new Tinebase_Exception_UnexpectedValue(Sales_Model_Document_Abstract::FLD_CUSTOMER_ID .
                    ' is not instance of ' . Sales_Model_Document_Customer::class);
            }
            if ($_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID}->delivery) {
                foreach ($_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID}->delivery as $address) {
                    if (!$address->{Sales_Model_Document_Abstract::FLD_ORIGINAL_ID}) {
                        $address->{Sales_Model_Document_Abstract::FLD_ORIGINAL_ID} = $address->getId();
                    }
                    $address->setId(null);
                }
            }
            if (!$_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID}
                    ->{Sales_Model_Document_Abstract::FLD_ORIGINAL_ID}) {
                $_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID}
                    ->{Sales_Model_Document_Abstract::FLD_ORIGINAL_ID} =
                    $_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID}->getId();
            }
            $_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID}->setId(null);
            $_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID} = Sales_Controller_Document_Customer::getInstance()
                ->create($_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID})->getId();
        }*/
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        parent::_inspectBeforeUpdate($_record, $_oldRecord);

       /* if ($_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID} instanceof Sales_Model_Document_Customer) {
            if (!$_oldRecord->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID}) {
                $_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID} = Sales_Controller_Document_Customer::getInstance()
                    ->create($_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID})->getId();
            } elseif ($_oldRecord->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID} !== $_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID}->getId()) {
                Sales_Controller_Document_Customer::getInstance()->delete($_oldRecord->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID});
                $_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID} = Sales_Controller_Document_Customer::getInstance()
                    ->create($_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID})->getId();
            } else {
                Sales_Controller_Document_Customer::getInstance()
                    ->update($_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID});
            }
        } elseif (null === $_record->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID} && $_oldRecord->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID}) {
            Sales_Controller_Document_Customer::getInstance()->delete($_oldRecord->{Sales_Model_Document_Abstract::FLD_CUSTOMER_ID});
        }*/
    }

}
