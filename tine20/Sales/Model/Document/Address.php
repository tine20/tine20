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
 * Address Model for Documents (is a snapshot / copy of normal Model_Address record)
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_Address extends Sales_Model_Address
{
    public const MODEL_NAME_PART = 'Document_Address';
    public const TABLE_NAME = 'sales_document_address';

    public const FLD_DOCUMENT_ID = 'document_id';
    public const FLD_DOCUMENT_FIELD = 'document_field';

    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::VERSION] = 2;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TABLE][self::NAME] = self::TABLE_NAME;
        $_definition[self::EXPOSE_JSON_API] = true;
        $_definition[self::DENORMALIZATION_OF] = Sales_Model_Address::class;

        $_definition[self::FIELDS][self::FLD_DOCUMENT_ID] = [
            self::TYPE                  => self::TYPE_RECORD,
            self::NULLABLE              => true,
            self::CONFIG                => [
                self::APP_NAME              => Sales_Config::APP_NAME,
                self::MODEL_NAME            => Sales_Model_Customer::MODEL_NAME_PART,
            ],
        ];
        $_definition[self::FIELDS][self::FLD_DOCUMENT_FIELD] = [
            self::TYPE                  => self::TYPE_STRING,
            self::LENGTH                => 255,
            self::NULLABLE              => true,
        ];
        unset($_definition[self::FIELDS][self::FLD_CUSTOMER_ID][self::VALIDATORS]);
        $_definition[self::FIELDS][self::FLD_CUSTOMER_ID][self::NULLABLE] = true;
        $_definition[self::FIELDS][self::FLD_CUSTOMER_ID][self::CONFIG][self::MODEL_NAME] = Sales_Model_Document_Customer::MODEL_NAME_PART;
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
