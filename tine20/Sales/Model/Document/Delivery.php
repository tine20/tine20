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
 * Delivery Document Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_Delivery extends Sales_Model_Document_Abstract
{
    public const MODEL_NAME_PART = 'Document_Delivery';
    public const TABLE_NAME = 'sales_document_delivery';

    public const FLD_DELIVERY_STATUS = 'delivery_status';

    /**
     * delivery status
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
        $_definition[self::RECORD_NAME] = 'Delivery'; // gettext('GENDER_Delivery')
        $_definition[self::RECORDS_NAME] = 'Deliveries'; // ngettext('Delivery', 'Deliveries', n)

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

        // delivery positions
        $_definition[self::FIELDS][self::FLD_POSITIONS][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_DocumentPosition_Delivery::MODEL_NAME_PART;

        // delivery status
        $_definition[self::FIELDS][self::FLD_DELIVERY_STATUS] = [
            self::LABEL => 'Status', // _('Status')
            self::TYPE => self::TYPE_KEY_FIELD,
            self::NAME => Sales_Config::DOCUMENT_DELIVERY_STATUS,
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

