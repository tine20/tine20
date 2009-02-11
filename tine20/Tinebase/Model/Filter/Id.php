<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
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
    );
    
    /**
     * @var array maps abstract operators to sql operators
     */
    protected $_opSqlMap = array(
        'equals'     => array('sqlop' => ' = ?'   ),
        'in'         => array('sqlop' => ' IN (?)'),
    );
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select $_select
     */
     public function appendFilterSql($_select)
     {
         $action = $this->_opSqlMap[$this->_operator];
         
         // quote field identifier
         // ZF 1.7+ $field = $_select->getAdapter()->quoteIdentifier($this->field);
         $field = $db = Tinebase_Core::getDb()->quoteIdentifier($this->_field);
         
         //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($this->_value, TRUE));
         
         if (/*$this->_operator == 'in' && */empty($this->_value)) {
             // prevent sql error
             $_select->where('1=0');
         } else {
             // finally append query to select object
             $_select->where($this->_field . $action['sqlop'], $this->_value);
         }
     }
}