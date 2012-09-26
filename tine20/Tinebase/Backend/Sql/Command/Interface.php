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
 * encapsulates SQL commands that are different for each dialect
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
interface Tinebase_Backend_Sql_Command_Interface
{
    /**
     *
     * @param Zend_Db_Adapter_Abstract $adapter
     * @param string $field
     * @return string
     */
     public static function getAggregateFunction($adapter,$field);

     /**
      *
      * @param Zend_Db_Adapter_Abstract $adapter
      * @param string $field
      * @param mixed $returnIfTrue
      * @param mixed $returnIfFalse
      * @return string
      */
    public static function getIfIsNull($adapter,$field,$returnIfTrue,$returnIfFalse);

    /**
     *
     * @param Zend_Db_Adapter_Abstract $adapter
     * @param date $date
     */
    public static function setDate($adapter, $date);
    /**
     *
     * @param Zend_Db_Adapter_Abstract $adapter
     * @param date $date
     */
    public static function setDateValue($adapter, $date);

    /**
     * returns the false value according to backend
     * @return mixed
     */
    public static function getFalseValue($adapter = null);

    /**
     * returns the true value according to backend
     * @return mixed
     */
    public static function getTrueValue($adapter = null);

    /**
     * @param Zend_Db_Adapter_Abstract $adapter
     * @param array $field
     */
    public static function setDatabaseJokerCharacters($adapter);

}
