<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Id
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * filters one or more ids
 */
class Tinebase_Model_Filter_Id extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
        1 => 'in',
        2 => 'notin',
    );
    
    /**
     * @var array maps abstract operators to sql operators
     */
    protected $_opSqlMap = array(
        'equals'     => array('sqlop' => ' = ?'   ),
        'in'         => array('sqlop' => ' IN (?)'),
        'notin'      => array('sqlop' => ' NOT IN (?)'),
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
         
         // quote field identifier
         $field = $this->_getQuotedFieldName($_backend);
         
         //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_value, TRUE));
         
         if (empty($this->_value)) {
             // prevent sql error
             if ($this->_operator == 'in') {
                $_select->where('1=0');
             }
         } else {
             // finally append query to select object
             $_select->where($field . $action['sqlop'], $this->_value);
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
     * get controller
     * 
     * @return Tinebase_Controller_Record_Abstract|NULL
     */
    protected function _getController()
    {
        if ($this->_controller === NULL) {
            if (! isset($this->_options['modelName'])) {
                Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ . ' No modelName defined in filter options, can not resolve record.');
                return NULL;
            }
            $this->_controller = Tinebase_Core::getApplicationInstance($this->_options['modelName']);
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
            $recordArray = $controller->get($value)->toArray();
        } catch (Exception $e) {
            $recordArray = $value;
        }
        
        return $recordArray;
    }
}
