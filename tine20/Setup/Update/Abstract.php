<?php
/**
 * Tine 2.0
 * 
 * @package     Setup
 * @subpackage  Update
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 */

/**
 * Common class for a Tine 2.0 Update
 * 
 * @package     Setup
 * @subpackage  Update
 */
class Setup_Update_Abstract
{
    /**
     * backend for databse handling and extended database queries
     *
     * @var Setup_Backend_Mysql
     */
    protected $_backend;
    
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    
    /** 
    * the constructor
    */
    public function __construct($_backend)
    {
        $this->_backend = $_backend;
        $this->_db = Tinebase_Core::getDb();
    }
    
    /**
     * get version number of a given application 
     * version is stored in database table "applications"
     *
     * @param string application
     * @return string version number major.minor release 
     */
    public function getApplicationVersion($_application)
    {
        $select = $this->_db->select()
                ->from(SQL_TABLE_PREFIX . 'applications')
                ->where($this->_db->quoteIdentifier('name') . ' = ?', $_application);

        $stmt = $select->query();
        $version = $stmt->fetchAll();
        
        return $version[0]['version'];
    }

    /**
     * set version number of a given application 
     * version is stored in database table "applications"
     *
     * @param string $_applicationName
     * @param string $_version new version number
     * @return Tinebase_Model_Application
     */    
    public function setApplicationVersion($_applicationName, $_version)
    {
        $application = Tinebase_Application::getInstance()->getApplicationByName($_applicationName);
        $application->version = $_version;
        
        return Tinebase_Application::getInstance()->updateApplication($application);
    }
    
    /**
     * get version number of a given table
     * version is stored in database table "applications_tables"
     *
     * @param Tinebase_Application application
     * @return int version number 
     */
    public function getTableVersion($_tableName)
    {
        $select = $this->_db->select()
                ->from(SQL_TABLE_PREFIX . 'application_tables')
                ->where(    $this->_db->quoteIdentifier('name') . ' = ?', $_tableName)
                ->orwhere(  $this->_db->quoteIdentifier('name') . ' = ?', SQL_TABLE_PREFIX . $_tableName);

        $stmt = $select->query();
        $rows = $stmt->fetchAll();
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        $result = (count($rows) > 0 && isset($rows[0]['version'])) ? $rows[0]['version'] : 0;
        
        return $result;
    }
    
    /**
     * set version number of a given table
     * version is stored in database table "applications_tables"
     *
     * @param string tableName
     * @param int|string $_version
     * @param boolean $_createIfNotExist
     * @param string $_application
     * @return void
     * @throws Setup_Exception_NotFound
     */     
    public function setTableVersion($_tableName, $_version, $_createIfNotExist = TRUE, $_application = 'Tinebase')
    {
        if ($this->getTableVersion($_tableName) == 0) {
            if ($_createIfNotExist) {
                Tinebase_Application::getInstance()->addApplicationTable(
                    Tinebase_Application::getInstance()->getApplicationByName($_application), 
                    $_tableName,
                    $_version
                );
            } else {
                throw new Setup_Exception_NotFound('Table ' . $_tableName . ' not found in application tables or previous version number invalid.');
            }
        } else {
            $applicationsTables = new Tinebase_Db_Table(array('name' =>  SQL_TABLE_PREFIX . 'application_tables'));
            $where  = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', $_tableName),
            );
            $result = $applicationsTables->update(array('version' => $_version), $where);
        }
    }
    
    /**
     * set version number of a given table
     * version is stored in database table "applications_tables"
     *
     * @param string tableName
     * @return int version number 
     */  
    public function increaseTableVersion($_tableName)
    {
        $currentVersion = $this->getTableVersion($_tableName);

        $version = ++$currentVersion;
        
        $applicationsTables = new Tinebase_Db_Table(array('name' =>  SQL_TABLE_PREFIX . 'application_tables'));
        $where  = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', $_tableName),
        );
        $result = $applicationsTables->update(array('version' => $version), $where);
    }
    
    /**
     * compares version numbers of given table and given number
     *
     * @param  string $_tableName
     * @param  int version number
     * @throws Setup_Exception
     */     
    public function validateTableVersion($_tableName, $_version)
    {
        $currentVersion = $this->getTableVersion($_tableName);
        if($_version != $currentVersion) {
            throw new Setup_Exception("Wrong table version for $_tableName. expected $_version got $currentVersion");
        }
    }
    
    /**
     * create new table and add it to application tables
     * 
     * @param string $_tableName
     * @param Setup_Backend_Schema_Table_Abstract $_table
     * @param string $_application
     */
    public function createTable($_tableName, Setup_Backend_Schema_Table_Abstract $_table, $_application = 'Tinebase', $_version = 1)
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName($_application);
        Tinebase_Application::getInstance()->removeApplicationTable($app, $_tableName);
        
        $this->_backend->createTable($_table);
        
        Tinebase_Application::getInstance()->addApplicationTable($app, $_tableName, $_version);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Created new table ' . $_tableName);
    }
    
    /**
     * rename table in applications table
     *
     * @param string $_oldTableName
     * @param string $_newTableName
     */  
    public function renameTable($_oldTableName, $_newTableName)
    {
        $this->_backend->renameTable($_oldTableName, $_newTableName);
        
        $applicationsTables = new Tinebase_Db_Table(array('name' =>  SQL_TABLE_PREFIX . 'application_tables'));
        $where  = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', $_oldTableName),
        );
        $result = $applicationsTables->update(array('name' => $_newTableName), $where);
    }
    
    /**
     * drop table
     *
     * @param string $_tableName
     * @param string $_application
     */  
    public function dropTable($_tableName, $_application = 'Tinebase')
    {
        Tinebase_Application::getInstance()->removeApplicationTable(Tinebase_Application::getInstance()->getApplicationByName($_application), $_tableName);
        $result = $this->_backend->dropTable($_tableName);
    }
    
    /**
     * prompts for a username to set as active user on performing updates. this must be an admin user.
     * the user account will be returned. this method can be called by cli only, so a exception will 
     * be thrown if not running on cli
     * 
     * @throws Tinebase_Exception
     * @return Tinebase_Model_FullUser
     */
    public function promptForUsername()
    {
        if (php_sapi_name() == 'cli') {
            
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Prompting for username on CLI');
            
            $userFound = NULL;
            
            do {
                try {
                    if ($userFound === FALSE) {
                        echo PHP_EOL;
                        echo 'The user "' . $user . '" could not be found!' . PHP_EOL . PHP_EOL;
                    }
                    
                    $user = Tinebase_Server_Cli::promptInput('Please enter an admin username to perform updates ');
                    $userAccount = Tinebase_User::getInstance()->getFullUserByLoginName($user);
                    
                    if (! $userAccount->hasRight('Tinebase', Tinebase_Acl_Rights::ADMIN)) {
                        $userFound = NULL;
                        echo PHP_EOL;
                        echo 'The user "' . $user . '" could be found, but this is not an admin user!' . PHP_EOL . PHP_EOL;
                    } else {
                        Tinebase_Core::set(Tinebase_Core::USER, $userAccount);
                        $userFound = TRUE;
                    }
                    
                } catch (Tinebase_Exception_NotFound $e) {
                    $userFound = FALSE;
                }
                
            } while (! $userFound);
            
        } else {
            throw new Setup_Exception_PromptUser();
        }
        
        return $userAccount;
    }

    /**
     * get db adapter
     * 
     * @return Zend_Db_Adapter_Abstract
     */
    public function getDb()
    {
        return $this->_db;
    }
    
    /**
     * Search for text fields that contain a string longer as a specific length and truncate it to this length
     * 
     * @param string $table
     * @param string $field
     * @param int $length
     */
    public function shortenTextValues($table, $field, $length)
    {
        $results = $this->_db->query(
            "SELECT " . $this->_db->quoteIdentifier($field) .
            ", LEFT(" . $this->_db->quoteIdentifier($field) . "," . $length . ") AS short" .
            " FROM " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $table) .
            " WHERE CHAR_LENGTH(" . $this->_db->quoteIdentifier($field) . ") > " . $length
            )->fetchAll();
        
        foreach ($results as $result) {
            $where  = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier($field) . ' = ?', $result[$field]),
            );
            
            $newContent = array($field => $result['short']);
            
            try {
                $this->_db->update(SQL_TABLE_PREFIX . $table, $newContent, $where);
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Field was shortend: ' . print_r($result, true));
            } catch (Tinebase_Exception_Record_Validation $terv) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Failed to shorten field: ' . print_r($result, true));
                Tinebase_Exception::log($terv);
            }
        }
    }
    
    /**
     * truncate text fields to a specific length
     * Array needs to contain the table name, field name, and a config option for "<notnull>" ("true", "false")
     * or use "null" to set default to NULL
     * 
     * @param array $columns
     * @param int $length
     */
    public function truncateTextColumn($columns, $length)
    {
        foreach ($columns as $table => $fields) {
            foreach ($fields as $field => $config) {
                try {
                    $this->shortenTextValues($table, $field, $length);
                    if (isset($config)) {
                        $config = ($config == 'null' ? '<default>NULL</default>': '<notnull>' . $config . '</notnull>');
                    }
                    $declaration = new Setup_Backend_Schema_Field_Xml('
                        <field>
                            <name>' . $field . '</name>
                            <type>text</type>
                            <length>' . $length . '</length>'
                            . $config .
                        '</field>
                    ');
                    
                    $this->_backend->alterCol($table, $declaration);
                } catch (Zend_Db_Statement_Exception $zdse) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                        . ' Could not truncate text column ' . $field . ' in table ' . $table);
                    Tinebase_Exception::log($zdse);
                }
            }
        }
    }

    protected function _addModlogFields($table)
    {
        $fields = array('<field>
                <name>created_by</name>
                <type>text</type>
                <length>40</length>
            </field>','
            <field>
                <name>creation_time</name>
                <type>datetime</type>
            </field> ','
            <field>
                <name>last_modified_by</name>
                <type>text</type>
                <length>40</length>
            </field>','
            <field>
                <name>last_modified_time</name>
                <type>datetime</type>
            </field>','
            <field>
                <name>is_deleted</name>
                <type>boolean</type>
                <default>false</default>
            </field>','
            <field>
                <name>deleted_by</name>
                <type>text</type>
                <length>40</length>
            </field>','
            <field>
                <name>deleted_time</name>
                <type>datetime</type>
            </field>','
            <field>
                <name>seq</name>
                <type>integer</type>
                <notnull>true</notnull>
                <default>0</default>
            </field>');
        
        foreach ($fields as $field) {
            $declaration = new Setup_Backend_Schema_Field_Xml($field);
            try {
                $this->_backend->addCol($table, $declaration);
            } catch (Zend_Db_Statement_Exception $zdse) {
                Tinebase_Exception::log($zdse);
            }
        }
    }
}
