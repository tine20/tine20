<?php declare(strict_types=1);

/**
 * Offer Document controller for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Offer Document controller class for Sales application
 *
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Document_Offer extends Sales_Controller_Document_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = Sales_Config::APP_NAME;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Sales_Model_Document_Offer::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Sales_Model_Document_Offer::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Sales_Model_Document_Offer::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->_validateTransitionState(Sales_Model_Document_Offer::FLD_OFFER_STATUS,
            Sales_Config::getInstance()->{Sales_Config::DOCUMENT_OFFER_STATUS_TRANSITIONS}, $_record);

        // when booked and no document_date set, set it to now
        if (Sales_Config::getInstance()->{Sales_Config::DOCUMENT_OFFER_STATUS}->records
                ->getById($_record->{Sales_Model_Document_Offer::FLD_OFFER_STATUS})
                    ->{Sales_Model_Document_OfferStatus::FLD_BOOKED} &&
                    ! $_record->{Sales_Model_Document_Offer::FLD_DOCUMENT_DATE}) {
            $_record->{Sales_Model_Document_Offer::FLD_DOCUMENT_DATE} = Tinebase_DateTime::now();
        }

        parent::_inspectBeforeCreate($_record);
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        $this->_validateTransitionState(Sales_Model_Document_Offer::FLD_OFFER_STATUS,
            Sales_Config::getInstance()->{Sales_Config::DOCUMENT_OFFER_STATUS_TRANSITIONS}, $_record, $_oldRecord);

        // when oldRecord is booked, enforce read only
        if ($_oldRecord->{Sales_Model_Document_Offer::FLD_OFFER_STATUS} &&
                Sales_Config::getInstance()->{Sales_Config::DOCUMENT_OFFER_STATUS}->records
                    ->getById($_oldRecord->{Sales_Model_Document_Offer::FLD_OFFER_STATUS})
                        ->{Sales_Model_Document_OfferStatus::FLD_BOOKED}) {
            foreach ($_record->getConfiguration()->fields as $field => $fConf) {
                if (in_array($field, [
                    Sales_Model_Document_Offer::FLD_OFFER_STATUS,
                    Sales_Model_Document_Offer::FLD_COST_CENTER_ID,
                    Sales_Model_Document_Offer::FLD_COST_BEARER_ID,
                    Sales_Model_Document_Offer::FLD_DESCRIPTION,
                    'tags', 'attachments', 'relations',
                ])) continue;
                $_record->{$field} = $_oldRecord->{$field};
            }
        }
        // when booked and no document_date set, set it to now
        if (Sales_Config::getInstance()->{Sales_Config::DOCUMENT_OFFER_STATUS}->records
                ->getById($_record->{Sales_Model_Document_Offer::FLD_OFFER_STATUS})
                    ->{Sales_Model_Document_OfferStatus::FLD_BOOKED} &&
                    ! $_record->{Sales_Model_Document_Offer::FLD_DOCUMENT_DATE}) {
            $_record->{Sales_Model_Document_Offer::FLD_DOCUMENT_DATE} = Tinebase_DateTime::now();
        }

        parent::_inspectBeforeUpdate($_record, $_oldRecord);
    }

    protected function _inspectDelete(array $_ids)
    {
        // do not deleted booked records
        foreach ($this->getMultiple($_ids) as $record) {
            if (Sales_Config::getInstance()->{Sales_Config::DOCUMENT_OFFER_STATUS}->records
                    ->getById($record->{Sales_Model_Document_Offer::FLD_OFFER_STATUS})
                    ->{Sales_Model_Document_OfferStatus::FLD_BOOKED}) {
                unset($_ids[array_search($record->getId(), $_ids)]);
            }
        }
        return parent::_inspectDelete($_ids);
    }
}
