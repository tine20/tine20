<?php declare(strict_types=1);

/**
 * Abstract DocumentPosition controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Abstract DocumentPosition controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 */
abstract class Sales_Controller_DocumentPosition_Abstract extends Tinebase_Controller_Record_Abstract
{

    /**
     * @return array<string>
     */
    public static function getDocumentModels(): array
    {
        return [
            Sales_Model_DocumentPosition_Delivery::class,
            Sales_Model_DocumentPosition_Invoice::class,
            Sales_Model_DocumentPosition_Offer::class,
            Sales_Model_DocumentPosition_Order::class,
        ];
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param   Sales_Model_SubProductMapping $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        if (0 === strlen((string) $_record->{Sales_Model_DocumentPosition_Abstract::FLD_TITLE}) &&
                0 === strlen((string) $_record->{Sales_Model_DocumentPosition_Abstract::FLD_DESCRIPTION})) {
            throw new Tinebase_Exception_Record_Validation(Sales_Model_DocumentPosition_Abstract::FLD_TITLE . ' and ' .
                Sales_Model_DocumentPosition_Abstract::FLD_DESCRIPTION . ' can not be both empty at the same time');
        }
        parent::_inspectBeforeCreate($_record);
    }

    /**
     * @param Sales_Model_SubProductMapping $_record
     * @param Sales_Model_SubProductMapping $_oldRecord
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        if (0 === strlen((string) $_record->{Sales_Model_DocumentPosition_Abstract::FLD_TITLE}) &&
                0 === strlen((string) $_record->{Sales_Model_DocumentPosition_Abstract::FLD_DESCRIPTION})) {
            throw new Tinebase_Exception_Record_Validation(Sales_Model_DocumentPosition_Abstract::FLD_TITLE . ' and ' .
                Sales_Model_DocumentPosition_Abstract::FLD_DESCRIPTION . ' can not be both empty at the same time');
        }
        parent::_inspectBeforeUpdate($_record, $_oldRecord);
    }
}
