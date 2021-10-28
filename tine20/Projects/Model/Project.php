<?php

/**
 * class to hold Project data
 * 
 * @package     Projects
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * class to hold Project data
 * 
 * @package     Projects
 * @subpackage  Model
 */
class Projects_Model_Project extends Tinebase_Record_NewAbstract
{
    public const FLD_DESCRIPTION = 'description';
    public const FLD_END = 'end';
    public const FLD_NUMBER = 'number';
    public const FLD_SCOPE = 'scope';
    public const FLD_START = 'start';
    public const FLD_STATUS = 'status';
    public const FLD_TITLE = 'title';
    public const FLD_TYPE = 'type';

    public const MODEL_NAME_PART = 'Project';
    public const TABLE_NAME = 'projects_project';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 6,
        self::MODLOG_ACTIVE => true,

        self::APP_NAME => Projects_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::RECORD_NAME => 'Project',
        self::RECORDS_NAME => 'Projects', // ngettext('Project', 'Projects', n)
        self::TITLE_PROPERTY => self::FLD_TITLE,

        self::CONTAINER_PROPERTY => 'container_id',
        self::CONTAINER_NAME     => 'Project list',
        self::CONTAINERS_NAME    => 'Project lists', // ngettext('Project list', 'Project lists', n)

        self::HAS_ATTACHMENTS => true,
        self::HAS_CUSTOM_FIELDS => true,
        self::HAS_NOTES => true,
        self::HAS_RELATIONS => true,
        self::HAS_TAGS => true,

        self::EXPOSE_HTTP_API => true,
        self::EXPOSE_JSON_API => true,
        self::CREATE_MODULE => true,

        self::DEFAULT_SORT_INFO => ['field' => 'number', 'direction' => 'DESC'],

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::INDEXES       => [
                self::FLD_DESCRIPTION => [
                    self::COLUMNS               => [self::FLD_DESCRIPTION],
                    self::FLAGS                 => [self::TYPE_FULLTEXT],
                ],
            ],
        ],

        self::FILTER_MODEL => [
            // relation filters
            'contact' => [
                'filter' => 'Tinebase_Model_Filter_Relation', 'options' => [
                    'related_model' => 'Addressbook_Model_Contact',
                    'filtergroup' => 'Addressbook_Model_ContactFilter'
                ]
            ],
        ],

        self::FIELDS => [
            self::FLD_NUMBER => [
                self::TYPE => self::TYPE_STRING,
                self::QUERY_FILTER => true,
                self::LABEL => 'Number', // _('Number')
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ]
            ],
            self::FLD_TITLE => [
                self::TYPE => self::TYPE_STRING,
                self::QUERY_FILTER => true,
                self::LABEL => 'Name', // _('Name')
                self::LENGTH => 255,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED,
                ]
            ],
            self::FLD_DESCRIPTION => [
                self::TYPE => self::TYPE_FULLTEXT,
                self::QUERY_FILTER => true,
                self::LABEL => 'Description', // _('Description')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ]
            ],
            self::FLD_STATUS => [
                self::TYPE => self::TYPE_KEY_FIELD,
                self::LABEL => 'Status', // _('Status')
                self::NAME => Projects_Config::PROJECT_STATUS,
                self::LENGTH => 40,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::DEFAULT_VALUE => 'IN-PROCESS',
                ]
            ],
            self::FLD_START => [
                self::TYPE => self::TYPE_DATE,
                self::LABEL => 'Start', // _('Start')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ]
            ],
            self::FLD_END => [
                self::TYPE => self::TYPE_DATE,
                self::LABEL => 'End', // _('End')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ]
            ],
            self::FLD_SCOPE => [
                self::LABEL => 'Scope', // _('Scope')
                self::NULLABLE => true,
                self::TYPE => self::TYPE_KEY_FIELD,
                self::LENGTH => 64,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::NAME => Projects_Config::PROJECT_SCOPE,
            ],
            self::FLD_TYPE => [
                self::LABEL => 'Type', // _('Type')
                self::NULLABLE => true,
                self::TYPE => self::TYPE_KEY_FIELD,
                self::LENGTH => 128,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::NAME => Projects_Config::PROJECT_TYPE,
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
    
    /**
     * @see Tinebase_Record_Abstract
     */
    protected static $_relatableConfig = array(
        array('relatedApp' => 'Addressbook', 'relatedModel' => 'Contact',
            'keyfieldConfig' => array('from' => 'own', 'name' => 'projectAttendeeRole'),
            'default' => array('type' => 'COWORKER', 'related_degree' => 'sibling')
        )
    );
}

