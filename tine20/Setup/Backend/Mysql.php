<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * setup backend class for MySQL 5.0 +
 *
 * @package     Setup
 * @subpackage  Backend
 */
class Setup_Backend_Mysql extends Setup_Backend_Abstract
{
    /**
     * Define how database agnostic data types get mapped to mysql data types
     * 
     * @var array
     */
    protected $_typeMappings = array(
        'integer' => array(
            'lengthTypes' => array(
                4 => 'tinyint',
                19 => 'int',
                64 => 'bigint'),
            'defaultType' => 'int',
            'defaultLength' => self::INTEGER_DEFAULT_LENGTH),
        'boolean' => array(
            'defaultType' => 'tinyint',
            'defaultLength' => 1),
        'text' => array(
            'lengthTypes' => array(
                255 => 'varchar',
                65535 => 'text',
                16777215 => 'mediumtext',
                2147483647 => 'longtext'),
            'defaultType' => 'text',
            'defaultLength' => null,
            'lengthLessTypes' => array(
                'mediumtext',
                'longtext'
            )
        ),
        'float' => array(
            'defaultType' => 'double'),
        'decimal' => array(
            'lengthTypes' => array(
                65 => 'decimal'),
            'defaultType' => 'decimal',
            'defaultScale' => '0'),
        'datetime' => array(
            'defaultType' => 'datetime'),
        'time' => array(
            'defaultType' => 'time'),
        'date' => array(
            'defaultType' => 'date'),
        'blob' => array(
            'defaultType' => 'longblob'),
        'clob' => array(
            'defaultType' => 'longtext'),
        'enum' => array(
            'defaultType' => 'enum')
    );

    protected $_useUtf8mb4 = true;

    public function __construct($_forceUtf8mb4 = false)
    {
        parent::__construct();
        if (!$_forceUtf8mb4 && $this->_db->getConfig()['charset'] === 'utf8') {
            $this->_useUtf8mb4 = false;
        }
    }

    /**
     * get create table statement
     * 
     * @param Setup_Backend_Schema_Table_Abstract $_table
     * @return string
     */
    public function getCreateStatement(Setup_Backend_Schema_Table_Abstract  $_table)
    {
        $statement = "CREATE TABLE IF NOT EXISTS `" . SQL_TABLE_PREFIX . $_table->name . "` (\n";
        $statementSnippets = array();
     
        foreach ($_table->fields as $field) {
            if (isset($field->name)) {
               $statementSnippets[] = $this->getFieldDeclarations($field);
            }
        }

        foreach ($_table->indices as $index) {
            if ($index->foreign) {
               $statementSnippets[] = $this->getForeignKeyDeclarations($index);
            } else {
               $statementSnippets[] = $this->getIndexDeclarations($index);
            }
        }

        $statement .= implode(",\n", array_filter($statementSnippets)) . "\n)";

        if (isset($_table->engine)) {
            $statement .= " ENGINE=" . $_table->engine . " DEFAULT CHARSET=" . $_table->charset;
        } else {
            if ($this->_useUtf8mb4) {
                $statement .= " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            } else {
                $statement .= " ENGINE=InnoDB DEFAULT CHARSET=utf8";
            }
        }

        $statement .= " ROW_FORMAT=DYNAMIC";

        if (isset($_table->comment)) {
            $statement .= " COMMENT='" . $_table->comment . "'";
        }

        if (Setup_Core::isLogLevel(Zend_Log::TRACE)) Setup_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . $statement);
        
        return $statement;
    }

    /**
     * return list of all foreign key names for given table
     *
     * @param string $tableName
     * @return array list of foreignkey names
     */
    public function getExistingForeignKeys($tableName)
    {
        $select = $this->_db->select()
            ->from(array('table_constraints' => 'INFORMATION_SCHEMA.TABLE_CONSTRAINTS'), array('TABLE_NAME', 'CONSTRAINT_NAME'))
            ->join(
                array('key_column_usage' => 'INFORMATION_SCHEMA.KEY_COLUMN_USAGE'), 
                $this->_db->quoteIdentifier('table_constraints.CONSTRAINT_NAME') . '=' . $this->_db->quoteIdentifier('key_column_usage.CONSTRAINT_NAME'),
                array()
            )
            ->where($this->_db->quoteIdentifier('table_constraints.CONSTRAINT_SCHEMA')    . ' = ?', $this->_config->database->dbname)
            ->where($this->_db->quoteIdentifier('table_constraints.TABLE_SCHEMA')         . ' = ?', $this->_config->database->dbname)
            ->where($this->_db->quoteIdentifier('key_column_usage.TABLE_SCHEMA')          . ' = ?', $this->_config->database->dbname)
            ->where($this->_db->quoteIdentifier('table_constraints.CONSTRAINT_TYPE')      . ' = ?', 'FOREIGN KEY')
            ->where($this->_db->quoteIdentifier('key_column_usage.REFERENCED_TABLE_NAME') . ' = ?', SQL_TABLE_PREFIX . $tableName);

        $foreignKeyNames = array();

        $stmt = $select->query();
        while ($row = $stmt->fetch()) {
            $foreignKeyNames[$row['CONSTRAINT_NAME']] = array(
                'table_name'      => substr($row['TABLE_NAME'], strlen(SQL_TABLE_PREFIX)),
                'constraint_name' => (strpos($row['CONSTRAINT_NAME'], SQL_TABLE_PREFIX) === 0 ?
                    substr($row['CONSTRAINT_NAME'], strlen(SQL_TABLE_PREFIX)) : $row['CONSTRAINT_NAME'])
            );
        }
        
        return $foreignKeyNames;
    }
    
    /**
     * Get schema of existing table
     * 
     * @param String $_tableName
     * 
     * @return Setup_Backend_Schema_Table_Mysql
     */
    public function getExistingSchema($_tableName)
    {
        // Get common table information
        $select = $this->_db->select()
            ->from('information_schema.tables')
            ->where($this->_db->quoteIdentifier('TABLE_SCHEMA') . ' = ?', $this->_config->database->dbname)
            ->where($this->_db->quoteIdentifier('TABLE_NAME') . ' = ?',  SQL_TABLE_PREFIX . $_tableName);
          
          
        $stmt = $select->query();
        $tableInfo = $stmt->fetchObject();
        $stmt->closeCursor();
        
        //$existingTable = new Setup_Backend_Schema_Table($tableInfo);
        $existingTable = Setup_Backend_Schema_Table_Factory::factory('Mysql', $tableInfo);
       // get field informations
        $select = $this->_db->select()
            ->from('information_schema.COLUMNS')
            ->where($this->_db->quoteIdentifier('TABLE_NAME') . ' = ?', SQL_TABLE_PREFIX .  $_tableName);

        $stmt = $select->query();
        $tableColumns = $stmt->fetchAll();
        $stmt->closeCursor();

        foreach ($tableColumns as $tableColumn) {
            $field = Setup_Backend_Schema_Field_Factory::factory('Mysql', $tableColumn);
            $existingTable->addField($field);
            
            if ($field->primary === 'true' || $field->unique === 'true' || $field->mul === 'true') {
                $index = Setup_Backend_Schema_Index_Factory::factory('Mysql', $tableColumn);
                        
                // get foreign keys
                $select = $this->_db->select()
                    ->from('information_schema.KEY_COLUMN_USAGE')
                    ->where($this->_db->quoteIdentifier('TABLE_NAME') . ' = ?', SQL_TABLE_PREFIX .  $_tableName)
                    ->where($this->_db->quoteIdentifier('COLUMN_NAME') . ' = ?', $tableColumn['COLUMN_NAME']);

                $stmt = $select->query();
                $keyUsage = $stmt->fetchAll();
                $stmt->closeCursor();

                foreach ($keyUsage as $keyUse) {
                    if ($keyUse['REFERENCED_TABLE_NAME'] != NULL) {
                        $index->setForeignKey($keyUse);
                    }
                }
                $existingTable->addIndex($index);
            }
        }
        
        return $existingTable;
    }

    /**
     * add column/field to database table
     * 
     * @param string $_tableName
     * @param Setup_Backend_Schema_Field_Abstract $_declaration
     * @param int $_position of future column
     */    
    public function addCol($_tableName, Setup_Backend_Schema_Field_Abstract $_declaration, $_position = NULL)
    {
        $this->execQueryVoid($this->addAddCol(null, $_tableName, $_declaration, $_position));
    }

    /**
     * add column/field to database table
     *
     * @param string $_query
     * @param string $_tableName
     * @param Setup_Backend_Schema_Field_Abstract $_declaration
     * @param int $_position of future column
     * @return string
     */
    public function addAddCol($_query, $_tableName, Setup_Backend_Schema_Field_Abstract $_declaration, $_position = NULL)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Add new column to table ' . $_tableName);

        if (empty($_query)) {
            $_query = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "`";
        } else {
            $_query .= ',';
        }

        $_query .= " ADD COLUMN " . $this->getFieldDeclarations($_declaration);

        if ($_position !== NULL) {
            if ($_position == 0) {
                $_query .= ' FIRST ';
            } else {
                $before = $this->execQuery('DESCRIBE `' . SQL_TABLE_PREFIX . $_tableName . '` ');
                $_query .= ' AFTER `' . $before[$_position]['Field'] . '`';
            }
        }

        return $_query;
    }
    
    /**
     * rename or redefines column/field in database table
     * 
     * @param string $_tableName
     * @param Setup_Backend_Schema_Field_Abstract $_declaration
     * @param string $_oldName column/field name
     */    
    public function alterCol($_tableName, Setup_Backend_Schema_Field_Abstract $_declaration, $_oldName = NULL)
    {
        $this->execQueryVoid($this->addAlterCol(null, $_tableName, $_declaration, $_oldName));
    }

    /**
     * rename or redefines column/field in database table
     *
     * @param string $_query
     * @param string $_tableName
     * @param Setup_Backend_Schema_Field_Abstract $_declaration
     * @param string $_oldName column/field name
     * @return string
     */
    public function addAlterCol($_query, $_tableName, Setup_Backend_Schema_Field_Abstract $_declaration, $_oldName = NULL)
    {
        if (empty($_query)) {
            $_query = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "`";
        } else {
            $_query .= ',';
        }

        $_query .= " CHANGE COLUMN " ;

        if ($_oldName === NULL) {
            $oldName = $_declaration->name;
        } else {
            $oldName = $_oldName;
        }

        $_query .= " `" . $oldName .  "` " . $this->getFieldDeclarations($_declaration);

        return $_query;
    }
 
    /**
     * add a key to database table
     * 
     * @param string $_tableName
     * @param Setup_Backend_Schema_Index_Abstract $_declaration
     */     
    public function addIndex($_tableName ,  Setup_Backend_Schema_Index_Abstract $_declaration)
    {
        $this->execQueryVoid($this->addAddIndex(null, $_tableName, $_declaration));
    }

    /**
     * add a key to database table
     *
     * @param string $_query
     * @param string $_tableName
     * @param Setup_Backend_Schema_Index_Abstract $_declaration
     * @return string
     */
    public function addAddIndex($_query, $_tableName ,  Setup_Backend_Schema_Index_Abstract $_declaration)
    {
        if (empty($indexDeclaration = $this->getIndexDeclarations($_declaration))) {
            return $_query;
        }

        if (empty($_query)) {
            $_query = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "`";
        } else {
            $_query .= ',';
        }

        $_query .= " ADD " . $indexDeclaration;

        return $_query;
    }

    /**
     * create the right mysql-statement-snippet for keys
     *
     * @param   Setup_Backend_Schema_Index_Abstract $_key
     * @param String $_tableName [is not used in this Backend (MySQL)]
     * @return  string
     * @throws  Setup_Exception_NotFound
     */
    public function getIndexDeclarations(Setup_Backend_Schema_Index_Abstract $_key, $_tableName = '')
    {
        $keys = array();

        $snippet = "  KEY `" . $_key->name . "`";
        if (!empty($_key->primary)) {
            $snippet = '  PRIMARY KEY ';
        } elseif (!empty($_key->unique)) {
            $snippet = "  UNIQUE KEY `" . $_key->name . "`" ;
        } elseif (!empty($_key->fulltext)) {
            if (!$this->supports('mysql >= 5.6.4 | mariadb >= 10.0.5') ||
                    !Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_FULLTEXT_INDEX)) {
                if (Setup_Core::isLogLevel(Zend_Log::WARN)) Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .
                    ' full text search is only supported on mysql 5.6.4+ / mariadb 10.0.5+ ... do yourself a favor and migrate. You need to add the missing full text indicies yourself manually now after migrating. Skipping creation of full text index!');
                return '';
            }
            $snippet = " FULLTEXT KEY `" . $_key->name . "`" ;
        }
        
        foreach ((array)$_key->field as $keyfield) {
            $key = '`' . (string)$keyfield . '`';
            if ($_key->length !== NULL) {
                $key .= ' (' . $_key->length . ')';
            }
            else if ((isset($_key->fieldLength[(string)$keyfield]) || array_key_exists((string)$keyfield, $_key->fieldLength))) {
                $key .= ' (' . $_key->fieldLength[(string)$keyfield] . ')';
            }
            $keys[] = $key;
        }

        if (empty($keys)) {
            throw new Setup_Exception_NotFound('no keys for index found');
        }

        $snippet .= ' (' . implode(",", $keys) . ')';
        
        return $snippet;
    }

    /**
     *  create the right mysql-statement-snippet for foreign keys
     *
     * @param Setup_Backend_Schema_Index_Abstract $_key the xml index definition
     * @return string
     */
    public function getForeignKeyDeclarations(Setup_Backend_Schema_Index_Abstract $_key)
    {
        $snippet = '  CONSTRAINT `' . SQL_TABLE_PREFIX . $_key->name . '` FOREIGN KEY ';
        $snippet .= '(`' . $_key->field . "`) REFERENCES `" . SQL_TABLE_PREFIX
                    . $_key->referenceTable . 
                    "` (`" . $_key->referenceField . "`)";

        if (!empty($_key->referenceOnDelete)) {
            $snippet .= " ON DELETE " . strtoupper($_key->referenceOnDelete);
        }
        if (!empty($_key->referenceOnUpdate)) {
            $snippet .= " ON UPDATE " . strtoupper($_key->referenceOnUpdate);
        }

        return $snippet;
    }
    
    /**
     * enable/disabled foreign key checks
     *
     * @param integer|string|boolean $_value
     */
    public function setForeignKeyChecks($_value)
    {
        if ($_value == 0 || $_value == 1) {
            $this->_db->query("SET FOREIGN_KEY_CHECKS=" . $_value);
        }
    }

    /**
     * Backup Database
     *
     * @param $option
     */
    public function backup($option)
    {
        $backupDir = $option['backupDir'];

        // hide password from shell via my.cnf
        $mycnf = $backupDir . '/my.cnf';
        $this->_createMyConf($mycnf, $this->_config->database);

        $ignoreTables = '';
        if (count($option['structTables']) > 0) {
            $structDump = 'mysqldump --defaults-extra-file=' . $mycnf . ' --no-data ' .
                escapeshellarg($this->_config->database->dbname);
            foreach($option['structTables'] as $table) {
                $structDump .= ' ' . escapeshellarg($table);
                $ignoreTables .= '--ignore-table=' . escapeshellarg($this->_config->database->dbname . '.' . $table) . ' ';
            }
        } else {
            $structDump = false;
        }

        $cmd = ($structDump!==false?'{ ':'')
              ."mysqldump --defaults-extra-file=$mycnf "
              .$ignoreTables
              ."--single-transaction --max_allowed_packet=512M "
              ."--opt "
              . escapeshellarg($this->_config->database->dbname)
              . ($structDump!==false?'; ' . $structDump . '; }':'')
              ." | bzip2 > $backupDir/tine20_mysql.sql.bz2";

        exec($cmd);
        unlink($mycnf);

        // validate all tables have been dumped
        exec("bzcat $backupDir/tine20_mysql.sql.bz2 | grep 'CREATE TABLE `'", $output);
        array_walk($output, function (&$val) {
            if (preg_match('/`(.*)`/', $val, $m)) {
                $val = $m[1];
            } else {
                $val = null;
            }
        });
        $output = array_filter($output);
        $allTables = $this->_db->listTables();
        $diff = array_diff($allTables, $output);
        if (!empty($diff)) {
            throw new Tinebase_Exception_Backend('dump did not work, table diff: ' . print_r($diff, true));
        }
    }

    /**
     * Restore Database
     *
     * @param $backupDir
     * @throws Exception
     */
    public function restore($backupDir)
    {
        $mysqlBackupFile = $backupDir . '/tine20_mysql.sql.bz2';
        if (! file_exists($mysqlBackupFile)) {
            throw new Exception("$mysqlBackupFile not found");
        }

        // hide password from shell via my.cnf
        $mycnf = $backupDir . '/my.cnf';
        $this->_createMyConf($mycnf, $this->_config->database);

        $cmd = "bzcat $mysqlBackupFile"
             . " | mysql --defaults-extra-file=$mycnf -f "
             . escapeshellarg($this->_config->database->dbname);

        if (Setup_Core::isLogLevel(Zend_Log::DEBUG)) Setup_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
            ' restore cmd: ' . $cmd);

        exec($cmd);
        unlink($mycnf);
    }

    /**
     * create my.cnf
     *
     * @param $path
     * @param $config
     */
    protected function _createMyConf($path, $config)
    {
        $port = $config->port ? $config->port : 3306;

        $mycnfData = <<<EOT
[client]
host = {$config->host}
port = {$port}
user = {$config->username}
password = {$config->password}
EOT;
        file_put_contents($path, $mycnfData);
    }

    /**
     * checks whether this backend supports a specific requirement or not
     *
     * @param $requirement
     * @return bool
     */
    public function supports($requirement)
    {
        return static::mariaDBFuckedUsSupports($this->_db, $requirement);
    }


    public static function mariaDBFuckedUsSupports($db, $requirement)
    {
        if (preg_match('/mysql ([<>=]+) ([\d\.]+)/', $requirement, $m))
        {
            $version = $db->getServerVersion();
            if (version_compare($version, '10', '<') === true && version_compare($version, $m[2], $m[1]) === true) {
                return true;
            }
        }
        if (preg_match('/mariadb ([<>=]+) ([\d\.]+)/', $requirement, $m))
        {
            $version = $db->getServerVersion();
            if (version_compare($m[2], '10', '>=') === true && version_compare($version, $m[2], $m[1]) === true) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $_buffer
     * @param Setup_Backend_Schema_Field_Abstract $_field
     * @return array
     */
    protected function _addDeclarationCollation(array $_buffer, Setup_Backend_Schema_Field_Abstract $_field)
    {
        if (isset($_field->collation)) {
            $collation = ($_field->collation == 'utf8mb4_bin' && ! $this->_useUtf8mb4) ? 'utf8_bin' : $_field->collation;
            $_buffer[] = 'COLLATE ' . $collation;
        }
        return $_buffer;
    }
}
