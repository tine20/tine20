<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Text
 * 
 * filters one filterstring in one property
 * 
 * @package     Tinebase
 * @subpackage  Filter
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

        // filter replace space and - with %
        10 => 'equalsspecial'
    );
    
    /**
     * @var array maps abstract operators to sql operators
     * filled in constructor
     */
    protected $_opSqlMap = array();

    /**
     * get a new single filter action
     *
     * @param string|array $_fieldOrData
     * @param string $_operator
     * @param mixed  $_value
     * @param array  $_options
     *
     * @todo remove legacy code + obsolete params sometimes
     */
    public function __construct($_fieldOrData, $_operator = NULL, $_value = NULL, array $_options = array())
    {
        $this->_setOpSqlMap();
        parent::__construct($_fieldOrData, $_operator, $_value, $_options);
    }
    
    /**
     * set operator sql map (need to do this here because of Tinebase_Backend_Sql_Commands)
     */
    protected function _setOpSqlMap()
    {
        $db = Tinebase_Core::getDb();
        $this->_opSqlMap = array(
            'equals'            => array('sqlop' => ' LIKE ' . Tinebase_Backend_Sql_Command::factory($db)->prepareForILike('(?)'),          'wildcards' => '?'  ),
            'contains'          => array('sqlop' => ' LIKE ' . Tinebase_Backend_Sql_Command::factory($db)->prepareForILike('(?)'),          'wildcards' => '%?%'),
            'startswith'        => array('sqlop' => ' LIKE ' . Tinebase_Backend_Sql_Command::factory($db)->prepareForILike('(?)'),          'wildcards' => '?%' ),
            'endswith'          => array('sqlop' => ' LIKE ' . Tinebase_Backend_Sql_Command::factory($db)->prepareForILike('(?)'),          'wildcards' => '%?' ),
            'not'               => array('sqlop' => ' NOT LIKE ' . Tinebase_Backend_Sql_Command::factory($db)->prepareForILike('(?)'),      'wildcards' => '?'  ),
            'in'                => array('sqlop' => ' IN (?)',          'wildcards' => '?'  ),
            'notin'             => array('sqlop' => ' NOT IN (?)',      'wildcards' => '?'  ),
            'isnull'            => array('sqlop' => ' IS NULL',         'wildcards' => '?'  ),
            'notnull'           => array('sqlop' => ' IS NOT NULL',     'wildcards' => '?'  ),
            'group'             => array('sqlop' => " NOT LIKE  ''",    'wildcards' => '?'  ),
            'equalsspecial'     => array('sqlop' => ' LIKE ' . Tinebase_Backend_Sql_Command::factory($db)->prepareForILike('(?)'),          'wildcards' => '?'     ),
        );
    }

    /**
     * appends sql to given select statement
     *
     * @param  Zend_Db_Select                $_select
     * @param  Tinebase_Backend_Sql_Abstract $_backend
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function appendFilterSql($_select, $_backend)
    {
        // quote field identifier, set action and replace wildcards
        $field = $this->_getQuotedFieldName($_backend);
        
        if (! (isset($this->_opSqlMap[$this->_operator]) || array_key_exists($this->_operator, $this->_opSqlMap))) {
            throw new Tinebase_Exception_InvalidArgument('Operator "' . $this->_operator . '" not defined in sql map of ' . get_class($this));
        }
        $action = $this->_opSqlMap[$this->_operator];
        
        // don't remove wildcards for certain operators
        // TODO add an option for this?
        $value = (! in_array($this->_operator, array('in', 'notin'))) ? $this->_replaceWildcards($this->_value) : $this->_value;
        
        // check if group by is operator and return if this is the case
        if ($this->_operator == 'group') {
            $_select->group($this->_field);
        }
        
        if (in_array($this->_operator, array('in', 'notin')) && ! is_array($value)) {
            $value = explode(' ', $value);
        }
        
        // this is a text filter, so all items in the filter must be of type text (needed in pgsql)
        if (in_array($this->_operator, array('in', 'notin')) && is_array($value)) {
            foreach($value as &$item) {
                $item = (string) $item;
            }
        }
        
        $db = Tinebase_Core::getDb();
                
        if (is_array($value) && empty($value)) {
             $_select->where('1=' . (substr($this->_operator, 0, 3) == 'not' ? '1/* empty query */' : '0/* impossible query */'));
             return;
        }
        
        if ($this->_operator == 'equalsspecial') {
            if (is_array($value)) {
                foreach($value as $key => $v){
                    $value[$key] = preg_replace('/(\s+|\-)/', '%', $v);
                }
            }
            else {
                $value = preg_replace('/(\s+|\-)/', '%', $value);
            }  
        }
        
        if (! in_array($this->_operator, array('in', 'notin'))) {
            $where = Tinebase_Core::getDb()->quoteInto(Tinebase_Backend_Sql_Command::factory($db)->prepareForILike($field) . ' ' . $action['sqlop'], $value);
        } else {
            $where = Tinebase_Core::getDb()->quoteInto($field . $action['sqlop'], $value);
        }

        if (in_array($this->_operator, array('not', 'notin')) && $value !== '') {
            $where = "( $where OR $field IS NULL)";
        }

        if (in_array($this->_operator, array('equals', 'equalsspecial', 'contains', 'startswith', 'endswith', 'in')) && $value === '') {
            $where = "( $where OR $field IS NULL)";
        }

        // finally append query to select object
        $_select->where($where);
    }
}
