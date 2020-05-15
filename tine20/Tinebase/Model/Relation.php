<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 *
 * @todo        add 'options' field and use it for (crm) remarks (product price/desc/quantity)
 */

/**
 * Tinebase_Model_Relation
 * Model of a record relation
 *
 * @package     Tinebase
 * @subpackage  Record
 *
 * @property string                     $own_model
 * @property string                     $own_backend
 * @property string                     $own_id
 * @property string                     $related_degree
 * @property string                     $related_model
 * @property string                     $related_backend
 * @property string                     $related_id
 * @property Tinebase_Record_Interface  $related_record
 * @property string                     $type
 * @property string                     $record_removed_reason
 */
class Tinebase_Model_Relation extends Tinebase_Record_Abstract
{
    /**
     * degree parent
     */
    const DEGREE_PARENT = 'parent';
    
    /**
     * degree child
     */
    const DEGREE_CHILD = 'child';
    
    /**
     * degree sibling
     */
    const DEGREE_SIBLING = 'sibling';

    /**
     * related record removed by acl
     */
    const REMOVED_BY_ACL = 'removedByAcl';

    /**
     * related record removed by acl
     */
    const REMOVED_BY_AREA_LOCK = 'removedByAreaLock';

    /**
     * related record removed by another reason
     */
    const REMOVED_BY_OTHER = 'removedByOther';

    /**
     * manually created relation
     */
    const TYPE_MANUAL = 'manual';
    
    /**
     * default record backend
     */
    const DEFAULT_RECORD_BACKEND = 'Sql';
    
    /**
     * key to find identifier
     */
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';

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
        'recordName'        => 'Relation',
        'recordsName'       => 'Relations', // ngettext('Relation', 'Relations', n)
        'hasRelations'      => false,
        'hasCustomFields'   => false,
        'hasNotes'          => false,
        'hasTags'           => false,
        'hasXProps'         => false,
        'modlogActive'      => true,
        'hasAttachments'    => false,
        'createModule'      => false,
        'exposeHttpApi'     => false,
        'exposeJsonApi'     => false,

        'appName'           => 'Tinebase',
        'modelName'         => 'Relation',

        'filterModel'       => [],

        'fields'            => [
            'own_model'                     => [
                'type'                          => 'string',
                'validators'                    => ['presence' => 'required', Zend_Filter_Input::ALLOW_EMPTY => false],
            ],
            'own_backend'                   => [
                'type'                          => 'string',
                'validators'                    => ['presence' => 'required', Zend_Filter_Input::ALLOW_EMPTY => false],
            ],
            'own_id'                        => [
                'type'                          => 'string',
                'validators'                    => ['presence' => 'required', Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            // NOTE: we use tree structure terms here, but relations do not represent a real tree
            // if this is set to PARENT, "own" record is child of "related" record
            // if this is set to CHILD, "own" record is parent of "related" record
            // if this is set to SIBLINGS, there is no parent/child relation
            'related_degree'                => [
                'type'                          => 'string',
                'validators'                    => [
                    'presence' => 'required',
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    'inArray' => [
                        self::DEGREE_PARENT,
                        self::DEGREE_CHILD,
                        self::DEGREE_SIBLING,
                    ]
                ],
            ],
            'related_model'                 => [
                'type'                          => 'string',
                'validators'                    => ['presence' => 'required', Zend_Filter_Input::ALLOW_EMPTY => false],
            ],
            'related_backend'               => [
                'type'                          => 'string',
                'validators'                    => ['presence' => 'required', Zend_Filter_Input::ALLOW_EMPTY => false],
            ],
            'related_id'                    => [
                'type'                          => 'string',
                'validators'                    => ['presence' => 'required', Zend_Filter_Input::ALLOW_EMPTY => false],
            ],
            'type'                          => [
                'type'                          => 'string',
                'validators'                    => ['presence' => 'required', Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            // freeform field for manual relations
            'remark'                        => [
                'type'                          => 'string',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            // "virtual" column - gives the reason why the related_record is not set
            'record_removed_reason'         => [
                'type'                          => 'string',
                'validators'                    => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    'inArray' => [
                        self::REMOVED_BY_AREA_LOCK,
                        self::REMOVED_BY_ACL,
                        self::REMOVED_BY_OTHER,
                    ]
                ],
            ],
            'related_record'                => [
                //'type'                          => 'record',
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
        ],
    ];
    
    /**
     * convert remark to array if json encoded
     *
     * @see Tinebase_Record_Abstract::setFromArray
     *
     * @param array $_data            the new data to set
     *
     * @todo    always json::encode remarks? / add options field that is always json encoded
     */
    public function setFromArray(array &$_data)
    {
        parent::setFromArray($_data);
        
        if ($this->remark && is_string($this->remark) && strpos($this->remark, '{') === 0) {
            $this->remark = Zend_Json::decode($this->remark);
        }
    }

    /**
     * @param Tinebase_Record_Interface|null $_parent
     * @param Tinebase_Record_Interface|null $_child
     * @return string
     */
    public function getPathPart(Tinebase_Record_Interface $_parent = null, Tinebase_Record_Interface $_child = null)
    {
        if ($this->related_degree === Tinebase_Model_Relation::DEGREE_PARENT) {
            if (null !== $_child) {
                $_child = $this;
            }
        } elseif ($this->related_degree === Tinebase_Model_Relation::DEGREE_CHILD) {
            if (null !== $_parent) {
                $_parent = $this;
            }
        } else {
            throw new Tinebase_Exception_UnexpectedValue('related degree needs to be parent or child, found: ' . $this->related_degree);
        }

        $parentType = null !== $_parent ? $_parent->getTypeForPathPart() : '';
        $childType = null !== $_child ? $_child->getTypeForPathPart() : '';
        // TODO allow empty titles?
        $title = isset($this->related_record) ? $this->related_record->getTitle() : null;

        return $parentType . '/' . mb_substr(str_replace(array('/', '{', '}'), '', trim($title)), 0, 1024) . $childType;
    }

    /**
     * @param Tinebase_Record_Interface|null $_parent
     * @param Tinebase_Record_Interface|null $_child
     * @return string
     */
    public function getShadowPathPart(Tinebase_Record_Interface $_parent = null, Tinebase_Record_Interface $_child = null)
    {
        if ($this->related_degree === Tinebase_Model_Relation::DEGREE_PARENT) {
            if (null !== $_child) {
                $_child = $this;
            }
        } elseif ($this->related_degree === Tinebase_Model_Relation::DEGREE_CHILD) {
            if (null !== $_parent) {
                $_parent = $this;
            }
        } else {
            throw new Tinebase_Exception_UnexpectedValue('related degree needs to be parent or child, found: ' . $this->related_degree);
        }

        $parentType = null !== $_parent ? $_parent->getTypeForPathPart() : '';
        $childType = null !== $_child ? $_child->getTypeForPathPart() : '';

        return $parentType . '/{' . $this->related_model . '}' . $this->related_id . $childType;
    }

    /**
     * @return string
     */
    public function getTypeForPathPart()
    {
        return !empty($this->type) ? '{' . $this->type . '}' : '';
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        /** @var Tinebase_Record_Interface $model */
        $model = $this->related_model;
        $relatedRecord = $this->related_record;
        if (is_array(($relatedRecord))) {
            $relatedRecord = new $model($relatedRecord, true);
        }
        return (class_exists($model) ? $model::getRecordName() : $model)
            . (empty($this->type) ? '' :  ' ' . $this->type)
            . ($relatedRecord instanceof Tinebase_Record_Interface ? ' ' . $relatedRecord->getTitle()
                : '');
    }

    /**
     * @param Tinebase_Record_RecordSet $_recordSetOne
     * @param Tinebase_Record_RecordSet $_recordSetTwo
     * @return null|Tinebase_Record_RecordSetDiff
     */
    public static function recordSetDiff(Tinebase_Record_RecordSet $_recordSetOne, Tinebase_Record_RecordSet $_recordSetTwo)
    {
        $setOne = [];
        $mapOne = [];
        /** @var self $relation */
        foreach ($_recordSetOne as $relation) {
            $key = $relation->related_model . $relation->related_id . $relation->related_degree .
                $relation->own_model . $relation->own_id . $relation->type;
            $setOne[] = $key;
            $mapOne[$key] = $relation;
        }

        $setTwo = [];
        $mapTwo = [];
        /** @var self $relation */
        foreach ($_recordSetTwo as $relation) {
            $key = $relation->related_model . $relation->related_id . $relation->related_degree .
                $relation->own_model . $relation->own_id . $relation->type;
            $setTwo[] = $key;
            $mapTwo[$key] = $relation;
        }

        $deleted = [];
        foreach (array_diff($setOne, $setTwo) as $delKey) {
            $deleted[] = $mapOne[$delKey];
        }
        $added = [];
        foreach (array_diff($setTwo, $setOne) as $addKey) {
            $added[] = $mapTwo[$addKey];
        }

        return new Tinebase_Record_RecordSetDiff([
                'model'     => static::class,
                'added'     => new Tinebase_Record_RecordSet(static::class, $added),
                'removed'   => new Tinebase_Record_RecordSet(static::class, $deleted),
                'modified'  => new Tinebase_Record_RecordSet(Tinebase_Record_Diff::class),
            ]);
    }
}
