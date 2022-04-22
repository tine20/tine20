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
 * DocumentPosition TransitionSource Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_DocumentPosition_TransitionSource extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'DocumentPosition_TransitionSource';

    public const FLD_SOURCE_DOCUMENT_POSITION_MODEL = 'sourceDocumentPositionModel';
    public const FLD_SOURCE_DOCUMENT_POSITION = 'sourceDocumentPosition';
    public const FLD_IS_REVERSAL = 'isReversal';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::FIELDS                        => [
            self::FLD_SOURCE_DOCUMENT_POSITION_MODEL => [
                self::TYPE                          => self::TYPE_MODEL,
            ],
            self::FLD_SOURCE_DOCUMENT_POSITION  => [
                self::TYPE                          => self::TYPE_DYNAMIC_RECORD,
                self::CONFIG                        => [
                    self::REF_MODEL_FIELD               => self::FLD_SOURCE_DOCUMENT_POSITION_MODEL,
                    self::PERSISTENT                    => Tinebase_Model_Converter_DynamicRecord::REFID,
                ],
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_IS_REVERSAL                 => [
                self::TYPE                          => self::TYPE_BOOLEAN,
                self::INPUT_FILTERS                 => [Zend_Filter_Empty::class => false],
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
