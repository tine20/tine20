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
     * all valid fields
     * 
     * @todo add custom (Alnum + some chars like '-') validator for id fields
     */
    protected $_validators = array(
        'id'                     => array('allowEmpty' => true),
        'own_model'              => array('presence' => 'required', 'allowEmpty' => false),
        'own_backend'            => array('presence' => 'required', 'allowEmpty' => false),
        'own_id'                 => array('presence' => 'required', 'allowEmpty' => true),
        // NOTE: we use tree structure terms here, but relations do not represent a real tree
        // if this is set to PARENT, "own" record is child of "related" record
        // if this is set to CHILD, "own" record is parent of "related" record
        // if this is set to SIBLINGS, there is no parent/child relation
        'related_degree'         => array('presence' => 'required', 'allowEmpty' => false, array('InArray', array(
            self::DEGREE_PARENT,
            self::DEGREE_CHILD,
            self::DEGREE_SIBLING
        ))),
        'related_model'          => array('presence' => 'required', 'allowEmpty' => false),
        'related_backend'        => array('presence' => 'required', 'allowEmpty' => false),
        'related_id'             => array('presence' => 'required', 'allowEmpty' => false),
        'type'                   => array('presence' => 'required', 'allowEmpty' => true),
        'remark'                 => array('allowEmpty' => true          ), // freeform field for manual relations
        // "virtual" column - gives the reason why the related_record is not set
        'record_removed_reason'  => array('allowEmpty' => true, [ 'InArray', [
            self::REMOVED_BY_AREA_LOCK,
            self::REMOVED_BY_ACL,
            self::REMOVED_BY_OTHER,
        ]]),
        'related_record'         => array('allowEmpty' => true          ), // property to store 'resolved' relation record
        'created_by'             => array('allowEmpty' => true,         ),
        'creation_time'          => array('allowEmpty' => true          ),
        'last_modified_by'       => array('allowEmpty' => true,         ),
        'last_modified_time'     => array('allowEmpty' => true          ),
        'is_deleted'             => array('allowEmpty' => true          ),
        'deleted_time'           => array('allowEmpty' => true          ),
        'deleted_by'             => array('allowEmpty' => true,         ),
        'seq'                    => array('allowEmpty' => true,         ),
    );
    
    /**
     * fields containing datetime data
     */
    protected $_datetimeFields = array(
        'creation_time',
        'last_modified_time',
        'deleted_time'
    );
    
    /**
     * convert remark to array if json encoded
     *
     * @see Tinebase_Record_Abstract::setFromArray
     *
     * @param array $_data            the new data to set
     *
     * @todo    always json::encode remarks? / add options field that is always json encoded
     */
    public function setFromArray(array $_data)
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
        /** @var Tinebase_Record_Abstract $model */
        $model = $this->related_model;
        return (class_exists($model) ? $model::getRecordName() : $model)
            . (empty($this->type) ? '' :  ' ' . $this->type)
            . ($this->related_record instanceof Tinebase_Record_Abstract ? ' ' . $this->related_record->getTitle()
                : '');
    }
}
