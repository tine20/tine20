<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * 
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
class Tinebase_Backend_Sql_Adapter_Pdo_Mysql extends Zend_Db_Adapter_Pdo_Mysql
{
    /**
     * Creates a PDO object and connects to the database.
     *
     * @return void
     * @throws Zend_Db_Adapter_Exception
     */
    protected function _connect()
    {
        if ($this->_connection) {
            return;
        }
        
        parent::_connect();
        
        if (isset($this->_config['options']['init_commands']) && is_array($this->_config['options']['init_commands'])) {
            foreach ($this->_config['options']['init_commands'] as $sqlInitCommand) {
                $this->_connection->exec($sqlInitCommand);
            }
        }
    }

    /**
     * @param $dbConfigArray
     * @return string charset
     */
    public static function getCharsetFromConfigOrCache(&$dbConfigArray)
    {
        $cacheId = md5(__METHOD__ . '::useUtf8mb4');
        if (!isset($dbConfigArray['useUtf8mb4'])) {
            if (false !== ($result = Tinebase_Core::getCache()->load($cacheId))) {
                $dbConfigArray['useUtf8mb4'] = $result;
            }
        }

        if (isset($dbConfigArray['useUtf8mb4']) && !$dbConfigArray['useUtf8mb4']) {
            $dbConfigArray['charset'] = 'utf8';
        } else {
            $dbConfigArray['charset'] = 'utf8mb4';
        }

        return $dbConfigArray['charset'];
    }

    /**
     * auto detect charset to be used and puts useUtf8mb4 into cache
     *
     * @param $db
     * @return bool
     */
    public static function supportsUTF8MB4($db)
    {
        $cacheId = md5(__METHOD__ . '::useUtf8mb4');

        // empty db => utf8mb4
        if (false !== $db->query('SHOW TABLES LIKE "' . SQL_TABLE_PREFIX . 'access_log"')->fetchColumn(0) &&
            strpos($db->query('show create table ' . SQL_TABLE_PREFIX . 'access_log')->fetchColumn(1),
                'utf8mb4') === false &&
            false !== $db->query("SHOW VARIABLES LIKE 'innodb_large_prefix'")->fetchColumn(0) &&
            $db->query("SHOW VARIABLES LIKE 'innodb_large_prefix'")->fetchColumn(1) !== 'ON'
        ) {
            Tinebase_Core::getCache()->save(0, $cacheId);
            return false;
        } else {
            Tinebase_Core::getCache()->save(1, $cacheId);
            return true;
        }
    }
}
