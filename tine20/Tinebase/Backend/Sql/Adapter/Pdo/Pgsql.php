<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2014-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * 
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
class Tinebase_Backend_Sql_Adapter_Pdo_Pgsql extends Zend_Db_Adapter_Pdo_Pgsql
{
    /**
     * 
     * @var boolean
     */
    protected $_hasUnaccentExtension = null;
    
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
     * get value of session variable "unaccent"
     * 
     * @param Zend_Db_Adapter_Abstract $db
     * @return boolean $valueUnaccent
     */
    public function hasUnaccentExtension()
    {
        if ($this->_hasUnaccentExtension !== null) {
            return $this->_hasUnaccentExtension;
        }
        
        $select = $this->select()
            ->from('pg_extension', 'COUNT(*)')
            ->where("extname = 'unaccent'");
        
        // if there is no table pg_extension, returns 0 (false)
        try {
            // checks if unaccent extension is installed or not
            // (1 - yes; unaccent found)
            $this->_hasUnaccentExtension = (bool) $this->fetchOne($select);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // (0 - no; unaccent not found)
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Unaccent extension disabled (' . $zdse->getMessage() . ')');
            $this->_hasUnaccentExtension = FALSE;
        }
        
        return $this->_hasUnaccentExtension;
    }
}
