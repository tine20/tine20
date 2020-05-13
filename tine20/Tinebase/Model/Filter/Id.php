<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Id
 * 
 * filters one or more ids
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_Id extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        'equals',
        'not',
        'in',
        'notin',
        'isnull',
        'notnull',
    );
    
    /**
     * @var array maps abstract operators to sql operators
     */
    protected $_opSqlMap = array(
        'equals'     => array('sqlop' => ' = ?'   ),
        'not'        => array('sqlop' => ' != ?'  ),
        'in'         => array('sqlop' => ' IN (?)'),
        'notin'      => array('sqlop' => ' NOT IN (?)'),
        'isnull'     => array('sqlop' => ' IS NULL'),
        'notnull'    => array('sqlop' => ' IS NOT NULL'),
    );
    
    /**
     * controller for record resolving
     * 
     * @var Tinebase_Controller_Record_Abstract
     */
    protected $_controller = NULL;
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        $action = $this->_opSqlMap[$this->_operator];
         
        if (empty($this->_value) && $this->_value != '0') {
             // prevent sql error
             if ($this->_operator == 'in' || $this->_operator == 'equals') {
                 if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                     . ' Empty value with "' . $this->_operator . '"" operator (model: '
                     . (isset($this->_options['modelName']) ? $this->_options['modelName'] : 'unknown / no modelName defined in filter options'). ')');
                 $_select->where('1=0');
             } else if ($this->_operator == 'not') {

                 $field = $this->_getQuotedFieldName($_backend);
                 $_select->where($field . " != '' AND " . $field . " IS NOT NULL");
            }
        } else if ($this->_operator == 'equals' && is_array($this->_value)) {
             if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                 . ' Unexpected array value with "equals" operator (model: ' 
                 . (isset($this->_options['modelName']) ? $this->_options['modelName'] : 'unknown / no modelName defined in filter options') . ')');
             $_select->where('1=0');
         } else {
             $type = $this->_getFieldType($_backend);
             $this->_enforceValueType($type);
             
             $field = $this->_getQuotedFieldName($_backend);
             // finally append query to select object
             $_select->where($field . $action['sqlop'], $this->_value, $type);
         }
     }
     
     /**
      * get field type from schema
      * 
      * @param Tinebase_Backend_Sql_Abstract $backend
      */
     protected function _getFieldType($backend)
     {
         $schema = $backend->getSchema();
         if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
             . ' schema: ' . print_r($schema, TRUE));
         
         $type = isset($schema[$this->_field]) ? $schema[$this->_field]['DATA_TYPE'] : NULL;
         return $type;
     }
     
     /**
      * enforce value typecast
      * 
      * @param string $type
      * 
      * @todo add more type strings / move to db adapter?
      */
     protected function _enforceValueType($type)
     {
         switch (strtoupper($type)) {
             case 'VARCHAR':
             case 'TEXT':
                 $this->_enforceStringValue();
                 break;
             case 'INTEGER':
             case 'TINYINT':
             case 'SMALLINT':
             case 'INT':
                 $this->_enforceIntValue();
                 break;
             default:
                 // do not cast / enforce type
         }
     }
     
    /**
     * returns array with the filter settings of this filter
     *
     * @param  bool $_valueToJson resolve value for json api?
     * @return array
     */
    public function toArray($_valueToJson = false)
    {
        $result = parent::toArray($_valueToJson);
        
        if ($_valueToJson) {
            if (is_array($result['value'])) {
                foreach ($result['value'] as $key => $value) {
                    $result['value'][$key] = $this->_resolveRecord($value);
                }
            } else {
                $result['value'] = $this->_resolveRecord($result['value']);
            }
        }
        
        return $result;
    }
    
    /**
     * enforce string data type for correct sql quoting
     */
    protected function _enforceStringValue()
    {
        if (is_array($this->_value)) {
            foreach ($this->_value as &$value) {
                if (is_array($value)) {
                    // use the first element of the array
                    $value = array_pop($value);
                } else {
                    $value = (string) $value;
                }
            }
        } else {
            $this->_value = (string) $this->_value;
        }
    }
    
    /**
     * enforce integer data type for correct sql quoting
     */
    protected function _enforceIntValue()
    {
        if (is_array($this->_value)) {
            foreach ($this->_value as &$value) {
                if (! is_numeric($value)) {
                    throw new Tinebase_Exception_UnexpectedValue("$value is not a number");
                } 
                $value = (int) $value;
            }
        } else {
            if (! is_numeric($this->_value)) {
                throw new Tinebase_Exception_UnexpectedValue("$this->_value is not a number");
            } 
            $this->_value = (int) $this->_value;
        }
    }
    
    /**
     * get controller
     * 
     * @return Tinebase_Controller_Record_Abstract|null
     */
    protected function _getController()
    {
        if ($this->_controller === null) {
            if (isset($this->_options['controller'])) {
                $cname = $this->_options['controller'];
                $this->_controller = $cname::getInstance();
            } elseif (isset($this->_options['modelName'])) {
                $this->_controller = Tinebase_Core::getApplicationInstance($this->_options['modelName']);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__
                    . ' No modelName or controller defined in filter options, can not resolve record.');
                return null;
            }
        }
        
        return $this->_controller;
    }
    
    /**
     * resolves a record
     * 
     * @param string $value
     * @return array|string
     */
    protected function _resolveRecord($value)
    {
        $controller = $this->_getController();
        if ($controller === NULL) {
            return $value;
        }
        
        try {
            if (method_exists($controller, 'get')) {
                $recordArray = $controller->get($value, /* $_containerId = */ null, /* $_getRelatedData = */ false)->toArray();
            } else {
                Tinebase_Core::getLogger()->NOTICE(__METHOD__ . '::' . __LINE__ . ' Controller ' . get_class($controller) . ' has no get method');
                return $value;
            }
        } catch (Exception $e) {
            $recordArray = $value;
        }
        
        return $recordArray;
    }
}
