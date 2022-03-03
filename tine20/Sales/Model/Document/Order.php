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
 * Order Document Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_Order extends Sales_Model_Document_Abstract
{
    public const MODEL_NAME_PART = 'Document_Order';
    public const TABLE_NAME = 'sales_document_order';

    public const FLD_INVOICE_RECIPIENT_ID = 'invoice_recipient_id';
    public const FLD_DELIVERY_RECIPIENT_ID = 'delivery_recipient_id';

    public const FLD_ORDER_STATUS = 'order_status';

    /**
     * order status
     */
    public const STATUS_RECEIVED = 'RECEIVED';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_DONE = 'DONE';

    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::CREATE_MODULE] = true;
        $_definition[self::RECORD_NAME] = 'Order'; // gettext('GENDER_Order')
        $_definition[self::RECORDS_NAME] = 'Orders'; // ngettext('Order', 'Orders', n)
        
        $_definition[self::VERSION] = 1;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TABLE] = [
            self::NAME                      => self::TABLE_NAME,
        ];

        // order status
        Tinebase_Helper::arrayInsertAfterKey($_definition[self::FIELDS], self::FLD_DOCUMENT_NUMBER, [
            self::FLD_ORDER_STATUS => [
                self::LABEL => 'Status', // _('Status')
                self::TYPE => self::TYPE_KEY_FIELD,
                self::NAME => Sales_Config::DOCUMENT_ORDER_STATUS,
                self::LENGTH => 255,
                self::NULLABLE => true,
            ],
            // @TODO invoice & delivery status -> virtual from following documents
        ]);

        $_definition[self::FIELDS][self::FLD_RECIPIENT_ID][self::CONFIG][self::FORCE_VALUES] = [
            Sales_Model_Document_Address::FLD_DOCUMENT_FIELD => self::FLD_RECIPIENT_ID,
        ];

        $_definition[self::FIELDS][self::FLD_RECIPIENT_ID][self::CONFIG][self::ADD_FILTERS] = [
            ['field' => Sales_Model_Document_Address::FLD_DOCUMENT_FIELD, 'operator' => 'equals', 'value' => self::FLD_RECIPIENT_ID],
        ];

        // invoice & delivery recipients
        Tinebase_Helper::arrayInsertAfterKey($_definition[self::FIELDS], self::FLD_RECIPIENT_ID, [
            self::FLD_INVOICE_RECIPIENT_ID => [
                self::TYPE                  => self::TYPE_RECORD,
                self::LABEL                 => 'Invoice Recipient', //_('Invoice Recipient')
                self::NULLABLE              => true,
                self::UI_CONFIG             => [
                    self::TYPE                  => Sales_Model_Document_Address::TYPE_BILLING,
                ],
                self::CONFIG                => [
                    self::APP_NAME              => Sales_Config::APP_NAME,
                    self::MODEL_NAME            => Sales_Model_Document_Address::MODEL_NAME_PART,
                    self::REF_ID_FIELD          => Sales_Model_Document_Address::FLD_DOCUMENT_ID,
                    self::FORCE_VALUES          => [
                        Sales_Model_Document_Address::FLD_DOCUMENT_FIELD => self::FLD_INVOICE_RECIPIENT_ID,
                    ],
                    self::ADD_FILTERS           => [
                        ['field' => Sales_Model_Document_Address::FLD_DOCUMENT_FIELD, 'operator' => 'equals', 'value' => self::FLD_INVOICE_RECIPIENT_ID],
                    ],
                ],
            ],
            self::FLD_DELIVERY_RECIPIENT_ID => [
                self::TYPE                  => self::TYPE_RECORD,
                self::LABEL                 => 'Delivery Recipient', //_('Delivery Recipient')
                self::NULLABLE              => true,
                self::UI_CONFIG             => [
                    self::TYPE                  => Sales_Model_Document_Address::TYPE_DELIVERY,
                ],
                self::CONFIG                => [
                    self::APP_NAME              => Sales_Config::APP_NAME,
                    self::MODEL_NAME            => Sales_Model_Document_Address::MODEL_NAME_PART,
                    self::REF_ID_FIELD          => Sales_Model_Document_Address::FLD_DOCUMENT_ID,
                    self::FORCE_VALUES          => [
                        Sales_Model_Document_Address::FLD_DOCUMENT_FIELD => self::FLD_DELIVERY_RECIPIENT_ID,
                    ],
                    self::ADD_FILTERS           => [
                        ['field' => Sales_Model_Document_Address::FLD_DOCUMENT_FIELD, 'operator' => 'equals', 'value' => self::FLD_DELIVERY_RECIPIENT_ID],
                    ],
                ],
            ],
        ]);

        $_definition[self::JSON_EXPANDER][Tinebase_Record_Expander::EXPANDER_PROPERTIES]
            [self::FLD_INVOICE_RECIPIENT_ID] = [];
        $_definition[self::JSON_EXPANDER][Tinebase_Record_Expander::EXPANDER_PROPERTIES]
            [self::FLD_DELIVERY_RECIPIENT_ID] = [];

        // order positions
        $_definition[self::FIELDS][self::FLD_POSITIONS][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_DocumentPosition_Order::MODEL_NAME_PART;

    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    protected static $_statusField = self::FLD_ORDER_STATUS;
    protected static $_statusConfigKey = Sales_Config::DOCUMENT_ORDER_STATUS;
    protected static $_documentNumberPrefix = 'OR-'; // _('OR-')

    public function transitionFrom(Sales_Model_Document_Transition $transition)
    {
        parent::transitionFrom($transition);

        if (Sales_Config::INVOICE_DISCOUNT_SUM === $this->{self::FLD_INVOICE_DISCOUNT_TYPE}) {
            $this->_checkProductPrecursorPositionsComplete();
        }
    }
}

