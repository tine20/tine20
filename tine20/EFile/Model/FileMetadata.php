<?php
/**
 * Tine 2.0
 *
 * @package     EFile
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Model for metadata of files
 *
 * @package     EFile
 * @subpackage  Model
 */
class EFile_Model_FileMetadata extends Tinebase_Record_NewAbstract
{
    const FLD_DURATION_END = 'duration_end';
    const FLD_DURATION_START = 'duration_start';
    const FLD_COMMISSIONED_OFFICE = 'commissioned_office';

    const FLD_IS_HYBRID = 'is_hybrid';
    const FLD_PAPER_LOCATION = 'paper_file_location';
    
    const FLD_IS_CLOSED = 'is_closed';
    const FLD_FINAL_DECREE_DATE = 'final_decree_date';
    const FLD_FINAL_DECREE_BY = 'final_decree_by';
    const FLD_RETENTION_PERIOD = 'retention_period';
    const FLD_RETENTION_PERIOD_END_DATE = 'retention_period_end_date';
    
    const FLD_IS_DISPOSED = 'is_disposed';
    const FLD_DISPOSAL_TYPE = 'disposal_type';
    const FLD_DISPOSAL_DATE = 'disposal_date';
    const FLD_ARCHIVE_NAME = 'archive_name';

    const FLD_NODE_ID = 'node_id';
    const MODEL_NAME_PART = 'FileMetadata';
    const TABLE_NAME = 'efile_filemetadata';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::MODLOG_ACTIVE => true,
        self::IS_DEPENDENT => true,

        self::APP_NAME => EFile_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::ASSOCIATIONS => [
            // this morphs into a one_to_one since node_id is unique too
            ClassMetadataInfo::MANY_TO_ONE => [
                self::FLD_NODE_ID => [
                    'targetEntity' => Tinebase_Model_Tree_Node::class,
                    'fieldName' => self::FLD_NODE_ID,
                    'joinColumns' => [[
                        'name' => self::FLD_NODE_ID,
                        'referencedColumnName' => 'id',
                        'onDelete' => 'CASCADE',
                    ]],
                ]
            ],
            // a real many to one
            ClassMetadataInfo::MANY_TO_ONE => [
                self::FLD_FINAL_DECREE_BY => [
                    'targetEntity' => Addressbook_Model_Contact::class,
                    'fieldName' => self::FLD_FINAL_DECREE_BY,
                    'joinColumns' => [[
                        'name' => self::FLD_FINAL_DECREE_BY,
                        'referencedColumnName' => 'id',
                    ]],
                ]
            ],
        ],

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::INDEXES => [
                self::FLD_FINAL_DECREE_BY => [
                    self::COLUMNS => [self::FLD_FINAL_DECREE_BY]
                ]
            ],
            self::UNIQUE_CONSTRAINTS => [
                self::FLD_NODE_ID => [
                    self::COLUMNS => [self::FLD_NODE_ID]
                ],
            ],
        ],

        self::FIELDS => [
            self::FLD_NODE_ID => [
                self::TYPE => self::TYPE_RECORD,
                self::LENGTH => 40,
                self::OMIT_MOD_LOG => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::CONFIG => [
                    self::APP_NAME => Tinebase_Config::APP_NAME,
                    self::MODEL_NAME => 'Tree_Node',
                    self::IS_DEPENDENT => true,
                ]
            ],
            
            self::FLD_DURATION_START => [
                self::TYPE => self::TYPE_DATE,
                self::DEFAULT_VAL => 'CURRENT_DATE', // TODO <- use constant?
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL => 'Duration Start', // _('Duration Start')
            ],
            self::FLD_DURATION_END => [
                self::TYPE => self::TYPE_DATE,
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL => 'Duration End', // _('Duration End')
            ],
            self::FLD_COMMISSIONED_OFFICE => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
                self::LABEL => 'Commissioned Office', // _('Commissioned Office')
            ],
            self::FLD_IS_HYBRID => [
                self::TYPE => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL => 0,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL => 'File is Hybrid (a corresponding paper file exists)', // _('File is Hybrid (a corresponding paper file exists)');
            ],
            self::FLD_PAPER_LOCATION => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL => 'Location of Paper File', // _('Location of Paper File')
            ],
            
            self::FLD_IS_CLOSED => [
                self::TYPE => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL => 0,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL => 'File is Closed', // _('File is Closed')
                self::UI_CONFIG => [
                    'group' => 'Final Decree' // _('Final Decree');
                ],
            ],
            self::FLD_FINAL_DECREE_DATE => [
                self::TYPE => self::TYPE_DATE,
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL => 'Final Decree Date', // _('Final Decree Date')
                self::UI_CONFIG => [
                    'group' => 'Final Decree' // _('Final Decree');
                ],
            ],
            self::FLD_FINAL_DECREE_BY => [
                self::TYPE => self::TYPE_RECORD,
                self::LENGTH => 40,
                self::NULLABLE => true,
                self::OMIT_MOD_LOG => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::CONFIG => [
                    self::APP_NAME => Addressbook_Config::APP_NAME,
                    self::MODEL_NAME => Addressbook_Model_Contact::MODEL_PART_NAME,
                ],
                self::LABEL => 'Final Decree by', // _('Final Decree by')
                self::UI_CONFIG => [
                    'group' => 'Final Decree' // _('Final Decree');
                ],
            ],
            self::FLD_RETENTION_PERIOD => [
                self::TYPE => self::TYPE_KEY_FIELD,
                self::NAME => EFile_Config::RETENTION_PERIOD,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    // this keyfield will use the default from the keyfield config, so it may be empty -> default
                ],
                self::LABEL => 'Retention Period', // _('Retention Period')
                self::UI_CONFIG => [
                    'group' => 'Final Decree' // _('Final Decree');
                ],
            ],
            self::FLD_RETENTION_PERIOD_END_DATE => [
                self::TYPE => self::TYPE_DATE,
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL => 'Retention Period End Date', // _('Retention Period End Date')
                self::UI_CONFIG => [
                    'group' => 'Final Decree' // _('Final Decree');
                ],
            ],
            
            
            self::FLD_IS_DISPOSED => [
                self::TYPE => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL => 0,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL => 'File is Disposed', // _('File is Disposed')
                self::UI_CONFIG => [
                    'group' => 'Disposal' // _('Disposal');
                ],
            ],
            self::FLD_DISPOSAL_TYPE => [
                self::TYPE => self::TYPE_KEY_FIELD,
                self::NAME => EFile_Config::DISPOSAL_TYPE,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    // this keyfield will use the default from the keyfield config, so it may be empty -> default
                ],
                self::LABEL => 'Disposal Type', // _('Disposal Type')
                self::UI_CONFIG => [
                    'group' => 'Disposal' // _('Disposal');
                ],
            ],
            self::FLD_DISPOSAL_DATE => [
                self::TYPE => self::TYPE_DATE,
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL => 'Disposal Date', // _('Disposal Date')
                self::UI_CONFIG => [
                    'group' => 'Disposal' // _('Disposal');
                ],
            ],
            self::FLD_ARCHIVE_NAME => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::LABEL => 'Archive Name', // _('Archive Name')
                self::UI_CONFIG => [
                    'group' => 'Disposal' // _('Disposal');
                ],
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
