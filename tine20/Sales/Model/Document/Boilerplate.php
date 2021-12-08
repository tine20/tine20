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
 * Model for Boilerplates
 *
 * @package     Sales
 * @subpackage  Model
 *
 * @property string $name
 * @property string $listId
 */
class Sales_Model_Document_Boilerplate extends Sales_Model_Boilerplate
{
    public const MODEL_NAME_PART = 'Document_Boilerplate';
    public const TABLE_NAME = 'sales_document_boilerplate';

    public const FLD_DOCUMENT_ID = 'document_id';

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

        if (!isset($_definition[self::ASSOCIATIONS][\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE])) {
            $_definition[self::ASSOCIATIONS][\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE] = [];
        }
        /* sadly not possible
         * $_definition[self::ASSOCIATIONS][\Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE][self::FLD_DOCUMENT_ID] =
            [
                self::TARGET_ENTITY             => Sales_Model_Document_Offer::class,
                self::FIELD_NAME                => self::FLD_DOCUMENT_ID,
                self::JOIN_COLUMNS              => [[
                    self::NAME                      => self::FLD_DOCUMENT_ID,
                    self::REFERENCED_COLUMN_NAME    => Sales_Model_Document_Offer::ID,
                ]],
            ];*/

        $_definition[self::DENORMALIZATION_OF] = Sales_Model_Boilerplate::class;
        $_definition[self::DENORMALIZATION_CONFIG] = [
            self::TRACK_CHANGES     => true,
            self::CASCADE           => true,
        ];
        $_definition[self::FIELDS][self::FLD_DOCUMENT_ID] = [
            self::TYPE                  => self::TYPE_RECORD,
            self::NORESOLVE             => true,
            self::CONFIG                => [
                self::APP_NAME              => Sales_Config::APP_NAME,
                self::MODEL_NAME            => Sales_Model_Document_Offer::MODEL_NAME_PART, // TODO not nice, it can be any document really...
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
