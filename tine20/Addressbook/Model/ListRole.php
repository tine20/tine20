<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2016-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class Addressbook_Model_ListRole extends Tinebase_Record_NewAbstract
{
    public const FLD_NAME = 'name';
    public const FLD_DESCRIPTION = 'description';
    public const FLD_MAX_MEMBERS = 'max_members';

    public const MODEL_NAME_PART = 'ListRole';
    public const TABLE_NAME = 'addressbook_list_role';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::MODLOG_ACTIVE => true,

        self::APP_NAME => Addressbook_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        // ngettext('List Function', 'List Functions', n)
        // _('GENDER_List Function')
        self::RECORD_NAME => 'List Function',
        self::RECORDS_NAME => 'List Functions',
        self::TITLE_PROPERTY => 'name',

        self::EXPOSE_HTTP_API => true,
        self::EXPOSE_JSON_API => true,
        self::CREATE_MODULE => false,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
        ],

        self::FIELDS => [
            self::FLD_NAME => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::LABEL => 'Name', // _('Name')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::QUERY_FILTER              => true,
            ],
            self::FLD_DESCRIPTION => array(
                self::LABEL => 'Description', //_('Description')
                self::TYPE => self::TYPE_TEXT,
                self::LENGTH => \Doctrine\DBAL\Platforms\MySqlPlatform::LENGTH_LIMIT_MEDIUMTEXT,
                self::NULLABLE => true,
                self::VALIDATORS => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
            ),
            self::FLD_MAX_MEMBERS => array(
                self::LABEL => 'Maximum Members', //_('Maximum Members')
                self::TYPE => self::TYPE_INTEGER,
                self::NULLABLE => true,
                self::VALIDATORS => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
            ),
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;

    /**
     * returns true if this record should be replicated
     *
     * @return boolean
     */
    public function isReplicable()
    {
        return true;
    }
}
