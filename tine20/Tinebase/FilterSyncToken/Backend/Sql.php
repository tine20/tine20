<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  FilterSyncToken
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * FilterSyncToken sql backend class
 *
 * @package     Tinebase
 * @subpackage  FilterSyncToken
 */

class Tinebase_FilterSyncToken_Backend_Sql extends Tinebase_Backend_Sql_Abstract
{
    /**
     * the constructor
     *
     * allowed options:
     *  - modelName
     *  - tableName
     *  - tablePrefix
     *  - modlogActive
     *
     * @param Zend_Db_Adapter_Abstract $_dbAdapter (optional)
     * @param array $_options (optional)
     * @throws Tinebase_Exception_Backend_Database
     */
    public function __construct($_dbAdapter = NULL, $_options = array())
    {
        $_options['modelName'] = Tinebase_Model_FilterSyncToken::class;
        $_options['tableName'] = 'filter_sync_token';
        $_options['modlogActive'] = false;

        parent::__construct($_dbAdapter, $_options);
    }

    /**
     * @param $_filterSyncToken
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function hasFilterSyncToken($_filterSyncToken)
    {
        $result = $this->_db->query('select count(*) from ' .
            $this->_db->quoteIdentifier($this->_tablePrefix . $this->_tableName)
            . $this->_db->quoteInto(' where ' . $this->_db->quoteIdentifier('filterSyncToken') . ' = ?',
                $_filterSyncToken))
            ->fetchColumn();

        return (bool)$result;
    }

    /**
     * @param integer $days
     * @return int
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function deleteByAge($days)
    {
        $days = intval($days);
        if ($days < 1) {
            throw new Tinebase_Exception_InvalidArgument('days needs to be a positive integer');
        }
        $date = Tinebase_DateTime::now()->subDay($days);

        return $this->_db->delete($this->_tablePrefix . $this->_tableName, $this->_db->quoteIdentifier('created') .
            $this->_db->quoteInto(' < ?', $date->format('Y-m-d H:i:s')));
    }

    /**
     * @param $max
     * @return int
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Zend_Db_Statement_Exception
     */
    public function deleteByFilterMax($max)
    {
        $max = intval($max);
        if ($max < 1) {
            throw new Tinebase_Exception_InvalidArgument('max needs to be a positive integer');
        }

        $data = $this->_db->select()->from($this->_tablePrefix . $this->_tableName, ['c' => 'count(*)', 'filterHash'])
            ->group('filterHash')
            ->having('c > ' . $max)
            ->query()->fetchAll();

        $deleted = 0;
        foreach($data as $row) {
            $s = $this->_db->query('DELETE FROM ' . $this->_db->quoteIdentifier($this->_tablePrefix . $this->_tableName)
                . $this->_db->quoteInto(' WHERE ' . $this->_db->quoteIdentifier('filterHash') . ' = ?',
                    $row['filterHash']) . ' ORDER BY created ASC LIMIT ' . ($row['c'] - $max));
            $deleted += $s->rowCount();
        }

        return $deleted;
    }

    /**
     * @param $max
     * @return int
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Zend_Db_Statement_Exception
     */
    public function deleteByMaxTotal($max)
    {
        $max = intval($max);
        if ($max < 1) {
            throw new Tinebase_Exception_InvalidArgument('max needs to be a positive integer');
        }

        $n = $this->_db->select()->from($this->_tablePrefix . $this->_tableName, ['count(*)'])->query()->fetchColumn();
        if ($n <= $max) {
            return 0;
        }

        $s = $this->_db->query('DELETE FROM ' . $this->_db->quoteIdentifier($this->_tablePrefix . $this->_tableName)
            . ' ORDER BY created ASC LIMIT ' . ($n - $max));

        return $s->rowCount();
    }
}