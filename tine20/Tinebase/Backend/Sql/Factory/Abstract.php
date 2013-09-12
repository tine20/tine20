<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Abstract factory for customized SQL statements
 *
 * @package     Tinebase
 * @subpackage  Backend
 */

class Tinebase_Backend_Sql_Factory_Abstract 
{
    protected static $_instances = array();
     
    /**
     * @param Zend_Db_Adapter_Abstract $adapter
     * @return mixed
    */
    public static function factory(Zend_Db_Adapter_Abstract $adapter)
    {
        $className = get_called_class() . '_' . self::_getClassName($adapter);
         
        // @todo find better array key (add loginname and host)
        if (!isset(self::$_instances[$className])) {
            self::$_instances[$className] = new $className($adapter);
        }
         
        return self::$_instances[$className];
    }
     
    /**
     *
     * @return Zend_Db_Adapter_Abstract
     */
    public static function getDb()
    {
        return self::$_db;
    }
     
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
}
