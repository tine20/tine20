<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Tinebase_Model_Filter_Text
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * filters one filterstring in one property
 */
class Tinebase_Model_Filter_Text extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
        1 => 'contains',
        2 => 'startswith',
        3 => 'endswith',
        4 => 'not',
        5 => 'in',
        6 => 'notin',
        7 => 'isnull',
        8 => 'notnull',
    // add 'group by _fieldname_' to select statement and remove empty values / filter value is not used when this operator is set
        9 => 'group',
    );
    
    /**
     * @var array maps abstract operators to sql operators
     */
    protected $_opSqlMap = array(
        'equals'     => array('sqlop' => ' LIKE ?',      'wildcards' => '?'  ),
        'contains'   => array('sqlop' => ' LIKE ?',      'wildcards' => '%?%'),
        'startswith' => array('sqlop' => ' LIKE ?',      'wildcards' => '?%' ),
        'endswith'   => array('sqlop' => ' LIKE ?',      'wildcards' => '%?' ),
        'not'        => array('sqlop' => ' NOT LIKE ?',  'wildcards' => '?'  ),
        'in'         => array('sqlop' => ' IN (?)',      'wildcards' => '?'  ),
        'notin'      => array('sqlop' => ' NOT IN (?)',  'wildcards' => '?'  ),
        'isnull'     => array('sqlop' => ' IS NULL',     'wildcards' => '?'  ),
        'notnull'    => array('sqlop' => ' IS NOT NULL', 'wildcards' => '?'  ),
        'group'      => array('sqlop' => " NOT LIKE  ''",'wildcards' => '?'  ),
    );
    
    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                $_select
     * @param  Tinebase_Backend_Sql_Abstract $_backend
     * @throws Tinebase_Exception_NotFound
     */
    public function appendFilterSql($_select, $_backend)
    {
        // quote field identifier, set action and replace wildcards
        $field = $this->_getQuotedFieldName($_backend);
        $action = $this->_opSqlMap[$this->_operator];
        $value = $this->_replaceWildcards($this->_value);

        // check if group by is operator and return if this is the case
        if ($this->_operator == 'group') {
            $_select->group($this->_field);
        }

        if (in_array($this->_operator, array('in', 'notin')) && ! is_array($value)) {
            $value = explode(' ', $value);
        }
            
        if (is_array($value) && empty($value)) {
             $_select->where('1=' . (substr($this->_operator, 0, 3) == 'not' ? '1/* empty query */' : '0/* impossible query */'));
             return;
        }
        
        $where = Tinebase_Core::getDb()->quoteInto($field . $action['sqlop'], $value);
        
        if ($this->_operator == 'not' || $this->_operator == 'notin') {
            $where = "( $where OR $field IS NULL)";
        }
         
        // finally append query to select object
        $_select->where($where);
    }
}
