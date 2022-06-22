<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @var string application of this filter group
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * @var string name of model this filter group is designed for
     */
    protected $_configuredModel = Tinebase_Model_Tree_Node::class;

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
     * appends custom filters to a given select object
     *
     * @param  Zend_Db_Select                    $select
     * @param  Tinebase_Backend_Sql_Abstract     $backend
     * @return void
     */
    public function appendFilterSql($select, $backend)
    {
        parent::appendFilterSql($select, $backend);

        if (! $this->_ignorePinProtection
            && Tinebase_AreaLock::getInstance()->hasLock(Tinebase_Model_AreaLockConfig::AREA_DATASAFE)
            && Tinebase_AreaLock::getInstance()->isLocked(Tinebase_Model_AreaLockConfig::AREA_DATASAFE)
        ) {
            $db = $backend->getAdapter();
            $uniqueId = uniqid('pinProtected');
            $select->joinLeft(array(
                /* table  */ $uniqueId => SQL_TABLE_PREFIX . $backend->getTableName()),
                /* on     */ "{$db->quoteIdentifier($uniqueId . '.id')} = {$db->quoteIdentifier($backend->getTableName() . '.' . $this->_aclIdColumn)}",
                /* select */ array()
            );
            $select->where("{$db->quoteIdentifier($uniqueId . '.pin_protected_node')} IS NULL");
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
     * @param string|null $folderId
     * @param boolean $getDeleted
     * @return Tinebase_Model_Tree_Node_Filter
     */
    public static function getFolderParentIdFilterIgnoringAcl($folderId, $getDeleted = false)
    {
        $filterArr = [
            ['field' => 'parent_id', 'operator' => $folderId === null ? 'isnull' : 'equals', 'value' => $folderId],
            ['field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_FileObject::TYPE_FOLDER],
        ];
        if (true === $getDeleted) {
            $filterArr[] =
                ['field' => 'is_deleted', 'operator' => 'equals', 'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET];
        }
        return new Tinebase_Model_Tree_Node_Filter($filterArr, Tinebase_Model_Filter_FilterGroup::CONDITION_AND,
            ['ignoreAcl' => true]);
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
