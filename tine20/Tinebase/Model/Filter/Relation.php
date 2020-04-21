<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Relation
 * 
 * filters own ids match result of related filter
 * 
 * <code>
 *      'contact'        => array('filter' => 'Tinebase_Model_Filter_Relation', 'options' => array(
 *          'related_model'     => 'Addressbook_Model_Contact',
 *          'filtergroup'    => 'Addressbook_Model_ContactFilter'
 *      )
 * </code>     
 * 
 * @package     Tinebase
 * @subpackage  Filter
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
     * @return Tinebase_Model_Filter_FilterGroup|null
     */
    protected function _setFilterGroup()
    {
        if ($this->_valueIsNull) {
            return null;
        }
        $filters = $this->_getRelationFilters();
        $this->_filterGroup = Tinebase_Model_Filter_FilterGroup::getFilterForModel(
            $this->_options['filtergroup'],
            $filters,
            $this->_operator
        );
        return $this->_filterGroup;
    }
    
    /**
     * set options 
     *
     * @param array $_options
     */
    protected function _setOptions(array $_options)
    {
        if (! isset($_options['related_model'])) {
            throw new Tinebase_Exception_UnexpectedValue('related model is needed in options');
        }

        if (! isset($_options['filtergroup'])) {
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
        if (isset($this->_options['own_model'])) {
            $ownModel = $this->_options['own_model'];
        } else {
            $ownModel = $_backend->getModelName();
        }
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' 
            . 'Adding Relation filter: ' . $ownModel . ' <-> ' . $this->_options['related_model']);

        $idField = (isset($this->_options['idProperty']) || array_key_exists('idProperty', $this->_options)) ? $this->_options['idProperty'] : 'id';
        $db = $_backend->getAdapter();
        $qField = $db->quoteIdentifier($_backend->getTableName() . '.' . $idField);

        if (!$this->_valueIsNull) {
            $this->_resolveForeignIds();
        }

        $ownIds = $this->_getOwnIds($ownModel);
        $notOperator = strpos($this->_operator, 'not') === 0;
        if ($this->_valueIsNull) {
            $notOperator = !$notOperator;
        }
        if (!$notOperator) {
            if (empty($ownIds)) {
                $_select->where('1=0');
            } else {
                $_select->where($db->quoteInto("$qField IN (?)", $ownIds));
            }
        } else {
            if (empty($ownIds)) {
                $_select->where('1=1');
            } else {
                $_select->where($db->quoteInto("$qField NOT IN (?)", $ownIds));
            }
        }
    }
    
    /**
     * resolve foreign ids
     */
    protected function _resolveForeignIds()
    {
        if (! is_array($this->_foreignIds)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' foreign filter values: ' 
                . print_r($this->_filterGroup->toArray(), TRUE));
            try {
                $this->_foreignIds = $this->_getController()->search($this->_filterGroup, null, false, true);
            } catch(Tinebase_Exception_AccessDenied $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' no access to related app ');
                $this->_foreignIds = [];
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' foreign ids: ' 
            . print_r($this->_foreignIds, TRUE));
    }
    
    /**
     * returns own ids defined by relation filter
     * 
     * @param string $_modelName
     * @return array
     */
    protected function _getOwnIds($_modelName)
    {
        $filter = array(
            array('field' => 'own_model',     'operator' => 'equals', 'value' => $_modelName),
            array('field' => 'related_model', 'operator' => 'equals', 'value' => $this->_options['related_model'])
        );
        if (null !== $this->_foreignIds) {
            $filter[] = array('field' => 'related_id', 'operator' => 'in'    , 'value' => $this->_foreignIds);
        }
        if (isset($this->_options['type'])) {
            $filter[] = array('field' => 'type',       'operator' => 'equals', 'value' => $this->_options['type']);
        }

        $relationFilter = new Tinebase_Model_RelationFilter($filter);
        
        if ($this->_relationTypeFilter) {
            $typeValue = $this->_relationTypeFilter['value'];
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' 
                . 'Adding Relation type filter: ' . ((is_array($typeValue)) ? implode(',', $typeValue) : $typeValue));
            $relationFilter->addFilter($relationFilter->createFilter('type', $this->_relationTypeFilter['operator'], $typeValue));
        }
        $ownIds = Tinebase_Relations::getInstance()->search($relationFilter, NULL)->own_id;

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' own ids: ' 
            . print_r($ownIds, TRUE));
        
        return $ownIds;
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
            
            if (isset($filters[$idx]['field']) && $filters[$idx]['field'] === 'relation_type') {
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
     * @param  array $_additionalFilters
     * @return array
     */
    protected function _getForeignFiltersForToArray($_valueToJson, $_additionalFilters = array())
    {
        $additionalFilters = ($this->_relationTypeFilter) ? array($this->_relationTypeFilter) : $_additionalFilters;
        $result = parent::_getForeignFiltersForToArray($_valueToJson, $additionalFilters);
        
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
