<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Tinebase_Model_Filter_ForeignRecord
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * filters own ids match result of foreign filter
 */
abstract class Tinebase_Model_Filter_ForeignRecord extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'AND',
        1 => 'OR',
    );
    
    /**
     * @var Tinebase_Model_Filter_FilterGroup
     */
    protected $_filterGroup = NULL;
    
    /**
     * @var Tinebase_Controller_Record_Abstract
     */
    protected $_controller = NULL;
    
    /**
     * @var array
     */
    protected $_foreignIds = NULL;
        
    /**
     * creates corresponding filtergroup
     *
     * @param array $_value
     */
    public function setValue($_value) {
        $this->_foreignIds = NULL;
        $this->_value = (array)$_value;
        // @todo move this to another place?
        $this->_filterGroup = $this->_getFilterGroup();
        $this->_controller = $this->_getController();
    }
    
    /**
     * get foreign filter group
     * 
     * @return Tinebase_Model_Filter_FilterGroup
     */
    protected function _getFilterGroup()
    {
        return ($this->_filterGroup !== NULL) ? $this->_filterGroup 
            : new $this->_options['filtergroup']($this->_value, $this->_operator, $this->_options);
    }
    
    /**
     * get foreign controller
     * 
     * @return Tinebase_Controller_Record_Abstract
     */
    abstract protected function _getController();
    
    /**
     * set options 
     *
     * @param array $_options
     */
    protected function _setOptions(array $_options)
    {
        if (! array_key_exists('isGeneric', $_options)) {
            $_options['isGeneric'] = FALSE;
        }
        
        $this->_options = $_options;
    }
    
    /**
     * returns array with the filter settings of this filter
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = array(
            'field'     => $this->_field,
            'operator'  => $this->_operator,
        );
        
        $filters = $this->_getForeignFiltersForToArray($_valueToJson);
        
        if ($this->_options && $this->_options['isGeneric']) {
            $result['value'] = $this->_getGenericFilterInformation();
            $result['value']['filters'] = $filters;
        } else {
            $result['value'] = $filters;
        }
        
        return $result;
    }
    
    /**
     * returns filter group filters
     * 
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    protected function _getForeignFiltersForToArray($_valueToJson)
    {
        // we can't do this as we do not want the condition/filters syntax
        // $result = $this->_filterGroup->toArray($_valueToJson);
        $result = array();
        
        $filterObjects = $this->_filterGroup->getFilterObjects();
        foreach ($filterObjects as $filter) {
            $result[] = $filter->toArray($_valueToJson);
        }
        
        return $result;
    }
    
    /**
     * get filter information for toArray()
     * 
     * @return array
     */
    abstract protected function _getGenericFilterInformation();
}
