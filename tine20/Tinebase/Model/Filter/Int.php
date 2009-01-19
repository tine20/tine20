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
 * Tinebase_Model_Filter_Int
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * filters one int in one property
 */
class Tinebase_Model_Filter_Text extends Tinebase_Model_Filter_Abstract
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
        
    );
    
    /**
     * @var array maps abstract operators to sql operators
     */
    protected $_opSqlMap = array(
        'equals'     => 'LIKE ?',
        'startswith' => 'LIKE ?%',
        'endswith'   => 'LIKE %?',
        'greater'    => ' > ?',
        'less'       => ' < ?',
        'not'        => ' NOT LIKE ?',
    );
    
    /**
     * appeds sql to given select statement
     *
     * @param Zend_Db_Select $_select
     */
     public function appendSql($_select)
     {
         $value = str_replace(array('*', '_'), array('%', '\_'), $this->_value);
         $_select->where($this->field . $this->_opSqlMap[$this->_operator], $value);
     }
}