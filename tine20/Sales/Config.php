<?php
/**
 * @package     Sales
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Sales config class
 * 
 * @package     Sales
 * @subpackage  Config
 * 
 */
class Sales_Config extends Tinebase_Config_Abstract
{
    /**
     * sets the biggest interval, contracts will be billed
     * 
     * @var string
     */
    const AUTO_INVOICE_CONTRACT_INTERVAL = 'auto_invoice_contract_interval';
    
    /**
     * defines which billables should be ignored
     * 
     * @var string
     */
    const IGNORE_BILLABLES_BEFORE = 'ignoreBillablesBefore';
    
    /**
     * How should the contract number be created
     * @var string
     */
    const CONTRACT_NUMBER_GENERATION = 'contractNumberGeneration';
    
    /**
     * How should the contract number be validated
     * @var string
     */
    const CONTRACT_NUMBER_VALIDATION = 'contractNumberValidation';
    
    /**
     * Invoice Type
     * 
     * @var string
     */
    const INVOICE_TYPE = 'invoiceType';
    
    /**
     * Invoice Type
     *
     * @var string
     */
    const INVOICE_CLEARED = 'invoiceCleared';
    
    /**
     * the own currency
     *
     * @var string
     */
    const OWN_CURRENCY = 'ownCurrency';
    
    /**
     * invoices module feature
     *
     * @var string
     */
    const FEATURE_INVOICES_MODULE = 'invoicesModule';
    
    /**
     * offers module feature
     *
     * @var string
     */
    const FEATURE_OFFERS_MODULE = 'offersModule';
    
    /**
     * order confirmations module feature
     *
     * @var string
     */
    const FEATURE_ORDERCONFIRMATIONS_MODULE = 'orderConfirmationsModule';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::AUTO_INVOICE_CONTRACT_INTERVAL => array(
            //_('Auto Invoice Contract Interval')
            'label'                 => 'Auto Invoice Contract Interval',
            //_('Sets the biggest interval, contracts will be billed.')
            'description'           => 'Sets the biggest interval, contracts will be billed.',
            'type'                  => 'integer',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => 12
        ),
        self::IGNORE_BILLABLES_BEFORE => array(
            //_('Ignore Billables Before Date')
            'label'                 => 'Ignore Billables Before Date',
            //_('Sets the date billables will be ignored before.')
            'description'           => 'Sets the date billables will be ignored before.',
            'type'                  => 'string',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => '2000-01-01 22:00:00'
        ),
        self::CONTRACT_NUMBER_GENERATION => array(
                                   //_('Contract Number Creation')
            'label'                 => 'Contract Number Creation',
                                   //_('Should the contract number be set manually or be auto-created?')
            'description'           => 'Should the contract number be set manually or be auto-created?',
            'type'                  => 'string',
                                    // _('automatically')
                                    // _('manually')
            'options'               => array(array('auto', 'automatically'), array('manual', 'manually')),
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => 'auto'
        ),
        self::CONTRACT_NUMBER_VALIDATION => array(
                                   //_('Contract Number Validation')
            'label'                 => 'Contract Number Validation',
                                   //_('The Number can be validated as text or number.')
            'description'           => 'The Number can be validated as text or number.',
            'type'                  => 'string',
                                    // _('Number')
                                    // _('Text')
            'options'               => array(array('integer', 'Number'), array('string', 'Text')),
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => 'string'
        ),
        self::OWN_CURRENCY => array(
            // _('Own Currency')
            'label'                 => 'Own Currency',
            // _('The currency defined here is used as default currency in the customerd edit dialog.')
            'description'           => 'The currency defined here is used as default currency in the customerd edit dialog.',
            'type'                  => 'string',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => 'EUR'
        ),
        self::INVOICE_TYPE => array(
                                   //_('Invoice Type')
            'label'                 => 'Invoice Type',
                                   //_('Possible Invoice Types.')
            'description'           => 'Possible Invoice Types.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Sales_Model_InvoiceType'),
            'clientRegistryInclude' => TRUE,
            'default'               => 'INVOICE'
        ),
        self::INVOICE_CLEARED => array(
                                   //_('Invoice Cleared')
            'label'                 => 'Invoice Cleared',
                                   //_('Possible Invoice Cleared States.')
            'description'           => 'Possible Invoice Cleared States.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Sales_Model_InvoiceCleared'),
            'clientRegistryInclude' => TRUE,
            'default'               => 'TO_CLEAR'
        ),
        /**
         * enabled Sales features
         * 
         * to overwrite the defaults, you can add a Sales/config.inc.php like this:
         * 
         * <?php
            return array (
                // this switches some modules off
                'features' => array(
                    'invoicesModule'             => false,
                    'offersModule'               => false,
                    'orderConfirmationsModule'   => false,
                )
            );
         */
        self::ENABLED_FEATURES => array(
                                   //_('Enabled Features')
            'label'                 => 'Enabled Features',
                                   //_('Enabled Features in Sales Application.')
            'description'           => 'Enabled Features in Sales Application.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => TRUE,
            'content'               => array(
                self::FEATURE_INVOICES_MODULE => array(
                    'label'         => 'Invoices Module', //_('Invoices Module')
                    'description'   => 'Invoices Module',
                ),
                self::FEATURE_OFFERS_MODULE => array(
                    'label'         => 'Offers Module', //_('Offers Module')
                    'description'   => 'Offers Module',
                ),
                self::FEATURE_ORDERCONFIRMATIONS_MODULE => array(
                    'label'         => 'Order Confirmations Module', //_('Order Confirmations Module')
                    'description'   => 'Order Confirmations Module',
                ),
            ),
            'default'               => array(
                self::FEATURE_INVOICES_MODULE           => true,
                self::FEATURE_OFFERS_MODULE             => true,
                self::FEATURE_ORDERCONFIRMATIONS_MODULE => true,
            ),
        ),
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Sales';
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __construct() {}
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __clone() {}
    
    /**
     * Returns instance of Tinebase_Config
     *
     * @return Tinebase_Config
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}
