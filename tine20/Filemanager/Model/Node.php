<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      AirMike <airmike23@gmail.com>
 * @copyright   Copyright (c) 2012-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold data representing one node in the tree
 * 
 * @package     Filemanager
 * @subpackage  Model
 * @property    string             contenttype
 * @property    Tinebase_DateTime  creation_time
 * @property    string             hash
 * @property    string             name
 * @property    Tinebase_DateTime  last_modified_time
 * @property    string             object_id
 * @property    string             size
 * @property    string             type
 */
class Filemanager_Model_Node extends Tinebase_Model_Tree_Node
{
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Filemanager';

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
        'hasRelations'      => true,
        'hasCustomFields'   => true,
        'hasNotes'          => true,
        'hasTags'           => true,
        'hasXProps'         => false,
        'modlogActive'      => true,
        'hasAttachments'    => false,
        'createModule'      => false,
        'exposeHttpApi'     => false,
        'exposeJsonApi'     => false,

        'titleProperty'     => 'name',
        'appName'           => 'Filemanager',
        'modelName'         => 'Node', // _('GENDER_Node')
        'idProperty'        => 'id',
        'table'             => [
            'name'              => 'tree_nodes',
        ],

        'filterModel'       => [
            'isIndexed'         => [
                'filter'            => Tinebase_Model_Tree_Node_IsIndexedFilter::class,
            ],
        ],

        'fields'            => [
            'parent_id'                     => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'object_id'                     => [
                'type'                          => 'string',
                'validators'                    => ['presence' => 'required'],
            ],
            'revisionProps'                 => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'notificationProps'             => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            // contains id of node with acl info
            'acl_node'                      => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'name'                          => [
                'type'                          => 'string',
                'validators'                    => ['presence' => 'required'],
            ],
            'islink'                        => [
                'type'                          => 'integer',
                'validators'                    => [Zend_Filter_Input::DEFAULT_VALUE => 0],
            ],
            'quota'                         => [
                'type'                          => 'integer',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            // fields from filemanager_objects table (ro)
            'type'                          => [
                'type'                          => 'string',
                'validators'                    => ['inArray' => [
                    Tinebase_Model_Tree_FileObject::TYPE_FILE,
                    Tinebase_Model_Tree_FileObject::TYPE_FOLDER,
                    Tinebase_Model_Tree_FileObject::TYPE_PREVIEW,
                    Tinebase_Model_Tree_FileObject::TYPE_LINK,
                ], Zend_Filter_Input::ALLOW_EMPTY => true,],
            ],
            'description'                   => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'contenttype'                   => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'revision'                      => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'available_revisions'           => [
                'type'                          => 'string',
                'modlogOmit'                    => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'hash'                          => [
                'type'                          => 'string',
                'modlogOmit'                    => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'indexed_hash'                  => [
                'type'                          => 'string',
                'modlogOmit'                    => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'isIndexed'                     => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'                  => [Zend_Filter_StringTrim::class => null],
            ],
            'size'                          => [
                'type'                          => 'integer',
                'validators'                    => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Empty::class => 0
                ],
            ],
            'revision_size'                 => [
                'type'                          => 'integer',
                'modlogOmit'                    => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'preview_count'                 => [
                'type'                          => 'integer',
                'validators'                    => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    'Digits',
                    Zend_Filter_Input::DEFAULT_VALUE => 0,
                ],
            ],

            'pin_protected_node'            => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],

            // not persistent
            'container_name'                => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],

            // this is needed should be sent by / delivered to client (not persistent in db atm)
            'path'                          => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'account_grants'                => [
                //'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tempFile'                      => [
                //'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'stream'                        => [
                //'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            // acl grants
            'grants'                        => [
                //'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
        ],
    ];

    /**
     * returns true if this record should be replicated
     *
     * @return boolean
     */
    public function isReplicable()
    {
        return Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $this->type;
    }

    /**
     * returns a URL with a deep link path to the node provided
     *
     * @param Tinebase_Model_Tree_Node $_record
     * @return string
     */
    public static function getDeepLink($_record)
    {
        if (empty($_record->path)) {
            $path = Tinebase_Model_Tree_Node_Path::createFromStatPath(Tinebase_FileSystem::getInstance()->getPathOfNode($_record, true));
            $_record->path = $path->flatpath;
        }

        $path = explode('/', ltrim(Tinebase_Model_Tree_Node_Path::removeAppIdFromPath($_record->path, 'Filemanager'), '/'));
        array_walk($path, function(&$val) {
            $val = urlencode($val);
        });

        return Tinebase_Core::getUrl() . '/#/Filemanager/showNode/' . join('/', $path);
    }
}
