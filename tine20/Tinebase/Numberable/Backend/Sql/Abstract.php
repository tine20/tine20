<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Numberable
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */


/**
 * abstact backend for numberables
 *
 * @package     Tinebase
 * @subpackage  Numberable
 */
class Tinebase_Numberable_Backend_Sql_Abstract extends Tinebase_Backend_Sql_Abstract
{
    // parent constructor expects _modelName to be set
    protected $_modelName = 'shooo';


    protected $_numberableColumn = NULL;
    protected $_stepSize = 1;
    protected $_bucketColumn = NULL;
    protected $_bucketKey = NULL;
    protected $_start = 1;

    /**
     * the constructor
     *
     * allowed numberableConfiguration:
     *  - tablename (req)
     *  - numberablecolumn (req)
     *  - stepsize (optional)
     *  - bucketcolumn (optional)
     *  - bucketkey (optional)
     *
     *
     * allowed options:
     * see parent class
     *
     * @param array $_numberableConfiguration
     * @param Zend_Db_Adapter_Abstract $_dbAdapter (optional)
     * @param array $_options (optional)
     * @throws Tinebase_Exception_Backend_Database
     */
    public function __construct($_numberableConfiguration, $_dbAdapter = NULL, $_options = array())
    {
        if (!isset($_numberableConfiguration[Tinebase_Numberable_Abstract::TABLENAME])) {
            throw new Tinebase_Exception_UnexpectedValue(__CLASS__ . ' is missing "' . Tinebase_Numberable_Abstract::TABLENAME . '" configuration.');
        }
        $this->_tableName = $_numberableConfiguration[Tinebase_Numberable_Abstract::TABLENAME];

        if (!isset($_numberableConfiguration[Tinebase_Numberable_Abstract::NUMCOLUMN])) {
            throw new Tinebase_Exception_UnexpectedValue(__CLASS__ . ' is missing "' . Tinebase_Numberable_Abstract::NUMCOLUMN . '" configuration.');
        }
        $this->_numberableColumn = $_numberableConfiguration[Tinebase_Numberable_Abstract::NUMCOLUMN];


        parent::__construct($_dbAdapter, $_options);

        if (isset($_numberableConfiguration[Tinebase_Numberable_Abstract::STEPSIZE])) {
            if (!is_int($_numberableConfiguration[Tinebase_Numberable_Abstract::STEPSIZE])) {
                throw new Tinebase_Exception_UnexpectedValue(__CLASS__ . ' found improper "' . Tinebase_Numberable_Abstract::STEPSIZE . '" configuration: (not a int) "' . $_numberableConfiguration[Tinebase_Numberable_Abstract::STEPSIZE] . '"');
            }
            $this->_stepSize = $_numberableConfiguration[Tinebase_Numberable_Abstract::STEPSIZE];
        }

        if (isset($_numberableConfiguration[Tinebase_Numberable_Abstract::BUCKETCOLUMN])) {
            $this->_bucketColumn = $_numberableConfiguration[Tinebase_Numberable_Abstract::BUCKETCOLUMN];
        }

        if (isset($_numberableConfiguration[Tinebase_Numberable_Abstract::BUCKETKEY])) {
            $this->_bucketKey = $_numberableConfiguration[Tinebase_Numberable_Abstract::BUCKETKEY];
        }

        if (isset($_numberableConfiguration[Tinebase_Numberable_Abstract::START])) {
            if (!is_int($_numberableConfiguration[Tinebase_Numberable_Abstract::START])) {
                throw new Tinebase_Exception_UnexpectedValue(__CLASS__ . ' found improper "' . Tinebase_Numberable_Abstract::START . '" configuration: (not a int) "' . $_numberableConfiguration[Tinebase_Numberable_Abstract::START] . '"');
            }
            $this->_start = $_numberableConfiguration[Tinebase_Numberable_Abstract::START];
        } else {
            $this->_start = $this->_stepSize;
        }
    }

    /**
     * returns the next number for the current numberable
     *
     * @return string|int
     * @throws Tinebase_Exception_Backend_Database
     */
    public function getNext()
    {
        $stmt = $this->_getNext();

        // in case there is no data yet
        if ( $stmt->rowCount() != 1 ) {
            try {
                $rowCount = $this->_db->insert($this->_tablePrefix . $this->_tableName, array($this->_bucketColumn => $this->_bucketKey, $this->_numberableColumn => $this->_start));
            } catch (Zend_Db_Statement_Exception $zbse) {
                $rowCount = 0;
            }

            // in case of concurrency conflict
            if ( $rowCount !== 1 ) {

                $stmt = $this->_getNext();
                if ( $stmt->rowCount() != 1 ) {
                    throw new Tinebase_Exception_Backend_Database('could not get a new numberable');
                }
            }
        }

        if ($this->_db instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            $newId = $this->_db->query('SELECT currval(pg_get_serial_sequence(\'' . $this->_db->quoteIdentifier($this->_tablePrefix . $this->_tableName) . '\', \'id\'))')->fetchColumn();
        } else {
            $newId = $this->_db->lastInsertId();
        }
        $stmt = $this->_db->select()->from($this->_tablePrefix . $this->_tableName, array($this->_numberableColumn))
            ->where('id = '.$newId)->query();
        $result = $stmt->fetchAll(Zend_Db::FETCH_NUM);

        return $result[0][0];
    }

    /**
     * tries to insert the next value and returns the result as Zend_Db_Stmt
     *
     * @return Zend_Db_Statement_Interface
     */
    protected function _getNext()
    {
        $tableName = $this->_db->quoteIdentifier($this->_tablePrefix . $this->_tableName);
        $bucketColumn = $this->_db->quoteIdentifier($this->_bucketColumn);
        $numberableColumn = $this->_db->quoteIdentifier($this->_numberableColumn);

        return $this->_db->query('INSERT INTO ' . $tableName . ' (' . $bucketColumn . ', ' . $numberableColumn . ') SELECT ' . $bucketColumn . ', '
            . $numberableColumn .' + ' . $this->_stepSize . ' FROM ' . $tableName . ' WHERE ' . $bucketColumn
            . ($this->_bucketKey===null?' IS NULL':' = ' . $this->_db->quote($this->_bucketKey))
            . ' ORDER BY ' . $bucketColumn . ' DESC, ' . $numberableColumn . ' DESC LIMIT 1');
    }

    /**
     * tries to insert give value, returns true on success, false otherwise
     *
     * @param int $value
     * @return bool
     */
    public function insert($value)
    {
        $rowCount = $this->_db->insert($this->_tablePrefix . $this->_tableName, array($this->_bucketColumn => $this->_bucketKey, $this->_numberableColumn => $value));
        if ( $rowCount !== 1 ) {
            return false;
        }
        return true;
    }

    /**
     * tries to delete give value, returns true on success, false otherwise
     *
     * @param int $value
     * @return bool
     */
    public function free($value)
    {
        $rowCount = $this->_db->delete($this->_tablePrefix . $this->_tableName,
            $this->_db->quoteIdentifier($this->_bucketColumn) . ' = ' . $this->_db->quote($this->_bucketKey) . ' AND ' .
            $this->_db->quoteIdentifier($this->_numberableColumn) . ' = ' .  $this->_db->quote($value, Zend_Db::INT_TYPE));
        if ( $rowCount !== 1 ) {
            return false;
        }
        return true;
    }
}