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

    private static function _getClassName($adapter)
    {
        $completeClassName = explode('_',get_class($adapter));
        $className = $completeClassName[count($completeClassName)-1];
        $className = str_replace('Oci','Oracle',$className);
        return $className;
    }
    
    /**
     * 
     * @param $adapter Zend_Db_Adapter_Abstract
     * @param $on boolean
     */
    public static function setAutocommit($adapter, $on)
    {
        $className = self::_getClassName($adapter);
        $className = __CLASS__ . '_' . $className;
        $command = new $className();
        
        $command->setAutocommit($adapter,$on);
    }
}
