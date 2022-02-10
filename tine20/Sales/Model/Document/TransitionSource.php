<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Document TransitionSource Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_TransitionSource extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Document_TransitionSource';

    public const FLD_SOURCE_DOCUMENT = 'sourceDocument';
    public const FLD_SOURCE_DOCUMENT_MODEL = 'sourceDocumentModel';
    public const FLD_SOURCE_POSITIONS = 'sourcePositions';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::FIELDS                        => [
            self::FLD_SOURCE_DOCUMENT_MODEL     => [
                self::TYPE                          => self::TYPE_MODEL,
            ],
            self::FLD_SOURCE_DOCUMENT           => [
                self::TYPE                          => self::TYPE_DYNAMIC_RECORD,
                self::CONFIG                        => [
                    self::REF_MODEL_FIELD               => self::FLD_SOURCE_DOCUMENT_MODEL,
                    self::PERSISTENT                    => Tinebase_Model_Converter_DynamicRecord::REFID,
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_SOURCE_POSITIONS          => [
                self::TYPE                          => self::TYPE_RECORDS,
                self::CONFIG                        => [
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_DocumentPosition_TransitionSource::MODEL_NAME_PART,
                ],
                /*self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],*/
            ],
        ],
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
