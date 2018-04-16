<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2008-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Pagination Class
 * @package Tinebase
 *
 * @property string id
 * @property string start
 * @property string limit
 * @property string|array sort
 * @property string|array dir
 * @property string model
 */
class Tinebase_Model_Pagination extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
    /**
     * validators
     * 
     * @var array
     */
    protected $_validators = array(
        'id'                   => array('allowEmpty'    => true,
                                        'Int'                           ),
        'start'                => array('allowEmpty'    => true,
                                        'Int',
                                        'default'       => 0            ),
        'limit'                => array('allowEmpty'    => true,  
                                        'Int',
                                        'default'       => 0            ),
        // can be array for multiple sort rows
        'sort'                 => array('allowEmpty'    => true,
                                        'default'       => NULL         ),
        // can be array of sort dirs for multiple sort rows
        'dir'                  => array('presence'      => 'required',
                                        'allowEmpty'    => false,
                                        'default'       => 'ASC'        ),
        'model'                => array('allowEmpty'    => true,
                                        'default'       => NULL         ),
    );
    
    /**
     * Appends pagination statements to a given select object
     * 
     * @param  Zend_Db_Select
     * @return void
     */
    public function appendPaginationSql($_select)
    {
        // check model for required joins etc.
        $this->appendModelConfig($_select);

        $this->appendLimit($_select);
        $this->appendSort($_select);
    }

    /**
     * Appends limit statement to a given select object
     *
     * @param  Zend_Db_Select $_select
     * @return void
     */
    public function appendModelConfig($_select)
    {
        if (empty($this->model) || empty($this->sort) || empty($this->dir)) {
            return;
        }

        /** @var Tinebase_Record_Abstract $model */
        $model = $this->model;
        $mapping = $model::getSortExternalMapping();
        if (null === ($mc = $model::getConfiguration())) {
            if (empty($mapping)) {
                return;
            }
            $virtualFields = [];
            $recordFields = [];
        } else {
            $virtualFields = $mc->getVirtualFields();
            $recordFields = $mc->recordsFields;
        }
        $this->sort = (array)$this->sort;
        $joinCount = 0;
        $joined = [];
        foreach ($this->xprops('sort') as &$field) {
            if (isset($mapping[$field])) {
                $mappingDef = $mapping[$field];
                if (isset($mappingDef['fieldCallback'])) {
                    $field = call_user_func($mappingDef['fieldCallback'], $field);
                }
                if (isset($joined[$mappingDef['table']])) {
                    continue;
                }
                $_select->joinLeft([$mappingDef['table'] => SQL_TABLE_PREFIX . $mappingDef['table']],
                    $mappingDef['on'], []);
                $joined[$mappingDef['table']] = true;

            } elseif (isset($virtualFields[$field]) && isset($virtualFields[$field]['type']) &&
                    $virtualFields[$field]['type'] === 'relation' && isset($virtualFields[$field]['config']) &&
                    isset($virtualFields[$field]['config']['appName']) &&
                    isset($virtualFields[$field]['config']['modelName']) &&
                    isset($virtualFields[$field]['config']['type'])) {

                ++$joinCount;
                $db = $_select->getAdapter();
                $relationName = 'relationPagi' . $joinCount;
                /** @var Tinebase_Record_Abstract $relatedModel */
                $relatedModel = $virtualFields[$field]['config']['appName'] . '_Model_' .
                    $virtualFields[$field]['config']['modelName'];
                if (null === ($relatedMC = $relatedModel::getConfiguration())) {
                    $e = new Tinebase_Exception_InvalidArgument('related model not a modelconfig model in pagination: '
                        . $relatedModel);
                    Tinebase_Exception::log($e);
                    continue;
                }

                $_select->joinLeft(
                    [$relationName => SQL_TABLE_PREFIX . 'relations'],
                    $db->quoteIdentifier([$relationName, 'own_id']) . ' = ' . $db->quoteIdentifier([
                            $mc->getTableName(), $mc->getIdProperty()
                        ]) . ' AND ' .
                    $db->quoteIdentifier([$relationName, 'own_model']) . ' =  "' . $model . '" AND ' .
                    $db->quoteIdentifier([$relationName, 'related_model']) . ' = "' . $relatedModel . '" AND ' .
                    $db->quoteIdentifier([$relationName, 'type']) . $db->quoteInto(' = ?',
                        $virtualFields[$field]['config']['type']),
                    []
                );

                $relatedTableName = $relatedMC->getTableName() . '_relPagi' . $joinCount;
                $_select->joinLeft(
                    [$relatedTableName => SQL_TABLE_PREFIX . $relatedMC->getTableName()],
                    $db->quoteIdentifier([$relationName, 'related_id']) . ' = ' .
                        $db->quoteIdentifier([$relatedTableName, $relatedMC->getIdProperty()]),
                    []
                );
                if (is_array($relatedMC->defaultSortInfo) && isset($relatedMC->defaultSortInfo['field'])) {
                    $field = $relatedMC->defaultSortInfo['field'];
                } else {
                    if (is_array($relatedMC->titleProperty)) {
                        $field = $relatedMC->titleProperty[1][0];
                    } else {
                        $field = $relatedMC->titleProperty;
                    }
                }
                $field = $relatedTableName . '.' . $field;

            } elseif (isset($recordFields[$field]) && isset($recordFields[$field]['type']) &&
                    $recordFields[$field]['type'] === 'record' && isset($recordFields[$field]['config']) &&
                    isset($recordFields[$field]['config']['appName']) &&
                    isset($recordFields[$field]['config']['modelName']) &&
                    isset($recordFields[$field]['config']['type'])) {

                ++$joinCount;
                $db = $_select->getAdapter();
                /** @var Tinebase_Record_Abstract $relatedModel */
                $relatedModel = $recordFields[$field]['config']['appName'] . '_Model_' .
                    $recordFields[$field]['config']['modelName'];
                if (null === ($relatedMC = $relatedModel::getConfiguration())) {
                    $e = new Tinebase_Exception_InvalidArgument('related model not a modelconfig model in pagination: '
                        . $relatedModel);
                    Tinebase_Exception::log($e);
                    continue;
                }

                $relatedTableName = $relatedMC->getTableName() . '_recPagi' . $joinCount;
                $_select->joinLeft(
                    [$relatedTableName => SQL_TABLE_PREFIX . $relatedMC->getTableName()],
                    $db->quoteIdentifier([$mc->getTableName(), $field]) . ' = ' .
                    $db->quoteIdentifier([$relatedTableName, $relatedMC->getIdProperty()]),
                    []
                );
                if (is_array($relatedMC->defaultSortInfo) && isset($relatedMC->defaultSortInfo['field'])) {
                    $field = $relatedMC->defaultSortInfo['field'];
                } else {
                    if (is_array($relatedMC->titleProperty)) {
                        $field = $relatedMC->titleProperty[1][0];
                    } else {
                        $field = $relatedMC->titleProperty;
                    }
                }
                $field = $relatedTableName . '.' . $field;
            }
        }
    }

    /**
     * Appends limit statement to a given select object
     *
     * @param  Zend_Db_Select $_select
     * @return void
     */
    public function appendLimit($_select)
    {
        if (!empty($this->limit)) {
            $start = ($this->start >= 0) ? $this->start : 0;
            $_select->limit($this->limit, $start);
        }
    }
    
    /**
     * Appends sort statement to a given select object
     * 
     * @param  Zend_Db_Select $_select
     * @return void
     */
    public function appendSort($_select)
    {
        if (!empty($this->sort) && !empty($this->dir)){
            $_select->order($this->_getSortCols());
        }        
    }
    
    /**
     * get columns for select order statement
     * 
     * @return array
     */
    protected function _getSortCols()
    {
        $order = array();
        foreach ((array)$this->sort as $index => $sort) {
            $order[] = $sort . ' ' . (is_array($this->dir)
                        ? $this->dir[$index]
                        : $this->dir
                    );
        }
        return $order;
    }
}
