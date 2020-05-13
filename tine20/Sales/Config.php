<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * How should the contract number be created
     * @var string
     */
    const PRODUCT_NUMBER_GENERATION = 'productNumberGeneration';
    
    /**
     * How should the contract number be validated
     * 
     * @var string
     */
    const PRODUCT_NUMBER_VALIDATION = 'productNumberValidation';
    
    /**
     * Prefix of the product number
     * 
     * @var string
     */
    const PRODUCT_NUMBER_PREFIX = 'productNumberPrefix';
    
    /**
     * Fill product number with leading zero's if needed
     * 
     * @var string
     */
    const PRODUCT_NUMBER_ZEROFILL = 'productNumberZeroFill';
    
    /**
     * Invoice Type
     * 
     * @var string
     */
    const INVOICE_TYPE = 'invoiceType';
    
    const PAYMENT_METHODS = 'paymentMethods';
    
    /**
     * Product Category
     * 
     * @var string
     */
    const PRODUCT_CATEGORY = 'productCategory';
    
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
     * suppliers module feature
     *
     * @var string
     */
    const FEATURE_SUPPLIERS_MODULE = 'suppliersModule';
    
    /**
     * purchase invoices module feature
     *
     * @var string
     */
    const FEATURE_PURCHASE_INVOICES_MODULE = 'purchaseInvoicesModule';
    
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
            'default'               => array(
                'records' => array(
                    array('id' => 'INVOICE',  'value' => 'Invoice',  'system' => true), // _('Invoice')
                    array('id' => 'REVERSAL', 'value' => 'Reversal', 'system' => true), // _('Reversal')
                    array('id' => 'CREDIT',   'value' => 'Credit',   'system' => true)  // _('Credit')
                ),
                'default' => 'INVOICE'
            )
        ),
        self::PRODUCT_CATEGORY => array(
                                   //_('Product Category')
            'label'                 => 'Product Category',
                                   //_('Possible Product Categories.')
            'description'           => 'Possible Product Categories.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Sales_Model_ProductCategory'),
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'DEFAULT', 'value' => 'Default', 'system' => true) // _('Default')
                ),
                'default' => 'DEFAULT'
            )
        ),
        self::PRODUCT_NUMBER_GENERATION => array(
                                   //_('Product Number Creation')
            'label'                 => 'Product Number Creation',
                                   //_('Should the product number be set manually or be auto-created?')
            'description'           => 'Should the product number be set manually or be auto-created?',
            'type'                  => 'string',
                                    // _('automatically')
                                    // _('manually')
            'options'               => array(array('auto', 'automatically'), array('manual', 'manually')),
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => 'auto'
        ),
        self::PRODUCT_NUMBER_VALIDATION => array(
                                   //_('Product Number Validation')
            'label'                 => 'Product Number Validation',
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
        self::PRODUCT_NUMBER_PREFIX => array(
                                   //_('Product Number Prefix')
            'label'                 => 'Product Number Prefix',
                                   //_('The prefix of the product number.')
            'description'           => 'The prefix of the product number',
            'type'                  => 'string',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => 'P-'
        ),
        self::PRODUCT_NUMBER_ZEROFILL => array(
                                   //_('Product Number Zero Fill')
            'label'                 => 'Product Number Zero Fill',
                                   //_('Fill the number with leading zero's if needed.')
            'description'           => 'Fill the number with leading zero\'s if needed.',
            'type'                  => 'number',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => '5'
        ),
        self::PAYMENT_METHODS => array(
                                   //_('Payment Method')
            'label'                 => 'Payment Method',
                                   //_('Possible Payment Methods.')
            'description'           => 'Possible Payment Methods.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Sales_Model_PaymentMethod'),
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'BANK TRANSFER', 'value' => 'Bank transfer', 'system' => true), // _('Bank transfer')
                    array('id' => 'DIRECT DEBIT',  'value' => 'Direct debit',  'system' => true),  // _('Direct debit')
                    array('id' => 'CANCELLATION',  'value' => 'Cancellation',  'system' => true),  // _('Cancellation')
                    array('id' => 'CREDIT',  'value' => 'Credit',  'system' => true),  // _('Credit')
                    array('id' => 'CREDIT CARD',  'value' => 'Credit card',  'system' => true),  // _('Credit card')
                    array('id' => 'EC CARD',  'value' => 'EC card',  'system' => true),  // _('EC card')
                    array('id' => 'PAYPAL',  'value' => 'Paypal',  'system' => true),  // _('Paypal')
                    array('id' => 'ASSETS', 'value' => 'Assets', 'system' => true), // _('Assets')
                ),
                'default' => 'BANK TRANSFER'
            )
        ),
        self::INVOICE_CLEARED => array(
                                   //_('Invoice Cleared')
            'label'                 => 'Invoice Cleared',
                                   //_('Possible Invoice Cleared States.')
            'description'           => 'Possible Invoice Cleared States.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Sales_Model_InvoiceCleared'),
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'TO_CLEAR', 'value' => 'to clear', 'system' => true), // _('to clear')
                    array('id' => 'CLEARED',  'value' => 'cleared',  'system' => true), // _('cleared')
                ),
                'default' => 'TO_CLEAR'
            )
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
        self::ENABLED_FEATURES => [
            //_('Enabled Features')
            self::LABEL                 => 'Enabled Features',
            //_('Enabled Features in Sales Application.')
            self::DESCRIPTION           => 'Enabled Features in Sales Application.',
            self::TYPE                  => self::TYPE_OBJECT,
            self::CLASSNAME             => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => true,
            self::CONTENT               => [

                self::FEATURE_INVOICES_MODULE           => [
                    self::LABEL                             => 'Invoices Module',
                    //_('Invoices Module')
                    self::DESCRIPTION                       => 'Invoices Module',
                    self::TYPE                              => self::TYPE_BOOL,
                    self::DEFAULT_STR                       => true,
                ],
                self::FEATURE_OFFERS_MODULE             => [
                    self::LABEL                             => 'Offers Module',
                    //_('Offers Module')
                    self::DESCRIPTION                       => 'Offers Module',
                    self::TYPE                              => self::TYPE_BOOL,
                    self::DEFAULT_STR                       => true,
                ],
                self::FEATURE_ORDERCONFIRMATIONS_MODULE => [
                    self::LABEL                             => 'Order Confirmations Module',
                    //_('Order Confirmations Module')
                    self::DESCRIPTION                       => 'Order Confirmations Module',
                    self::TYPE                              => self::TYPE_BOOL,
                    self::DEFAULT_STR                       => true,
                ],
                self::FEATURE_SUPPLIERS_MODULE          => [
                    self::LABEL                             => 'Suppliers Module',
                    //_('Suppliers Module')
                    self::DESCRIPTION                       => 'Suppliers Module',
                    self::TYPE                              => self::TYPE_BOOL,
                    self::DEFAULT_STR                       => true,
                ],
                self::FEATURE_PURCHASE_INVOICES_MODULE  => [
                    self::LABEL                             => 'Purchase Invoice Module',
                    //_('Purchase Invoice Module')
                    self::DESCRIPTION                       => 'Purchase Invoice Module',
                    self::TYPE                              => self::TYPE_BOOL,
                    self::DEFAULT_STR                       => true,
                ],
            ],
            self::DEFAULT_STR => [],
        ],
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
