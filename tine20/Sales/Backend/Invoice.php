<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * backend for sales invoices
 *
 * @package     Sales
 * @subpackage  Backend
 */
class Sales_Backend_Invoice extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'sales_sales_invoices';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Sales_Model_Invoice';

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
    protected $_modlogActive = TRUE;


    public function getInvoicesWithChangedContract($contractId = NULL)
    {
        //SELECT tsi.id, tr.own_id FROM `tine20_sales_invoices` as tsi JOIN tine20_relations AS tr ON tsi.id = tr.related_id AND tr.own_model = 'Sales_Model_Contract'
        //JOIN tine20_sales_contracts as tsc ON tr.own_id = tsc.id AND tsc.last_modified_time is NOT NULL
        //wHERE tsi.creation_time < tsc.last_modified_time

        $select = $this->getAdapter()->select();
        $select->from(array($this->_tableName => $this->_tablePrefix . $this->_tableName), array($this->_tableName . '.' . 'id'));
        $select->join(
        /* table  */ array('tr' => 'tine20_relations'),
        /* on     */ $this->_db->quoteIdentifier($this->_tableName . '.' . 'id') . ' = ' . $this->_db->quoteIdentifier('tr.related_id') . ' AND ' . $this->_db->quoteIdentifier('tr.own_model') . ' = \'Sales_Model_Contract\'' . (null!==$contractId?' AND ' . $this->_db->quoteIdentifier('tr.own_id') . ' = ' . $this->_db->quote($contractId):''),
        /* select */ array('tr.own_id')
        );
        $select->join(
        /* table  */ array('tsc' => 'tine20_sales_contracts'),
        /* on     */ $this->_db->quoteIdentifier('tr.own_id') . ' = ' . $this->_db->quoteIdentifier('tsc.id') . ' AND tsc.last_modified_time is NOT NULL',
        /* select */ array()
        );
        $select->where($this->_tableName . '.creation_time < tsc.last_modified_time AND ' . $this->_tableName . '.cleared <> \'CLEARED\'');
        $select->order($this->_tableName . '.creation_time DESC');

        $result = array();
        $stmt = $this->_db->query($select);
        $stmt->setFetchMode(Zend_Db::FETCH_NUM);
        return $stmt->fetchAll();
    }
}
