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
 * Offer Document Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_Offer extends Sales_Model_Document_Abstract
{
    public const MODEL_NAME_PART = 'Document_Offer';
    public const TABLE_NAME = 'sales_document_offer';

    public const FLD_ORDER_ID = 'order_id';

    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::CREATE_MODULE] = true;
        $_definition[self::RECORD_NAME] = 'Offer'; // gettext('GENDER_Offer')
        $_definition[self::RECORDS_NAME] = 'Offers'; // ngettext('Offer', 'Offers', n)
        
        $_definition[self::VERSION] = 1;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TABLE] = [
            self::NAME                      => self::TABLE_NAME,
            /*self::INDEXES                   => [
                self::FLD_...            => [
                    self::COLUMNS                   => [self::FLD_...],
                ],
            ]*/
        ];

        if (!isset($_definition[self::ASSOCIATIONS][\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE])) {
            $_definition[self::ASSOCIATIONS][\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE] = [];
        }
        $_definition[self::ASSOCIATIONS][\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE][self::FLD_ORDER_ID] = [
            self::TARGET_ENTITY             => Sales_Model_Document_Order::class,
            self::FIELD_NAME                => self::FLD_ORDER_ID,
            self::JOIN_COLUMNS              => [[
                self::NAME                      => self::FLD_ORDER_ID,
                self::REFERENCED_COLUMN_NAME    => Sales_Model_Document_Order::ID,
            ]],
        ];

        // on offers customers are optional
        unset($_definition[self::FIELDS][self::FLD_CUSTOMER_ID][self::VALIDATORS]);

        // offers dont have precursor documents, that would be a crm lead or something in the future
        // TODO for the FE, maybe we make this a virtual field? not present in DB, always of value null?
        unset($_definition[self::FIELDS][self::FLD_PRECURSOR_DOCUMENTS]);

        $_definition[self::FIELDS][self::FLD_ORDER_ID] = [
            self::TYPE                  => self::TYPE_RECORD,
            self::DISABLED              => true,
            self::CONFIG                => [
                self::APP_NAME              => Sales_Config::APP_NAME,
                self::MODEL_NAME            => Sales_Model_Document_Order::MODEL_NAME_PART,
            ],
            self::NULLABLE              => true,
        ];

        $_definition[self::FIELDS][self::FLD_POSITIONS][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_DocumentPosition_Offer::MODEL_NAME_PART;
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
