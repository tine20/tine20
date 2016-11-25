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
 * abstact for numberables
 *
 * @package     Tinebase
 * @subpackage  Numberable
 */
class Tinebase_Numberable_Abstract
{
    const CONF_TABLENAME        = 'tablename';
    const CONF_NUMCOLUMN        = 'numberablecolumn';
    const CONF_STEPSIZE         = 'stepsize';
    const CONF_BUCKETCOLUMN     = 'bucketcolumn';
    const CONF_BUCKETKEY        = 'bucketkey';

    protected $_numberableColumn = NULL;
    protected $_stepSize = 1;
    protected $_bucketColumn = NULL;
    protected $_bucketKey = NULL;

    protected $_backend = NULL;

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
        $this->_backend = new Tinebase_Numberable_Backend_Sql_Abstract($_numberableConfiguration, $_dbAdapter, $_options);
    }

    /**
     * returns the next numberable
     *
     * @return int|string
     */
    public function getNext()
    {
        return $this->_backend->getNext();
    }

    /**
     * inserts a new numberable
     *
     * @param int $value
     * @return bool
     */
    public function insert($value)
    {
        if (!is_int($value)) {
            $tmp = intval($value);
            if (((string)$tmp) !== ((string)$value)) {
                throw new Tinebase_Exception_UnexpectedValue('value needs to be of type int');
            }
            $value = $tmp;
        }
        return $this->_backend->insert($value);
    }

    /**
     * frees a numberable
     *
     * @param int $value
     * @return bool
     */
    public function free($value)
    {
        if (!is_int($value)) {
            $tmp = intval($value);
            if (((string)$tmp) !== ((string)$value)) {
                throw new Tinebase_Exception_UnexpectedValue('value needs to be of type int');
            }
            $value = $tmp;
        }
        return $this->_backend->free($value);
    }
}