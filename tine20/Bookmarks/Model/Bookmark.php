<?php
/**
 * Tine 2.0
 * 
 * @package     Bookmarks
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * @property string         url
 * @property string         name
 * @property string         description
 * @property int            open_count
 */
class Bookmarks_Model_Bookmark extends Tinebase_Record_Abstract
{

    const FLDS_URL = 'url';
    const FLDS_NAME = 'name';
    const FLDS_DESCRIPTION = 'description';
    const FLDS_ACCESS_COUNT = 'access_count';
    const FLDS_XPROPS = 'xprops';

    const MODEL_NAME_PART = 'Bookmark';
    const TABLE_NAME = 'bookmarks';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = Bookmarks_Config::APP_NAME;

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
        self::VERSION                   => 1,
        self::RECORD_NAME               => 'Bookmark',
        self::RECORDS_NAME              => 'Bookmarks', // ngettext('Bookmark', 'Bookmarks', n)
        self::CONTAINER_PROPERTY        => 'container_id',
        self::TITLE_PROPERTY            => 'name',
        self::HAS_PERSONAL_CONTAINER    => true,
        self::CONTAINER_NAME            => 'Bookmark list',
        self::CONTAINERS_NAME           => 'Bookmark lists', // ngettext('Bookmark list', 'Bookmarks lists', n)
        self::HAS_RELATIONS             => true,
        self::HAS_CUSTOM_FIELDS         => false,
        self::HAS_NOTES                 => true,
        self::HAS_TAGS                  => true,
        self::MODLOG_ACTIVE             => true,
        self::HAS_ATTACHMENTS           => true,

        self::CREATE_MODULE             => true,

        self::EXPOSE_HTTP_API           => true,
        self::EXPOSE_JSON_API           => true,

        self::APP_NAME                  => Bookmarks_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,
        

        self::TABLE => [
            self::NAME    => self::TABLE_NAME,
            self::INDEXES => [
                'container_id' => [
                    self::COLUMNS => ['container_id']
                ]
            ],
        ],

        self::FIELDS => [
            self::FLDS_URL => [
                self::TYPE          => self::TYPE_TEXT,
                self::NULLABLE      => false,
                self::VALIDATORS    => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL         => 'Url', // _('Url')
                self::QUERY_FILTER  => true
            ],
            self::FLDS_NAME => [
                self::TYPE          => self::TYPE_STRING,
                self::LENGTH        => 255,
                self::NULLABLE      => false,
                self::VALIDATORS    => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'],
                self::LABEL         => 'Name', // _('Name')
                self::QUERY_FILTER  => true
            ],
            self::FLDS_DESCRIPTION => [
                self::TYPE          => self::TYPE_FULLTEXT,
                self::NULLABLE      => true,
                self::VALIDATORS    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL         => 'Description', // _('Description')
            ],
            self::FLDS_ACCESS_COUNT => [
                self::TYPE          => self::TYPE_INTEGER,
                self::READ_ONLY     => true,
                self::UNSIGNED      => true,
                self::NULLABLE      => true,
                self::DEFAULT_VAL   => 0,
                self::VALIDATORS    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL         => 'Access count', // _('Access count')
            ],
            // grr we can't tell dialogs to not show fields yet ;-(
//            self::FLDS_XPROPS => [
//                self::TYPE          => self::TYPE_JSON,
//                self::SHY           => true,
//                self::NULLABLE      => true,
//            ],
        ]
    ];

    public function isReplicable()
    {
        return true;
    }
}
