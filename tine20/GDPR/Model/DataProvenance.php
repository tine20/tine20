<?php
/**
 * class to hold DataProvenance data
 *
 * @package     GDPR
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold DataProvenance data
 *
 * @package     GDPR
 * @subpackage  Model
 * 
 * @property    string              $id
 * @property    string              $name
 * @property    Tinebase_DateTime   $expiration
 */
class GDPR_Model_DataProvenance extends Tinebase_Record_Abstract
{
    const MODEL_NAME_PART = 'DataProvenance';

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
        'recordName' => 'Data provenance',
        'recordsName' => 'Data provenances', // ngettext('Data provenance', 'Data provenances', n)
        'titleProperty' => 'name',
        'hasRelations' => false,
        'hasCustomFields' => false,
        'hasNotes' => false,
        'hasTags' => false,
        'modlogActive' => true,
        'hasAttachments' => false,
        'exposeJsonApi' => true,
        'exposeHttpApi' => true,

        'singularContainerMode' => false,
        'hasPersonalContainer' => false,

        'copyEditAction' => true,
        'multipleEdit' => false,
        
        'createModule' => false,
        'appName' => GDPR_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::TABLE => [
            self::NAME => 'gdpr_dataprovenances',
            self::UNIQUE_CONSTRAINTS   => [
                'name'                  => [
                    self::COLUMNS           => ['name'],
                ],
            ],
        ],

        self::FIELDS => [
            'name' => [
                'type' => 'string',
                'length' => 255,
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                'label' => 'Data provenance', // _('Data provenance')
                'queryFilter' => true
            ],
            'expiration' => [
                'validators' => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'nullable' => true,
                'label' => 'Expiration', // _('Expiration')
                'type' => 'datetime',
                'filterDefinition'  => [
                    'filter'    => Tinebase_Model_Filter_DateTime::class,
                    'options'   => [
                        Tinebase_Model_Filter_Date::AFTER_OR_IS_NULL  => true,
                    ]
                ]
            ],
        ]
    ];

    public function isReplicable()
    {
        return true;
    }
}
