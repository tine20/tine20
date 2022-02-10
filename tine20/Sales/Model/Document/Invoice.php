<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Invoice Document Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_Invoice extends Sales_Model_Document_Abstract
{
    public const MODEL_NAME_PART = 'Document_Invoice';
    public const TABLE_NAME = 'sales_document_invoice';

    public const FLD_INVOICE_STATUS = 'invoice_status';

    /**
     * invoice status
     */
    public const STATUS_PROFORMA = 'PROFORMA';
    public const STATUS_BOOKED = 'BOOKED';
    public const STATUS_SHIPPED = 'SHIPPED';
    public const STATUS_PAID = 'PAID';


    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::CREATE_MODULE] = true;
        $_definition[self::RECORD_NAME] = 'Invoice'; // gettext('GENDER_Invoice')
        $_definition[self::RECORDS_NAME] = 'Invoices'; // ngettext('Invoice', 'Invoices', n)

        $_definition[self::VERSION] = 1;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TABLE] = [
            self::NAME                      => self::TABLE_NAME,
            /*self::INDEXES                   => [
                self::FLD_PRODUCT_ID            => [
                    self::COLUMNS                   => [self::FLD_PRODUCT_ID],
                ],
            ]*/
        ];

        // invoice positions
        $_definition[self::FIELDS][self::FLD_POSITIONS][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_DocumentPosition_Invoice::MODEL_NAME_PART;

        // invoice status
        $_definition[self::FIELDS][self::FLD_INVOICE_STATUS] = [
            self::LABEL => 'Status', // _('Status')
            self::TYPE => self::TYPE_KEY_FIELD,
            self::NAME => Sales_Config::DOCUMENT_INVOICE_STATUS,
            self::LENGTH => 255,
            self::NULLABLE => true,
        ];
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    protected static $_statusField = self::FLD_INVOICE_STATUS;
    protected static $_statusConfigKey = Sales_Config::DOCUMENT_INVOICE_STATUS;
    protected static $_documentNumberPrefix = 'PI-'; // _('PI-')

    public function transitionFrom(Sales_Model_Document_Transition $transition)
    {
        parent::transitionFrom($transition);

        $this->{self::FLD_RECIPIENT_ID} = $transition->{Sales_Model_Document_Transition::FLD_SOURCE_DOCUMENTS}
            ->getFirstRecord()->{Sales_Model_Document_TransitionSource::FLD_SOURCE_DOCUMENT}
            ->{Sales_Model_Document_Order::FLD_INVOICE_RECIPIENT_ID};

        if (Sales_Config::INVOICE_DISCOUNT_SUM === $this->{self::FLD_INVOICE_DISCOUNT_TYPE}) {
            $this->_checkProductPrecursorPositionsComplete();
        }
    }
}
