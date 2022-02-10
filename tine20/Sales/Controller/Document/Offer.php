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

    protected $_documentStatusConfig = Sales_Config::DOCUMENT_OFFER_STATUS;
    protected $_documentStatusTransitionConfig = Sales_Config::DOCUMENT_OFFER_STATUS_TRANSITIONS;
    protected $_documentStatusField = Sales_Model_Document_Offer::FLD_OFFER_STATUS;
    protected $_oldRecordBookWriteableFields = [
        Sales_Model_Document_Offer::FLD_OFFER_STATUS,
        Sales_Model_Document_Offer::FLD_COST_CENTER_ID,
        Sales_Model_Document_Offer::FLD_COST_BEARER_ID,
        Sales_Model_Document_Offer::FLD_DESCRIPTION,
        'tags', 'attachments', 'relations',
    ];

    protected $_bookRecordRequiredFields = [
        Sales_Model_Document_Offer::FLD_CUSTOMER_ID,
        Sales_Model_Document_Offer::FLD_RECIPIENT_ID,
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
            Tinebase_Backend_Sql_Abstract::MODEL_NAME => Sales_Model_Document_Offer::class,
            Tinebase_Backend_Sql_Abstract::TABLE_NAME => Sales_Model_Document_Offer::TABLE_NAME,
            Tinebase_Backend_Sql_Abstract::MODLOG_ACTIVE => true,
        ]);
        $this->_modelName = Sales_Model_Document_Offer::class;
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = false;
    }
}
