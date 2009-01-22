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
    );
    
    /**
     * @var array maps abstract operators to sql operators
     */
    protected $_opSqlMap = array(
        'equals'     => array('sqlop' => ' LIKE ?',     'wildcards' => '?'  ),
        'contains'   => array('sqlop' => ' LIKE ?',     'wildcards' => '%?%'),
        'startswith' => array('sqlop' => ' LIKE ?',     'wildcards' => '%?' ),
        'endswith'   => array('sqlop' => ' LIKE ?',     'wildcards' => '?%' ),
        'not'        => array('sqlop' => ' NOT LIKE ?', 'wildcards' => '?'  ),
        'in'         => array('sqlop' => ' IN (?)',     'wildcards' => '?'  ),
    );
    
    /**
     * appeds sql to given select statement
     *
     * @param Zend_Db_Select $_select
     */
     public function appendFilterSql($_select)
     {
         $action = $this->_opSqlMap[$this->_operator];
         
         // quote field identifier
         // ZF 1.7+ $field = $_select->getAdapter()->quoteIdentifier($this->field);
         $field = $db = Tinebase_Core::getDb()->quoteIdentifier($this->_field);
         
         // replace wildcards from user
         $value = str_replace(array('*', '_'), array('%', '\_'), $this->_value);
         
         // add wildcard to value according to operator
         $value = str_replace('?', $value, $action['wildcards']);
         
         // finally append query to select object
         $_select->where($field . $action['sqlop'], $value);
         
         if ($this->_operator == 'not') {
             $_select->orWhere($field . ' IS NULL');
         }
     }
}