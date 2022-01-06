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
 * DeliveryNote Document Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_DeliveryNote extends Sales_Model_Document_Abstract
{
    public const MODEL_NAME_PART = 'Document_DeliveryNote';
    public const TABLE_NAME = 'sales_document_delivery_note';

    public const FLD_DELIVERY_NOTE_STATUS = 'delivery_note_status';

    /**
     * deliveryNote status
     */
    public const STATUS_CREATED = 'CREATED';
    public const STATUS_DELIVERED = 'DELIVERED';

    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::CREATE_MODULE] = true;
        $_definition[self::RECORD_NAME] = 'Delivery Note'; // gettext('GENDER_Delivery Note')
        $_definition[self::RECORDS_NAME] = 'Delivery Notes'; // ngettext('Delivery Note', 'Delivery Notes', n)

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

        // deliveryNote positions
        $_definition[self::FIELDS][self::FLD_POSITIONS][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_DocumentPosition_DeliveryNote::MODEL_NAME_PART;

        // deliveryNote status
        $_definition[self::FIELDS][self::FLD_DELIVERY_NOTE_STATUS] = [
            self::LABEL => 'Status', // _('Status')
            self::TYPE => self::TYPE_KEY_FIELD,
            self::NAME => Sales_Config::DOCUMENT_DELIVERY_NOTE_STATUS,
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
}

