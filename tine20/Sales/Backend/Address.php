<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * backend for contracts
 *
 * @package     Sales
 * @subpackage  Backend
 */
class Sales_Backend_Address extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'sales_addresses';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Sales_Model_Address';

    /**
     * default column(s) for count
     *
     * @var string
     */
    protected $_defaultCountCol = 'id';
    
    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = FALSE;
    
    /**
     * returns the max address count for the specified type, the customers identified by $parentIds have
     * 
     * @param array $parentIds
     * @param string $type
     */
    public function getMaxAddressesByType($parentIds, $type = 'postal')
    {
        $sql = 'SELECT MAX(' . $this->_db->quoteIdentifier("count") . ') 
            FROM 
                (SELECT '. $this->_db->quoteIdentifier("customer_id") . ', 
                    COUNT(' . $this->_db->quoteIdentifier("id") .') 
                        AS ' . $this->_db->quoteIdentifier("count") . 
                  ' FROM ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "sales_addresses") . 
                  ' WHERE ' . $this->_db->quoteIdentifier("customer_id") . $this->_db->quoteInto(' IN (?)', $parentIds, 'array') . 
                      ' AND ' . $this->_db->quoteIdentifier("type") . $this->_db->quoteInto(' = ?', $type) . 
                  ' GROUP BY ' . $this->_db->quoteIdentifier("customer_id") .
                ')
            AS ' . $this->_db->quoteIdentifier("count");
        
        $result = $this->_db->query($sql);
        $result->setFetchMode(Zend_Db::FETCH_ASSOC);
        
        return $result->fetchColumn();
    }
}
