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
     *
     * @param $adapter Zend_Db_Adapter_Abstract
     * @param $on boolean
     */
    public static function setAutocommit($adapter,$on)
    {
        // SET AUTOCOMMIT=0 is not supported for PostgreSQL
        if ($on) {
            $adapter->query('SET AUTOCOMMIT=1;');
        } else {
            $adapter->query('SET AUTOCOMMIT=0;');
        }
    }
}
