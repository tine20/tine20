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
 * Document Transition Model
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Document_Transition extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'Document_Transition';

    public const FLD_SOURCE_DOCUMENTS = 'sourceDocuments';
    public const FLD_TARGET_DOCUMENT_TYPE = 'targetDocumentType';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::APP_NAME                      => Sales_Config::APP_NAME,
        self::MODEL_NAME                    => self::MODEL_NAME_PART,

        self::FIELDS                        => [
            self::FLD_SOURCE_DOCUMENTS          => [
                self::TYPE                          => self::TYPE_RECORDS,
                self::CONFIG                        => [
                    self::STORAGE                       => self::TYPE_JSON,
                    self::APP_NAME                      => Sales_Config::APP_NAME,
                    self::MODEL_NAME                    => Sales_Model_Document_TransitionSource::MODEL_NAME_PART,
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE    => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_TARGET_DOCUMENT_TYPE      => [
                self::TYPE                          => self::TYPE_MODEL,
                self::VALIDATORS                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => false,
                    Zend_Filter_Input::PRESENCE         => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
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
