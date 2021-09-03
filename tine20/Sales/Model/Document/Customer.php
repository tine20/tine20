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
 * Customer Model for Documents (is a snapshot / copy of normal Model_Customer record)
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_Customer extends Sales_Model_Customer
{
    public const MODEL_NAME_PART = 'Document_Customer';
    public const TABLE_NAME = 'sales_document_customer';

    /**
     * @param array $_definition
     */
    public static function inheritModelConfigHook(array &$_definition)
    {
        parent::inheritModelConfigHook($_definition);

        $_definition[self::VERSION] = 1;
        $_definition[self::MODEL_NAME] = self::MODEL_NAME_PART;
        $_definition[self::TABLE][self::NAME] = self::TABLE_NAME;
        $_definition[self::EXPOSE_JSON_API] = true;

        $_definition[self::FIELDS]['delivery'][self::CONFIG][self::MODEL_NAME] =
            Sales_Model_Document_Address::MODEL_NAME_PART;

        $_definition[self::DENORMALIZATION_OF] = Sales_Model_Customer::class;
        $_definition[self::FIELDS][self::FLD_ORIGINAL_ID] = [
            self::TYPE                  => self::TYPE_RECORD,
            self::CONFIG                => [
                self::APP_NAME              => Sales_Config::APP_NAME,
                self::MODEL_NAME            => Sales_Model_Customer::MODEL_NAME_PART,
            ],
        ];
    }

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
