<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Tinebase_Model_Filter_Bool
 * 
 * filters one boolean in one property
 * 
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_Bool extends Tinebase_Model_Filter_Abstract
{
    /**
     * use this as value to indicate that the boolfilter should not be applied at all
     * this can be handy if you need to set a filterline e.g. in UI without effect
     */
    const VALUE_NOTSET = '#NOTSET#';

    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'equals',
    );
    
    /**
     * @var array maps abstract operators to sql operators
     */
    protected $_opSqlMap = array(
        'equals'     => array('sqlop' => ' = ?'),
    );
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
     public function appendFilterSql($_select, $_backend)
     {
         if ($this->_value === self::VALUE_NOTSET) {
             return;
         }

         $action = $this->_opSqlMap[$this->_operator];
         
         $db = $_backend->getAdapter();
         
         // prepare value
         $value = $this->_value ? 1 : 0;
        
         if (! empty($this->_options['fields'])) {
             foreach ((array) $this->_options['fields'] as $fieldName) {
                 $quotedField = $db->quoteIdentifier(strpos($fieldName, '.') === false ? $_backend->getTableName() . '.' . $fieldName : $fieldName);
                 if ($value) {
                     $_select->where($quotedField . $action['sqlop'], $value);
                 } else {
                     $_select->orwhere($quotedField . $action['sqlop'], $value);
                 }
             }
         } else if (! empty($this->_options['leftOperand'])) {
             $_select->where($this->_options['leftOperand'] . $action['sqlop'], $value);
         } else {
             $_select->where($this->_getQuotedFieldName($_backend) . $action['sqlop'], $value);
         }
     }
}
