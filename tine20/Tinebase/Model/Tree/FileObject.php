<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold data representing one object which can be inserted into the tree
 * 
 * @property  string            $name
 * @property  string            $revision
 * @property  array             $available_revisions
 * @property  string            $description
 * @property  string            $contenttype
 * @property  integer           $size
 * @property  integer           $revision_size
 * @property  string            $indexed_hash
 * @property  string            $hash
 * @property  string            $type
 * @property  integer           $preview_count
 * @property  integer           $preview_status
 * @property  integer           $preview_error_count
 * @property  Tinebase_DateTime $lastavscan_time
 * @property  boolean           $is_quarantined
 */
class Tinebase_Model_Tree_FileObject extends Tinebase_Record_Abstract
{
    public const TABLE_NAME = 'tree_fileobjects';

    public const DEFAULT_CONTENT_TYPE = 'application/octet-stream';

    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * object type: folder
     * 
     * @var string
     */
    const TYPE_FOLDER = 'folder';
    
    /**
     * object type: file
     * 
     * @var string
     */
    const TYPE_FILE   = 'file';

    /**
     * object type: preview
     *
     * @var string
     */
    const TYPE_PREVIEW = 'preview';

    /**
     * object type: link
     *
     * @var string
     */
    const TYPE_LINK = 'link';

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
        self::VERSION       => 7,
        'modlogActive'      => true,

        'appName'           => 'Tinebase',
        'modelName'         => 'Tree_FileObject',
        'idProperty'        => 'id',
        'table'             => [
            'name'              => 'tree_fileobjects',
            self::INDEXES => [
                'type'             => [
                    self::COLUMNS           => ['type']
                ],
                'is_deleted'        => [
                    self::COLUMNS           => ['is_deleted']
                ],
                'description'        => [
                    self::COLUMNS           => ['description'],
                    self::FLAGS             => ['fulltext'],
                ]
            ]
        ],

        'fields'            => [
            'revision'                      => [
                self::TYPE                      => self::TYPE_BIGINT,
                self::LENGTH                    => 64,
                self::NULLABLE                  => true,
                self::DEFAULT_VAL               => 0,
                self::UNSIGNED                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'type'                          => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 64,
                self::VALIDATORS                => [
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED,
                    ['InArray', [self::TYPE_FOLDER, self::TYPE_FILE, self::TYPE_PREVIEW, self::TYPE_LINK]]
                ],
            ],
            'contenttype'                   => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 128,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => self::DEFAULT_CONTENT_TYPE
                ],
                self::INPUT_FILTERS             => [Zend_Filter_StringToLower::class],
            ],
            // to preserve order we do this...
            'created_by'                    => null,
            'description'                   => [
                self::TYPE                      => self::TYPE_TEXT,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            // to preserve order we do this...
            'creation_time'                 => null,
            'last_modified_by'              => null,
            'last_modified_time'            => null,
            'is_deleted'                    => null,
            'deleted_by'                    => null,
            'deleted_time'                  => null,
            'seq'                           => null,
            'revision_size'                 => [
                self::TYPE                      => self::TYPE_BIGINT,
                self::LENGTH                    => 64,
                self::DEFAULT_VAL               => 0,
                self::UNSIGNED                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true, 'Digits'],
                self::OMIT_MOD_LOG              => true,
            ],
            'indexed_hash'                  => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 40,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::OMIT_MOD_LOG              => true,
            ],

            // doctrine ignore properties
            'size'                          => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::DOCTRINE_IGNORE           => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    'Digits',
                    Zend_Filter_Input::DEFAULT_VALUE => 0
                ],
            ],
            'preview_count'                 => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::DOCTRINE_IGNORE           => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    'Digits',
                    Zend_Filter_Input::DEFAULT_VALUE => 0
                ],
                self::OMIT_MOD_LOG              => true,
            ],
            'preview_status'                => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::DOCTRINE_IGNORE           => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    'Digits',
                    Zend_Filter_Input::DEFAULT_VALUE => 0
                ],
                self::OMIT_MOD_LOG              => true,
            ],
            'preview_error_count'           => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::DOCTRINE_IGNORE           => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    'Digits',
                    Zend_Filter_Input::DEFAULT_VALUE => 0
                ],
                self::OMIT_MOD_LOG              => true,
            ],
            'hash'                          => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 40,
                self::DOCTRINE_IGNORE           => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'lastavscan_time'               => [
                self::TYPE                      => self::TYPE_DATETIME,
                self::DOCTRINE_IGNORE           => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'is_quarantined'                => [
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::DOCTRINE_IGNORE           => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'available_revisions'           => [
                self::TYPE                      => self::TYPE_STRING,
                self::DOCTRINE_IGNORE           => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::OMIT_MOD_LOG              => true,
            ],
        ]
    ];

    protected static $_isReplicable = true;

    public static function modelConfigHook(array &$_definition)
    {
        // legacy :-/
        $_definition['is_deleted'][self::NULLABLE] = true;
        $_definition['is_deleted'][self::UNSIGNED] = true;
    }
    
    /**
     * converts a string or Addressbook_Model_List to a list id
     *
     * @param   string|Addressbook_Model_List  $_listId  the contact id to convert
     * 
     * @return  string
     * @throws  UnexpectedValueException  if no list id set 
     */
    static public function convertListIdToInt($_listId)
    {
        if ($_listId instanceof self) {
            if ($_listId->getId() == null) {
                throw new UnexpectedValueException('No identifier set.');
            }
            $id = (string) $_listId->getId();
        } else {
            $id = (string) $_listId;
        }
        
        if (empty($id)) {
            throw new UnexpectedValueException('Identifier can not be empty.');
        }
        
        return $id;
    }

    /**
     * returns real filesystem path
     * 
     * @param string $baseDir
     * @throws Tinebase_Exception_NotFound
     * @return string
     */
    public function getFilesystemPath($baseDir = NULL)
    {
        if (empty($this->hash)) {
            throw new Tinebase_Exception_NotFound('file object hash is missing');
        }
        
        if ($baseDir === NULL) {
            $baseDir = Tinebase_Core::getConfig()->filesdir;
        }
        
        return $baseDir . DIRECTORY_SEPARATOR . substr($this->hash, 0, 3) . DIRECTORY_SEPARATOR . substr($this->hash, 3);
    }

    public function runConvertToRecord()
    {
        if (isset($this->_properties['available_revisions']) && is_string($this->_properties['available_revisions'])) {
            $this->_properties['available_revisions'] = explode(',',
                ltrim(rtrim($this->_properties['available_revisions'], '}'), '{'));
        }

        parent::runConvertToRecord();
    }

    /**
     * @param bool $value
     * @return bool
     */
    public static function setReplicable($value)
    {
        $return = static::$_isReplicable;
        static::$_isReplicable = $value;
        return $return;
    }

    /**
     * returns true if this record should be replicated
     *
     * @return bool
     */
    public function isReplicable()
    {
        return static::$_isReplicable && (self::TYPE_FILE === $this->type || self::TYPE_FOLDER === $this->type);
    }
}
