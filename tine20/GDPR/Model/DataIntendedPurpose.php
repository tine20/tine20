<?php
/**
 * class to hold DataIntendedPurpose data
 *
 * @package     GDPR
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold DataIntendedPurpose data
 *
 * @package     GDPR
 * @subpackage  Model
 * 
 * @property    string              $id
 * @property    string              $name
 */
class GDPR_Model_DataIntendedPurpose extends Tinebase_Record_Abstract
{
    const MODEL_NAME_PART = 'DataIntendedPurpose';

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
            self::NAME => 'gdpr_dataintendedpurposes',
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
                'label' => 'Data intended purpose', // _('Data intended purpose')
                'queryFilter' => true
            ],
        ]
    ];

    public function isReplicable()
    {
        return true;
    }
}
