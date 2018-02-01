<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * tree node filter class
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Tree_Node_Filter extends Tinebase_Model_Filter_GrantsFilterGroup
{
    /**
     * @var string class name of this filter group
     *      this is needed to overcome the static late binding
     *      limitation in php < 5.3
     */
    protected $_className = 'Tinebase_Model_Tree_Node_Filter';
    
    /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'Tinebase_Model_Tree_Node';

    /**
     * @var string acl table name
     */
    protected $_aclTableName = 'tree_node_acl';

    /**
     * @var string acl record column for join with acl table
     */
    protected $_aclIdColumn = 'acl_node';

    /**
     * @var bool
     */
    protected $_ignorePinProtection = false;

    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'query'                 => array(
            'filter' => 'Tinebase_Model_Filter_Query', 
            'options' => array('fields' => array('name', 'content', 'description'))
        ),
        'id'                    => array('filter' => 'Tinebase_Model_Filter_Id'),
        'path'                  => array('filter' => 'Tinebase_Model_Tree_Node_PathFilter'),
        'parent_id'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'name'                  => array(
            'filter' => 'Tinebase_Model_Filter_Text',
            'options' => array('binary' => true)
        ),
        'object_id'             => array('filter' => 'Tinebase_Model_Filter_Text'),
        'acl_node'              => array('filter' => 'Tinebase_Model_Filter_Text'),
    // tree_fileobjects table
        'last_modified_time'    => array(
            'filter' => 'Tinebase_Model_Filter_Date',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'deleted_time'          => array(
            'filter' => 'Tinebase_Model_Filter_DateTime',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'creation_time'         => array(
            'filter' => 'Tinebase_Model_Filter_Date',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'last_modified_by'      => array(
            'filter' => 'Tinebase_Model_Filter_User',
            'options' => array('tablename' => 'tree_fileobjects'
        )),
        'created_by'            => array(
            'filter' => 'Tinebase_Model_Filter_User',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'type'                  => array(
            'filter' => 'Tinebase_Model_Filter_Text', 
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'contenttype'           => array(
            'filter' => 'Tinebase_Model_Filter_Text',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
        'description'           => array(
            'filter' => 'Tinebase_Model_Filter_FullText',
            'options' => array('tablename' => 'tree_fileobjects')
        ),
    // tree_filerevisions table
        'size'                  => array(
            'filter' => 'Tinebase_Model_Filter_Int',
            'options' => array('tablename' => 'tree_filerevisions')
        ),
    // recursive search
        'recursive'             => array(
            'filter' => 'Tinebase_Model_Filter_Bool'
        ),
        'tag'                   => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array(
            'idProperty' => 'tree_nodes.id',
            'applicationName' => 'Tinebase',
        )),
    // fulltext search
        'content'               => array(
            'filter'                => 'Tinebase_Model_Filter_ExternalFullText',
            'options'               => array(
                'idProperty'            => 'object_id',
            )
        ),
        'isIndexed'             => array(
            'filter'                => 'Tinebase_Model_Tree_Node_IsIndexedFilter',
        ),
        'is_deleted'            => array(
            'filter'                => 'Tinebase_Model_Filter_Bool'
        ),
        'quota'                 => array(
            'filter'                => Tinebase_Model_Filter_Int::class
        )
    );

    /**
     * set options
     *
     * @param array $_options
     */
    protected function _setOptions(array $_options)
    {
        if (isset($_options['nameCaseInSensitive']) && $_options['nameCaseInSensitive']) {
            $this->_filterModel['name']['options']['caseSensitive'] = false;
        }
        parent::_setOptions($_options);
    }

    /**
     * append grants acl filter
     *
     * @param Zend_Db_Select $select
     * @param Tinebase_Backend_Sql_Abstract $backend
     * @param Tinebase_Model_User $user
     */
    protected function _appendGrantsFilter($select, $backend, $user = null)
    {
        parent::_appendGrantsFilter($select, $backend, $user);

        if (!$this->_ignorePinProtection && !Tinebase_Auth_SecondFactor_Abstract::hasValidSecondFactor()) {
            $db = $backend->getAdapter();
            $uniqueId = uniqid('pinProtected');
            $select->joinLeft(array(
                /* table  */ $uniqueId => SQL_TABLE_PREFIX . $backend->getTableName()),
                /* on     */ "{$db->quoteIdentifier($uniqueId . '.id')} = {$db->quoteIdentifier($backend->getTableName() . '.' . $this->_aclIdColumn)}",
                /* select */ array()
            );
            $select->where("{$db->quoteIdentifier($uniqueId . '.pin_protected')} = 0 OR {$db->quoteIdentifier($uniqueId . '.pin_protected')} IS NULL");
        }

        // TODO do something when acl_node = NULL?
    }

    public function ignorePinProtection($_value = true)
    {
        $this->_ignorePinProtection = $_value;
    }

    /**
     * return folder + parent_id filter with ignore acl
     *
     * @param $folderId
     * @return Tinebase_Model_Tree_Node_Filter
     */
    public static function getFolderParentIdFilterIgnoringAcl($folderId)
    {
        return new Tinebase_Model_Tree_Node_Filter(array(
            array(
                'field'     => 'parent_id',
                'operator'  => $folderId === null ? 'isnull' : 'equals',
                'value'     => $folderId
            ), array(
                'field'     => 'type',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_Tree_FileObject::TYPE_FOLDER
            )
        ), Tinebase_Model_Filter_FilterGroup::CONDITION_AND, array('ignoreAcl' => true));
    }

    /**
     * check if filter is a recursive filter
     *
     * recursive must be set AND a recursive criteria must be given
     *
     * @return bool
     */
    public function isRecursiveFilter($removeIfNot = false)
    {
        if ($this->getFilter('recursive', false, true)) {
            foreach($this->getFilterModel() as $field => $config) {
                if ($filter = $this->getFilter($field, false, true)) {
                    if (in_array($field, ['path', 'type', 'recursive'])) continue;
                    if ($field == 'query' && !$filter->getValue()) continue;

                    return true;
                }
            }

            if ($removeIfNot) {
                $this->removeFilter('recursive', true);
            }
        }

        return false;
    }
}
