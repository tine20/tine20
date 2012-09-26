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
class Tinebase_Backend_Sql_Command implements Tinebase_Backend_Sql_Command_Interface
{

    /**
     *
     * @param Zend_Db_Adapter_Abstract $adapter
     * @return string
     */
    private static function _getClassName($adapter)
    {
        $completeClassName = explode('_',get_class($adapter));
        $className = $completeClassName[count($completeClassName)-1];
        $className = str_replace('Oci','Oracle',$className);
        return $className;
    }

    /**
     *
     * @param Zend_Db_Adapter_Abstract $adapter
     * @return Tinebase_Backend_Sql_Command_Interface
     */
    private static function _getCommand($adapter)
    {
        $className = self::_getClassName($adapter);
        $className = __CLASS__ . '_' . $className;
        $command = new $className();
        return $command;
    }

    /**
     *
     * @param Tinebase_Container $container
     * @param Zend_Db_Adapter_Abstract $adapter
     * @return string
     */
    public static function getAggregateFunction($adapter,$field)
    {
        $command = self::_getCommand($adapter);
        return $command->getAggregateFunction($adapter,$field);
    }

    /**
     *
     * @param Zend_Db_Adapter_Abstract $adapter
     * @param string $field
     * @param mixed $returnIfTrue
     * @param mixed $returnIfFalse
     * @return string
     */
    public static function getIfIsNull($adapter,$field,$returnIfTrue,$returnIfFalse)
    {
        $command = self::_getCommand($adapter);
        return $command->getIfIsNull($adapter,$field,$returnIfTrue,$returnIfFalse);
    }

    /**
     *
     * @param Zend_Db_Adapter_Abstract $adapter
     * @param string $field
     * @param mixed $returnIfTrue
     * @param mixed $returnIfFalse
     * @return string
     */
    public static function setDate($adapter, $field)
    {
        $command = self::_getCommand($adapter);
        return $command->setDate($adapter, $field);
    }

    /**
     *
     * @param Zend_Db_Adapter_Abstract $adapter
     * @param string $field
     * @param mixed $returnIfTrue
     * @param mixed $returnIfFalse
     * @return string
     */
    public static function setDateValue($adapter, $field)
    {
        $command = self::_getCommand($adapter);
        return $command->setDateValue($adapter, $field);
    }

    /**
     *
     * @param Zend_Db_Adapter_Abstract $adapter
     * @return mixed
     */
    public static function getFalseValue($adapter = null)
    {
        $command = self::_getCommand($adapter);
        return $command->getFalseValue();
    }

    /**
     *
     * @param Zend_Db_Adapter_Abstract $adapter
     * @return mixed
     */
    public static function getTrueValue($adapter = null)
    {
        $command = self::_getCommand($adapter);
        return $command->getTrueValue();
    }

    /**
     *
     * @param Zend_Db_Adapter_Abstract $adapter
     */
    public static function setDatabaseJokerCharacters($adapter)
    {
        $command = self::_getCommand($adapter);
        return $command->setDatabaseJokerCharacters($adapter);
    }

}
