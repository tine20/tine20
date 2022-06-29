<?php
/**
 * Tine 2.0
 *
 * @package     EFile
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2020-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * EFile config class
 *
 * @package     EFile
 * @subpackage  Config
 *
 */
class EFile_Config extends Tinebase_Config_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    const APP_NAME = 'EFile';

    const BASE_PATH = 'basePath';

    const NODE_NAME_DENIED_SUBSTRINGS = 'nodeNameDeniedSubstrigns';
    
    const RETENTION_PERIOD = 'retentionPeriod';
    const DISPOSAL_TYPE = 'disposalType';
    
    const TIER_REFNUMBER_PREFIX = 'tierRefNumberPrefix';
    const TIER_TOKEN_TEMPLATE = 'tierTokenTemplate';

    const TREE_NODE_FLD_FILE_METADATA = 'efile_file_metadata';
    const TREE_NODE_FLD_TIER_TYPE = 'efile_tier_type';
    const TREE_NODE_FLD_TIER_TOKEN = 'efile_tier_token';
    const TREE_NODE_FLD_TIER_COUNTER = 'efile_tier_counter';
    const TREE_NODE_FLD_TIER_REF_NUMBER = 'efile_tier_ref_number';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = self::APP_NAME;

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = [
        self::BASE_PATH                 => [
            self::LABEL                     => 'Base paths', //_('Base paths')
            self::DESCRIPTION               => 'Base paths', //_('Base paths')
            self::TYPE                      => self::TYPE_ARRAY,
            self::CLIENTREGISTRYINCLUDE     => true,
            self::SETBYADMINMODULE          => true,
            self::DEFAULT_STR               => ['/shared/'],
        ],
        self::NODE_NAME_DENIED_SUBSTRINGS => [
            self::TYPE                      => self::TYPE_ARRAY,
            self::CLIENTREGISTRYINCLUDE     => true,
            self::DEFAULT_STR               => [
                '#'
            ]
        ],
        self::RETENTION_PERIOD          => [
            self::LABEL                     => 'Retention Periods', //_('Retention Periods')
            self::DESCRIPTION               => 'Available retention periods', //_('Available retention periods')
            self::TYPE                      => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE     => true,
            self::DEFAULT_STR               => [
                self::RECORDS                   => [
                    ['id' => '6',  'value' => '6 Years',  'system' => 'true'], // _('6 Years')
                    ['id' => '10', 'value' => '10 Years', 'system' => 'true'], // _('10 Years')
                    ['id' => 'ETERNALLY', 'value' => 'Eternally', 'system' => 'true'], // _('Eternally')
                ],
                self::DEFAULT_STR               => '10',
            ]
        ],
        self::DISPOSAL_TYPE            => [
            self::LABEL                     => 'Disposal Type', //_('Disposal Type')
            self::DESCRIPTION               => 'Available disposal types', //_('Available disposal types')
            self::TYPE                      => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE     => true,
            self::DEFAULT_STR               => [
                self::RECORDS                   => [
                    ['id' => 'QUASHED',  'value' => 'Quashed',  'system' => 'true'], //_('Quashed')
                    ['id' => 'ARCHIVED', 'value' => 'Archived', 'system' => 'true'], //_('Archived')
                ],
                self::DEFAULT_STR               => 'ARCHIVED',
            ]
        ],
        self::TIER_REFNUMBER_PREFIX     => [
            self::LABEL                     => 'eFile Tier Reference Number Prefix', //_('eFile Tier Reference Number Prefix')
            self::DESCRIPTION               => 'eFile Tier Reference Number Prefix', //_('eFile Tier Reference Number Prefix')
            self::TYPE                      => self::TYPE_ARRAY,
            self::DEFAULT_STR               => [
                EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN_ROOT => '//',
                EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN => '.',
                EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP => '.',
                EFile_Model_EFileTierType::TIER_TYPE_FILE => '/',
                EFile_Model_EFileTierType::TIER_TYPE_SUB_FILE => '/',
                EFile_Model_EFileTierType::TIER_TYPE_CASE => '',
                EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR => '',
                EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT => '-',
            ],
        ],
        self::TIER_TOKEN_TEMPLATE       => [
            self::LABEL                     => 'eFile Token Template', //_('eFile Token Template')
            self::DESCRIPTION               => 'eFile Token Template', //_('eFile Token Template')
            self::TYPE                      => self::TYPE_ARRAY,
            self::DEFAULT_STR               => [
                EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN => '%02d',
                EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP => '%02d',
                EFile_Model_EFileTierType::TIER_TYPE_FILE => '%06d',
                EFile_Model_EFileTierType::TIER_TYPE_SUB_FILE => '%03d',
                EFile_Model_EFileTierType::TIER_TYPE_CASE => '#%06d',
                EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT => '%06d',
            ],
        ],
        EFile_Model_EFileTierType::MODEL_NAME_PART => [
            self::LABEL                     => 'eFile Tier Type', //_('eFile Tier Type')
            self::DESCRIPTION               => 'eFile Tier Type', //_('eFile Tier Type')
            self::TYPE                      => self::TYPE_KEYFIELD_CONFIG,
            self::OPTIONS                   => [self::RECORD_MODEL => EFile_Model_EFileTierType::class],
            self::CLIENTREGISTRYINCLUDE     => true,
            self::DEFAULT_STR               => [
                self::RECORDS                   => [
                    ['id' => EFile_Model_EFileTierType::TIER_TYPE_CASE, 'value' => 'Case', 'system' => true], //_('Case')
                    ['id' => EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT, 'value' => 'Document', 'system' => true], //_('Document')
                    ['id' => EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR, 'value' => 'Document Directory', 'system' => true], //_('Document Directory')
                    ['id' => EFile_Model_EFileTierType::TIER_TYPE_FILE, 'value' => 'File', 'system' => true], //_('File')
                    ['id' => EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP, 'value' => 'File Group', 'system' => true], //_('File Group')
                    ['id' => EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN, 'value' => 'Master Plan', 'system' => true], //_('Master Plan')
                    ['id' => EFile_Model_EFileTierType::TIER_TYPE_SUB_FILE, 'value' => 'Sub File', 'system' => true], //_('Sub File')
                ],
                self::DEFAULT_STR               => null,
            ],
        ],
    ];

    public static function getNodeLIVR()
    {
        $basePath = self::getInstance()->{self::BASE_PATH};
        static::$EFileNodeLIVR['name']['if']['then']['name'][0]['if']['condition']['parent']['nested_object']['path'][1]['one_of'] = $basePath;

        return static::$EFileNodeLIVR;
    }

    protected static $EFileNodeLIVR = [
        'name' => ['if' => [
            'condition' => [
                'type' => ['eq' => Tinebase_Model_Tree_FileObject::TYPE_FOLDER]
            ],
            'then' => ['name' => [[
                'if' => [
                    'condition' => [
                        'parent' => [
                            'nested_object' => [
                                'path' => ['required', ['one_of' => ['/shared/']]]
                            ],
                        ],
                    ],
                    'then' => [
                        EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required', ['eq' => EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN]]
                    ],
                    'else' => [
                        'parent' => ['required', [
                            'nested_object' => [
                                EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required']
                            ],
                        ]],
                    ]
                ]
            ], [
                'if' => [
                    'condition' => [
                        EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['notEqualToFieldPath' => 'oldrecord.' . EFile_Config::TREE_NODE_FLD_TIER_TYPE],
                        'oldrecord' => ['required', [
                            'nested_object' => [
                                EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required']
                            ],
                        ]],
                    ],
                    'then' => [
                        'oldrecord' => ['required', [
                            'nested_object' => [
                                EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['one_of' => [
                                    EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN,
                                    EFile_Model_EFileTierType::TIER_TYPE_CASE,
                                    EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR,
                                ]]
                            ],
                        ]],
                        'name' => [[
                            'if' => [
                                'condition' => [
                                    'oldrecord' => [
                                        'nested_object' => [
                                            EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['eq' => EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN]
                                        ],
                                    ],
                                ],
                                'then' => [
                                    EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required', ['eq' => EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP]],
                                    'hasChildren' => ['required', ['eq' => 0]]
                                ]
                            ]
                        ], [
                            'if' => [
                                'condition' => [
                                    'oldrecord' => [
                                        'nested_object' => [
                                            EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['eq' => EFile_Model_EFileTierType::TIER_TYPE_CASE]
                                        ],
                                    ],
                                ],
                                'then' => [
                                    EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required', ['eq' => EFile_Model_EFileTierType::TIER_TYPE_SUB_FILE]],
                                ]
                            ]
                        ], [
                            'if' => [
                                'condition' => [
                                    'oldrecord' => [
                                        'nested_object' => [
                                            EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['eq' => EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR]
                                        ],
                                    ],
                                ],
                                'then' => [
                                    EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required', ['one_of' => [
                                        EFile_Model_EFileTierType::TIER_TYPE_SUB_FILE,
                                        EFile_Model_EFileTierType::TIER_TYPE_CASE,
                                    ]]],
                                ]
                            ]
                        ]]
                    ]
                ]
            ], [
                'if' => [
                    'condition' => [
                        'parent' => [
                            'nested_object' => [
                                EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required', ['eq' => EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN]]
                            ],
                        ],
                    ],
                    'then' => [
                        EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required', ['one_of' => [
                            EFile_Model_EFileTierType::TIER_TYPE_MASTER_PLAN,
                            EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP
                        ]]]
                    ]
                ]
            ], [
                'if' => [
                    'condition' => [
                        'parent' => [
                            'nested_object' => [
                                EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required', ['eq' => EFile_Model_EFileTierType::TIER_TYPE_FILE_GROUP]]
                            ],
                        ],
                    ],
                    'then' => [
                        EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required', ['eq' => EFile_Model_EFileTierType::TIER_TYPE_FILE]]
                    ]
                ]
            ], [
                'if' => [
                    'condition' => [
                        'parent' => [
                            'nested_object' => [
                                EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required', ['eq' => EFile_Model_EFileTierType::TIER_TYPE_FILE]]
                            ],
                        ],
                    ],
                    'then' => [
                        EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required', ['one_of' => [
                            EFile_Model_EFileTierType::TIER_TYPE_SUB_FILE,
                            EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR,
                            EFile_Model_EFileTierType::TIER_TYPE_CASE
                        ]]]
                    ]
                ],
            ], [
                'if' => [
                    'condition' => [
                        'parent' => [
                            'nested_object' => [
                                EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required', ['eq' => EFile_Model_EFileTierType::TIER_TYPE_SUB_FILE]]
                            ],
                        ],
                    ],
                    'then' => [
                        EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required', ['one_of' => [
                            EFile_Model_EFileTierType::TIER_TYPE_SUB_FILE,
                            EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR,
                            EFile_Model_EFileTierType::TIER_TYPE_CASE
                        ]]]
                    ]
                ]
            ], [
                'if' => [
                    'condition' => [
                        'parent' => [
                            'nested_object' => [
                                EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required', ['eq' => EFile_Model_EFileTierType::TIER_TYPE_CASE]]
                            ],
                        ],
                    ],
                    'then' => [
                        EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required', ['eq' => EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR]]
                    ]
                ]
            ], [
                'if' => [
                    'condition' => [
                        'parent' => [
                            'nested_object' => [
                                EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required', ['eq' => EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR]]
                            ],
                        ],
                    ],
                    'then' => [
                        EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required', ['eq' => EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR]]
                    ]
                ]
            ]]
            ],
            'else' => ['name' => ['if' => [
                'condition' => [
                    'type' => ['required', ['eq' => Tinebase_Model_Tree_FileObject::TYPE_FILE]]
                ],
                'then' => [
                    EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['required', ['eq' => EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT]],
                    'parent' => ['required', ['nested_object' => [
                        EFile_Config::TREE_NODE_FLD_TIER_TYPE => ['one_of' => [
                            EFile_Model_EFileTierType::TIER_TYPE_FILE,
                            EFile_Model_EFileTierType::TIER_TYPE_SUB_FILE,
                            EFile_Model_EFileTierType::TIER_TYPE_DOCUMENT_DIR,
                            EFile_Model_EFileTierType::TIER_TYPE_CASE
                        ]]]]
                    ]
                ],
                'else' => ['doesnotexistthusfails' => ['required']],
            ]]
            ]
        ]
        ]
    ];

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}
