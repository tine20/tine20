<?php
/**
 * class to hold tree reflog data
 *
 * @package     Tinebase
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold tree reflog data
 *
 * @package     Tinebase
 * @subpackage  Model
 */
class Tinebase_Model_Tree_RefLog extends Tinebase_Record_NewAbstract
{
    const TABLE_NAME = 'tree_reflog';
    const MODEL_NAME_PART = 'Tree_RefLog';

    const FLD_FOLDER_ID = 'folder_id';
    const FLD_SIZE_DELTA = 'sizeDelta';
    const FLD_REVISION_SIZE_DELTA = 'revisionSizeDelta';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION       => 1,

        self::APP_NAME      => Tinebase_Config::APP_NAME,
        self::MODEL_NAME    => self::MODEL_NAME_PART,

        self::TABLE         => [
            self::NAME              => self::TABLE_NAME,
            // this means: id will become an auto increment on mysql
            self::ID_GENERATOR_TYPE => Doctrine\ORM\Mapping\ClassMetadataInfo::GENERATOR_TYPE_IDENTITY,
        ],

        self::FIELDS        => [
            self::ID                        => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::ID                        => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY  => true],
            ],
            self::FLD_FOLDER_ID             => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 40,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_SIZE_DELTA            => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
            self::FLD_REVISION_SIZE_DELTA   => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                ],
            ],
        ],
    ];
}
