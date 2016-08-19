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
    // ?!?
    protected $_modelName = 'shooo';


    protected $_numberableColumn = NULL;
    protected $_stepSize = 1;
    protected $_bucketColumn = NULL;
    protected $_bucketKey = NULL;

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
     * @param Zend_Db_Adapter_Abstract $_db (optional)
     * @param array $_options (optional)
     * @throws Tinebase_Exception_Backend_Database
     */
    public function __construct($_numberableConfiguration, $_dbAdapter = NULL, $_options = array())
    {
        if (!isset($_numberableConfiguration[Tinebase_Numberable_Abstract::CONF_TABLENAME])) {
            throw new Tinebase_Exception_UnexpectedValue(__CLASS__ . ' is missing "' . Tinebase_Numberable_Abstract::CONF_TABLENAME . '" configuration.');
        }
        $this->_tableName = $_numberableConfiguration[Tinebase_Numberable_Abstract::CONF_TABLENAME];

        if (!isset($_numberableConfiguration[Tinebase_Numberable_Abstract::CONF_NUMCOLUMN])) {
            throw new Tinebase_Exception_UnexpectedValue(__CLASS__ . ' is missing "' . Tinebase_Numberable_Abstract::CONF_NUMCOLUMN . '" configuration.');
        }
        $this->_numberableColumn = $_numberableConfiguration[Tinebase_Numberable_Abstract::CONF_NUMCOLUMN];


        parent::__construct($_dbAdapter, $_options);

        if (isset($_numberableConfiguration[Tinebase_Numberable_Abstract::CONF_STEPSIZE])) {
            if (!is_int($_numberableConfiguration[Tinebase_Numberable_Abstract::CONF_STEPSIZE])) {
                throw new Tinebase_Exception_UnexpectedValue(__CLASS__ . ' found improper "' . Tinebase_Numberable_Abstract::CONF_STEPSIZE . '" configuration: (not a int) "' . $_numberableConfiguration[Tinebase_Numberable_Abstract::CONF_STEPSIZE] . '"');
            }
            $this->_stepSize = intval($_numberableConfiguration[Tinebase_Numberable_Abstract::CONF_STEPSIZE]);
        }

        if (isset($_numberableConfiguration[Tinebase_Numberable_Abstract::CONF_BUCKETCOLUMN])) {
            $this->_bucketColumn = $_numberableConfiguration[Tinebase_Numberable_Abstract::CONF_BUCKETCOLUMN];
        }

        if (isset($_numberableConfiguration[Tinebase_Numberable_Abstract::CONF_BUCKETKEY])) {
            $this->_bucketKey = $_numberableConfiguration[Tinebase_Numberable_Abstract::CONF_BUCKETKEY];
        }
    }

    public function getNext()
    {
        $stmt = $this->_getNext();

        // in case there is no data yet
        if ( $stmt->rowCount() != 1 ) {
            try {
                $rowCount = $this->_db->insert($this->_tablePrefix . $this->_tableName, array($this->_bucketColumn => $this->_bucketKey, $this->_numberableColumn => 0 + $this->_stepSize));
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

        $newId = $this->_db->lastInsertId();
        $stmt = $this->_db->select()->from($this->_tablePrefix . $this->_tableName, array($this->_numberableColumn))
            ->where('id = '.$newId)->query();
        $result = $stmt->fetchAll(Zend_Db::FETCH_NUM);

        return $result[0][0];
    }

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
}