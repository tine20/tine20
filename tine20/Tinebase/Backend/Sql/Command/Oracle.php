<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * encapsulates SQL commands of Oracle database
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
class Tinebase_Backend_Sql_Command_Oracle implements Tinebase_Backend_Sql_Command_Interface
{
    /**
     * @param Zend_Db_Adapter_Abstract $adapter
     * @param string $field
     * @return string
     * @todo replace by equivalent function of MySQL GROUP_CONCAT in Oracle
     */
    public static function getAggregateFunction($adapter, $field)
    {
        return "GROUP_CONCAT( DISTINCT $field)";
    }

    /**
     * @param Zend_Db_Adapter_Abstract $adapter
     * @param string $field
     * @param mixed $returnIfTrue
     * @param mixed $returnIfFalse
     */
    public static function getIfIsNull($adapter, $field, $returnIfTrue, $returnIfFalse)
    {
        return "(CASE WHEN $field IS NULL THEN " . (string) $returnIfTrue . " ELSE " . (string) $returnIfFalse . " END)";
    }

    /**
     * @param Zend_Db_Adapter_Abstract $adapter
     * @param string $date
     * @return string
     */
    public static function setDate($adapter, $date)
    {
        return "TO_DATE({$date}, 'YYYY-MM-DD hh24:mi:ss') ";
    }

    /**
     * @param Zend_Db_Adapter_Abstract $adapter
     * @param string $value
     * @return string
     */
    public static function setDateValue($adapter, $value)
    {
        return "TO_DATE('{$value}', 'YYYY-MM-DD hh24:mi:ss') ";
    }

    /**
     * @param Zend_Db_Adapter_Abstract $adapter
     * @return mixed
     */
    public static function getFalseValue($adapter = null)
    {
        return '0';
    }

    /**
     * @param Zend_Db_Adapter_Abstract $adapter
     * @return mixed
     */
    public static function getTrueValue($adapter = null)
    {
        return '1';
    }

    /**
     * @param Zend_Db_Adapter_Abstract $adapter
     * @return string
     */
    public static function setDatabaseJokerCharacters($adapter)
    {
        return array('%', '_');
    }
}
