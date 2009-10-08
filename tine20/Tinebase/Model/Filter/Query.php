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
 * Tinebase_Model_Filter_Query
 * 
 * @package     Tinebase
 * @subpackage  Filter
 * 
 * filters for all of the given filterstrings if it is contained in at least 
 * one of the defined fileds
 * 
 * The fields to query in _must_ be defined in the options key 'fields'
 * The value string is space exploded into multibple filterstrings
 */
class Tinebase_Model_Filter_Query extends Tinebase_Model_Filter_Abstract
{
    /**
     * @var array list of allowed operators
     */
    protected $_operators = array(
        0 => 'contains',
    );
    
    /**
     * set options 
     *
     * @param  array $_options
     * @throws Tinebase_Exception_Record_NotDefined
     */
    protected function _setOptions(array $_options)
    {
        if (empty($_options['fields'])) {
            throw new Tinebase_Exception_Record_NotDefined('Fields must be defined in the options of a query filter');
        }
        
        $this->_options = $_options;
    }
    
    /**
     * appends sql to given select statement
     *
     * @param Zend_Db_Select                $_select
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    public function appendFilterSql($_select, $_backend)
    {
         if ( empty($this->_value)) {
             $_select->where('1=1/* empty query */');
             return;
         }
        
         $db = Tinebase_Core::getDb();
         
         $queries = explode(' ', $this->_value);
         foreach ($queries as $query) {
             $whereParts = array();
             foreach ($this->_options['fields'] as $qField) {
                 $whereParts[] = $db->quoteIdentifier($_backend->getTableName() . '.' . $qField) . ' LIKE ?';
             }                        
             $whereClause = '';
             if (!empty($whereParts)) {
                 $whereClause = implode(' OR ', $whereParts);
             }                        
             if (!empty($whereClause)) {
                 $_select->where($db->quoteInto($whereClause, '%' . trim($query) . '%'));
             }
         }
     }
     
}