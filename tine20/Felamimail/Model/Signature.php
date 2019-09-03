<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold Signature data
 * 
 * @package   Felamimail
 * @subpackage    Model
 */
class Felamimail_Model_Signature extends Tinebase_Record_NewAbstract
{
    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
         self::VERSION => 2,
        'recordName' => 'Signature',
        'recordsName' => 'Signatures', // ngettext('Signature', 'Signatures', n)
        'hasRelations' => false,
        'copyRelations' => false,
        'hasCustomFields' => false,
        'hasSystemCustomFields' => false,
        'hasNotes' => false,
        'hasTags' => false,
        'modlogActive' => true,
        'hasAttachments' => false,
        'createModule' => false,
        'exposeHttpApi' => false,
        'exposeJsonApi' => false,
        'multipleEdit' => false,

        'titleProperty' => 'name',
        'appName' => 'Felamimail',
        'modelName' => 'Signature',

        'table'             => array(
            'name'    => 'felamimail_signature',
            'indexes' => array(
                'account_id' => array(
                    'columns' => array('account_id')
                ),
            ),
        ),

        self::FIELDS => [
            'account_id' => [
                self::TYPE => self::TYPE_STRING,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::DEFAULT_VALUE => null
                ],
                self::LENGTH => 40,
                self::SHY => true,
            ],
            'name' => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::LABEL => 'Name', // _('Name')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => false],
                self::QUERY_FILTER => true,
            ],
            'is_default' => [
                self::TYPE => self::TYPE_BOOLEAN,
                self::NULLABLE => true,
                self::LABEL => 'Is default', // _('Is default')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'signature' => [
                self::TYPE => self::TYPE_TEXT,
                self::LENGTH => 16777215,
                self::NULLABLE => true,
                self::LABEL => 'Signature', // _('Signature')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
        ]
    ];
}
