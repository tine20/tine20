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

        $this->_transitionStatusField = Sales_Model_Document_Offer::FLD_OFFER_STATUS;
        $this->_transitionConfig = Sales_Config::DOCUMENT_OFFER_STATUS_TRANSITIONS;
    }

    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->_validateState($_record);
        parent::_inspectBeforeCreate($_record);
    }

    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        $this->_validateState($_record, $_oldRecord);
        parent::_inspectBeforeUpdate($_record, $_oldRecord);
    }
}
