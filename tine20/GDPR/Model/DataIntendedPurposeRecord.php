<?php
/**
 * class to hold DataIntendedPurposeRecord data
 *
 * @package     GDPR
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold DataIntendedPurposeRecord data
 *
 * @package     GDPR
 * @subpackage  Model
 * 
 * @property    string                          $id
 * @property    GDPR_Model_DataIntendedPurpose  $intendedPurpose
 * @property    Tinebase_DateTime               $agreeDate
 * @property    string                          $agreeComment
 * @property    Tinebase_DateTime               $withdrawDate
 * @property    string                          $withdrawComment
 */
class GDPR_Model_DataIntendedPurposeRecord extends Tinebase_Record_Abstract
{
    const MODEL_NAME_PART = 'DataIntendedPurposeRecord';

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
        'version' => 1,
        'recordName' => 'Data intended purpose',
        'recordsName' => 'Data intended purposes', // ngettext('Data intended purpose', 'Data intended purposes', n)
        'titleProperty' => 'id',
        'hasRelations' => false,
        'hasCustomFields' => false,
        'hasNotes' => false,
        'hasTags' => false,
        'modlogActive' => true,
        'hasAttachments' => false,
        'exposeJsonApi' => false,
        'exposeHttpApi' => false,

        'singularContainerMode' => false,
        'hasPersonalContainer' => false,

        'copyEditAction' => false,
        'multipleEdit' => false,
        
        'createModule' => false,
        'appName' => GDPR_Config::APP_NAME,
        'modelName' => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME      => 'gdpr_dataintendedpurposerecords',
            self::UNIQUE_CONSTRAINTS   => [
                'intendedPurpose'       => [
                    self::COLUMNS           => ['intendedPurpose', 'record'],
                ],
                'record'                => [
                    self::COLUMNS           => ['record', 'intendedPurpose'],
                ],
            ]
        ],

        self::ASSOCIATIONS => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'intendedPurpose_fk' => [
                    'targetEntity' => GDPR_Model_DataIntendedPurpose::class,
                    'fieldName' => 'intendedPurpose',
                    'joinColumns' => [[
                        'name' => 'intendedPurpose',
                        'referencedColumnName'  => 'id'
                    ]],
                ],
            ],
        ],

        self::FIELDS => [
            'intendedPurpose'       => [
                self::TYPE              => self::TYPE_RECORD,
                self::LENGTH            => 40,
                self::CONFIG            => [
                    self::APP_NAME          => GDPR_Config::APP_NAME,
                    self::MODEL_NAME        => GDPR_Model_DataIntendedPurpose::MODEL_NAME_PART,
                ],
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Data intended purpose', // _('Data intended purpose')
                self::QUERY_FILTER      => true,
            ],
            'record'                => [
                self::TYPE              => self::TYPE_STRING,
                self::LENGTH            => 40,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::DISABLED          => true,
            ],
            'agreeDate' => [
                self::TYPE              => self::TYPE_DATE,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL             => 'Agreement date', // _('Agreement date')
            ],
            'agreeComment' => [
                self::TYPE              => self::TYPE_STRING,
                self::LENGTH            => 255,
                self::NULLABLE          => true,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL             => 'Agreement comment', // _('Agreement comment')
            ],
            'withdrawDate' => [
                self::TYPE              => self::TYPE_DATE,
                self::NULLABLE          => true,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL             => 'Withdraw date', // _('Withdraw date')
            ],
            'withdrawComment' => [
                self::TYPE              => self::TYPE_STRING,
                self::LENGTH            => 255,
                self::NULLABLE          => true,
                self::VALIDATORS        => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL             => 'Withdraw comment', // _('Withdraw comment')
            ],
        ]
    ];
}
