<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Relation
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * filters own ids match result of related filter
 * 
 * <code>
 *      'contact'        => array('filter' => 'Tinebase_Model_Filter_Relation', 'options' => array(
 *          'related_model'     => 'Addressbook_Model_Contact',
 *          'filtergroup'    => 'Addressbook_Model_ContactFilter'
 *      )
 * </code>     
 */
class Tinebase_Model_Filter_Relation extends Tinebase_Model_Filter_ForeignRecord
{
    /**
     * relation type filter data
     * 
     * @var array
     */
    protected $_relationTypeFilter = NULL;
    
    /**
     * the prefixed ("left") fields sent by the client
     * 
     * @var array
     * @todo perhaps we need this in Tinebase_Model_Filter_ForeignRecord later
     */
    protected $_prefixedFields = array();
    
    /**
     * get foreign controller
     * 
     * @return Tinebase_Controller_Record_Abstract
     */
    protected function _getController()
    {
        if ($this->_controller === NULL) {
            $this->_controller = Tinebase_Controller_Record_Abstract::getController($this->_options['related_model']);
        }
        
        return $this->_controller;
    }
    
    /**
     * get foreign filter group
     * 
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _getFilterGroup()
    {
        if ($this->_filterGroup === NULL) {
            $filters = $this->_getRelationFilters();
            $this->_filterGroup = new $this->_options['filtergroup']($filters, $this->_operator);
        }
            
        return $this->_filterGroup;
    }
    
    /**
     * set options 
     *
     * @param array $_options
     */
    protected function _setOptions(array $_options)
    {
        if (! array_key_exists('related_model', $_options)) {
            throw new Tinebase_Exception_UnexpectedValue('related model is needed in options');
        }

        if (! array_key_exists('filtergroup', $_options)) {
            $_options['filtergroup'] = $_options['related_model'] . 'Filter';
        }
        
        parent::_setOptions($_options);
    }
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        if (! is_array($this->_foreignIds)) {
            $this->_foreignIds = $this->_controller->search($this->_filterGroup, NULL, FALSE, TRUE);
        }
        
        $relationFilter = new Tinebase_Model_RelationFilter(array(
            array('field' => 'own_model',     'operator' => 'equals', 'value' => $_backend->getModelName()),
            array('field' => 'related_model', 'operator' => 'equals', 'value' => $this->_options['related_model']),
            array('field' => 'related_id',    'operator' => 'in'    , 'value' => $this->_foreignIds)
        ));
        
        if ($this->_relationTypeFilter) {
            $relationFilter->addFilter($relationFilter->createFilter('type', $this->_relationTypeFilter['operator'], $this->_relationTypeFilter['value']));
        }
        
        $ownIds = Tinebase_Relations::getInstance()->search($relationFilter, NULL)->own_id;
        
        $idField = array_key_exists('idProperty', $this->_options) ? $this->_options['idProperty'] : 'id';
        
        $db = $_backend->getAdapter();
        $qField = $db->quoteIdentifier($_backend->getTableName() . '.' . $idField);
        
        $_select->where($db->quoteInto("$qField IN (?)", empty($ownIds) ? ' ' : $ownIds));
    }
    
    /**
     * get relation filters
     * 
     * @return array
     */
    protected function _getRelationFilters()
    {
        $filters = $this->_value;   
        foreach ($filters as $idx => $filterData) {
            if (! isset($filterData['field'])) {
                continue;
            }
            
            if (strpos($filterData['field'], ':') !== FALSE) {
                $filters[$idx]['field'] = str_replace(':', '', $filterData['field']);
                $this->_prefixedFields[] = $filters[$idx]['field'];
            }
            
            if ($filters[$idx]['field'] === 'relation_type') {
                $this->_relationTypeFilter = $filters[$idx];
                unset($filters[$idx]);
            }
        }        
        
        return $filters;
    }
    
    /**
     * returns filter group filters
     * 
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    protected function _getForeignFiltersForToArray($_valueToJson)
    {
        $result = parent::_getForeignFiltersForToArray($_valueToJson);
        
        // add relation type again
        if ($this->_relationTypeFilter) {
            array_unshift($result, $this->_relationTypeFilter);
        }
        
        // return prefixes
        if (! empty($this->_prefixedFields)) {
            foreach ($result as $idx => $filterData) {
                if (isset($filterData['field']) && in_array($filterData['field'], $this->_prefixedFields)) {
                    $result[$idx]['field'] = ':' . $filterData['field'];
                }
            }
        }
        
        return $result;
    }
    
    /**
     * get filter information for toArray()
     * 
     * @return array
     */
    protected function _getGenericFilterInformation()
    {
        list($appName, $i, $modelName) = explode('_', $this->_options['related_model']);
            
        $result = array(
            'linkType'      => 'relation',
            'appName'       => $appName,
            'modelName'     => $modelName,
        );
        
        return $result;
    }    
}
