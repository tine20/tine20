<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Tinebase_Model_Filter_Int
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * filters one int in one property
 */
class Tinebase_Model_Filter_Int extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
        1 => 'startswith',
        2 => 'endswith',
        3 => 'greater',
        4 => 'less',
        5 => 'not',
        6 => 'in',
        7 => 'notin',
    );
    
    /**
     * @var array maps abstract operators to sql operators
     */
    protected $_opSqlMap = array(
        'equals'     => array('sqlop' => ' = ?'   ,     'wildcards' => '?'  ),
        'startswith' => array('sqlop' => ' LIKE ?',     'wildcards' => '?%' ),
        'endswith'   => array('sqlop' => ' LIKE ?',     'wildcards' => '%?' ),
        'greater'    => array('sqlop' => ' > ?',        'wildcards' => '?'  ),
        'less'       => array('sqlop' => ' < ?',        'wildcards' => '?'  ),
        'not'        => array('sqlop' => ' NOT LIKE ?', 'wildcards' => '?'  ),
        'in'         => array('sqlop' => ' IN (?)',     'wildcards' => '?'  ),
        'notin'      => array('sqlop' => ' NOT IN (?)', 'wildcards' => '?'  ),
    );
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
        // quote field identifier, set action and replace wildcards
        $field = $this->_getQuotedFieldName($_backend);
        $action = $this->_opSqlMap[$this->_operator];
        $value = $this->_replaceWildcards($this->_value);
        
        if (in_array($this->_operator, array('in', 'notin')) && ! is_array($value)) {
            $value = explode(' ', $this->_value);
        }
        
        if (in_array($this->_operator, array('equals', 'greater', 'less', 'in', 'notin'))) {
            $value = str_replace(array('%', '\\_'), '', $value);
            
            if (is_array($value) && empty($value)) {
                $_select->where('1=' . (substr($this->_operator, 0, 3) == 'not' ? '1/* empty query */' : '0/* impossible query */'));
            } elseif ($this->_operator == 'equals' && ($value === '' || $value === NULL || $value === false)) {
                $_select->where($field . 'IS NULL');
            } else {
                // finally append query to select object
                $_select->where($field . $action['sqlop'], $value, Zend_Db::INT_TYPE);
            }
        } else {
            // finally append query to select object
            $_select->where($field . $action['sqlop'], $value);
        }
        
        if ($this->_operator == 'not' || $this->_operator == 'notin') {
            $_select->orWhere($field . ' IS NULL');
        }
    }
}
