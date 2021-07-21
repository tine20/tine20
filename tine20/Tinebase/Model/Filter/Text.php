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
    const CASE_SENSITIVE = 'caseSensitive';

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
        10 => 'equalsspecial',
        11 => 'notcontains',
    );
    
    /**
     * @var array maps abstract operators to sql operators
     * filled in constructor
     */
    protected $_opSqlMap = array();

    protected $_caseSensitive = false;

    protected $_binary = false;

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
        $_options = isset($_fieldOrData['options']) ? $_fieldOrData['options'] : $_options;
        if (isset($_options[self::CASE_SENSITIVE]) && $_options[self::CASE_SENSITIVE]) {
            $this->_caseSensitive = true;
        }
        if (isset($_options['binary']) && $_options['binary']) {
            $this->_binary = true;
            if (!isset($_options[self::CASE_SENSITIVE])) {
                $this->_caseSensitive = true;
            }
        }
        $this->_setOpSqlMap();
        parent::__construct($_fieldOrData, $_operator, $_value, $_options);
    }
    
    /**
     * set operator sql map (need to do this here because of Tinebase_Backend_Sql_Commands)
     */
    protected function _setOpSqlMap()
    {
        if (! empty($this->_opSqlMap)) {
            return;
        }

        // TODO fixme move CS here
        // if ($this->_caseSensitive) {}
        // it seems as of mysql8 this might become an option
        // 'sqlop' => ' LIKE _utf8mb4 ? COLLATE utf8mb4_unicode_cs? or something'
        // https://dev.mysql.com/doc/refman/8.0/en/string-comparison-functions.html

        // AND mysql 5.6 / 5.7 has a bug with unicode_utf8mb4_ci .... https://bugs.mysql.com/bug.php?id=81990
        // so we add some magic 'escape "|"' to it, to be removed once mysql 5.7 can be dropped

        $this->_opSqlMap = array(
            'equals'            => array('sqlop' => ' LIKE (?) ESCAPE "|"',                         ),
            'contains'          => array('sqlop' => ' LIKE (?) ESCAPE "|"',     'wildcards' => '%?%'),
            'notcontains'       => array('sqlop' => ' NOT LIKE (?) ESCAPE "|"', 'wildcards' => '%?%'),
            'startswith'        => array('sqlop' => ' LIKE (?) ESCAPE "|"',     'wildcards' => '?%' ),
            'endswith'          => array('sqlop' => ' LIKE (?) ESCAPE "|"',     'wildcards' => '%?' ),
            'not'               => array('sqlop' => ' NOT LIKE (?) ESCAPE "|"',                     ),
            'in'                => array('sqlop' => ' IN (?)',                                      ),
            'notin'             => array('sqlop' => ' NOT IN (?)',                                  ),
            'isnull'            => array('sqlop' => ' IS NULL',                                     ),
            'notnull'           => array('sqlop' => ' IS NOT NULL',                                 ),
            'group'             => array('sqlop' => ' NOT LIKE \'\'',                               ),
            'equalsspecial'     => array('sqlop' => ' LIKE (?) ESCAPE "|"',                         ),
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

        // TODO fix me: this should be moved to the static part of the query, the search condition.
        // TODO fix me: that might improve performance quiet a bit, remove this, do it in _setOpSqlMap
        $db = Tinebase_Core::getDb();
        if (!$this->_binary && $this->_caseSensitive) {
            $field = 'BINARY ' . $field;
        } elseif ($this->_binary && !$this->_caseSensitive) {
            if ($db->getConfig()['charset'] === 'utf8') {
                $field .= ' COLLATE utf8_unicode_ci';
            } else {
                $field .= ' COLLATE utf8mb4_unicode_ci';
            }
        }
        
        if (! (isset($this->_opSqlMap[$this->_operator]) || array_key_exists($this->_operator, $this->_opSqlMap))) {
            throw new Tinebase_Exception_InvalidArgument('Operator "' . $this->_operator . '" not defined in sql map of ' . get_class($this));
        }
        $action = $this->_opSqlMap[$this->_operator];
        
        // don't remove wildcards for certain operators
        // TODO add an option for this?
        $value = (! in_array($this->_operator, array('in', 'notin'))) ? $this->_replaceWildcards($this->_value) : $this->_value;
        
        // check if group by is operator and return if this is the case
        if ($this->_operator == 'group') {
            $_select->group(isset($this->_options['field']) ? $this->_options['field'] : $this->_field);
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
            } else {
                $value = preg_replace('/(\s+|\-)/', '%', $value);
            }  
        }

        $where = Tinebase_Core::getDb()->quoteInto($field . $action['sqlop'], $value);
        
        if (in_array($this->_operator, array('not', 'notin', 'notcontains')) && $value !== '') {
            $where = "( $where OR $field IS NULL)";
        }

        if (in_array($this->_operator, array('equals', 'equalsspecial', 'contains', 'startswith', 'endswith', 'in')) && $value === '') {
            $where = "( $where OR $field IS NULL)";
        }

        // finally append query to select object
        $_select->where($where);
    }
}
