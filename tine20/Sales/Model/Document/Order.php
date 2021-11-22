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
            /*self::INDEXES                   => [
                self::FLD_PRODUCT_ID            => [
                    self::COLUMNS                   => [self::FLD_PRODUCT_ID],
                ],
            ]*/
        ];

        // order precursor documents are offers
        $_definition[self::FIELDS][self::FLD_PRECURSOR_DOCUMENTS][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_Document_Offer::MODEL_NAME_PART;
        $_definition[self::FIELDS][self::FLD_PRECURSOR_DOCUMENTS][self::CONFIG][self::REF_ID_FIELD] =
            Sales_Model_Document_Offer::FLD_ORDER_ID;

        $_definition[self::FIELDS][self::FLD_POSITIONS][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_DocumentPosition_Order::MODEL_NAME_PART;
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}

