<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * 
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
class Tinebase_Backend_Sql_Adapter_Pdo_Oci extends Zend_Db_Adapter_Pdo_Oci
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
}
