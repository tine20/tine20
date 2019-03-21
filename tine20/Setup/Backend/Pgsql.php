<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * setup backend class for PostgreSQL 8.3 +
 * based on class Setup_Backend_Mysql
 * @package     Setup
 */
class Setup_Backend_Pgsql extends Setup_Backend_Abstract
{
    /**
     * Define how database agnostic data types get mapped to postgresql data types
     * @todo reviews data type
     * @var array
     */
    protected $_typeMappings = array(
        'integer' => array(
            'lengthTypes' => array(
                4 => 'smallint',
                19 => 'integer',
                64 => 'bigint'),
            'defaultType' => 'integer',
            'defaultLength' => Setup_Backend_Abstract::INTEGER_DEFAULT_LENGTH),
        'boolean' => array(
            'defaultType' => 'NUMERIC',
            'defaultScale' => 0,
            'defaultLength' => 1),
        'text' => array(
            'lengthTypes' => array(
                255 => 'character varying',
                65535 => 'character varying',
                16777215 => 'text',
                2147483647 => 'text'),
            'defaultType' => 'text',
            'defaultLength' => null,
            'lengthLessTypes' => array(
                'text',
            )),
        'float' => array(
            'defaultType' => 'double precision',
            'defaultLength' => null),
        'decimal' => array(
            'defaultType' => 'numeric',
            'defaultLength' => null),
        'datetime' => array(
            'defaultType' => 'timestamp',
            'defaultLength' => null),
        'time' => array(
            'defaultType' => 'time',
            'defaultLength' => null),
        'date' => array(
            'defaultType' => 'date',
            'defaultLength' => null),
        'blob' => array(
            'defaultType' => 'text',
            'defaultLength' => null),
        'clob' => array(
            'defaultType' => 'text',
            'defaultLength' => null),
        'enum' => array(
            'defaultType' => 'enum',
            'defaultLength' => null)
    );

    /**
     * Generates an SQL CREATE STATEMENT
     * @param Setup_Backend_Schema_Table_Abstract $_table
     * @return array CREATE TABLE statement, sequence, indexes
     * @throws Setup_Exception_NotFound
     */
    public function getCreateStatement(Setup_Backend_Schema_Table_Abstract $_table) 
    {
        $enums = array();
        $statement = "CREATE TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_table->name) . " (\n";
        $statementSnippets = array();

        // get primary key now because it is necessary in two places
        $primaryKey = $this->_getPrimaryKeyName($_table);

        foreach ($_table->fields as $field) {
            if (isset($field->name)) {
                $fieldDeclarations = $this->getFieldDeclarations($field, $_table->name);

                if (strpos($primaryKey, $field->name) !== false) {
                    // replaces integer auto_increment with serial
                    $sequence = SQL_TABLE_PREFIX . $_table->name . "_{$primaryKey}_seq";
                    // don't create sequence if is field is not auto_increment
                    $primaryKey = (strpos($fieldDeclarations, 'auto_increment') !== false) ? $primaryKey : null;
                    $fieldDeclarations = str_replace('integer NOT NULL auto_increment', 'integer NOT NULL DEFAULT nextval(' . $this->_db->quote($sequence) . ')', $fieldDeclarations);
                } elseif(strpos($fieldDeclarations, 'auto_increment') !== false) {
                    $fieldDeclarations = str_replace('integer NOT NULL auto_increment', 'SERIAL NOT NULL', $fieldDeclarations);
                }

                $statementSnippets[] = $fieldDeclarations;
            }
        }

        $createIndexStatement = array();

        foreach ($_table->indices as $index) {
            if ($index->foreign) {
                $statementSnippets[] = $this->getForeignKeyDeclarations($index);

            } else {
                $statementSnippet = $this->getIndexDeclarations($index, $_table->name);
                if (strpos($statementSnippet, 'CREATE INDEX') !== false) {
                    $createIndexStatement[] = $statementSnippet;
                } else {
                    $statementSnippets[] = $statementSnippet;
                }
            }
        }

        $statement .= implode(",\n", array_filter($statementSnippets)) . "\n)";
        
        return array('table' => $statement, 'index' => $createIndexStatement, 'primary' => $primaryKey);
    }

    /**
     *
     * Gets the primary key name
     * @param Setup_Backend_Schema_Table_Abstract $table
     */
    private function _getPrimaryKeyName($table)
    {
        $primaryKeyName = '';

        foreach ($table->indices as $index) {
            if ($index->primary === 'true') {
                foreach ($index->field as $field) {
                    $primaryKeyName .= $field . '-';
                }
                $primaryKeyName = substr($primaryKeyName, 0, strlen($primaryKeyName) - 1);

                return $primaryKeyName;
            }
        }

        return null;
    }
    
    /**
     * check if given constraint exists 
     *
     * @param string $_tableName
     * @return boolean|string "version" if the table exists, otherwise false
     */
    protected function _constraintExists($_name)
    {
        $select = $this->_db->select()
            ->from('pg_constraint')
            ->where($this->_db->quoteIdentifier('conname') . ' = ?', $_name);
        
        $stmt = $select->query();
        $constraint = $stmt->fetch();
        
        return ($constraint === false) ? false : true;
    }
    
    /**
     * (non-PHPdoc)
     * @see Setup_Backend_Interface::getExistingForeignKeys()
     */
    public function getExistingForeignKeys($tableName)
    {
        $select = $this->_db->select()
            ->from(array('table_constraints' => 'information_schema.table_constraints'), array('table_name', 'constraint_name'))
            ->join(
                array('constraint_column_usage' => 'information_schema.constraint_column_usage'), 
                $this->_db->quoteIdentifier('table_constraints.constraint_name') . '=' . $this->_db->quoteIdentifier('constraint_column_usage.constraint_name'),
                array()
            )
            ->where($this->_db->quoteIdentifier('table_constraints.constraint_catalog') . ' = ?', $this->_config->database->dbname)
            ->where($this->_db->quoteIdentifier('table_constraints.constraint_type')    . ' = ?', 'FOREIGN KEY')
            ->where($this->_db->quoteIdentifier('constraint_column_usage.table_name')   . ' = ?', SQL_TABLE_PREFIX . $tableName);

        $foreignKeyNames = array();
        
        $stmt = $select->query();
        while ($row = $stmt->fetch()) {
            $foreignKeyNames[$row['constraint_name']] = array(
                'table_name'      => str_replace(SQL_TABLE_PREFIX, '', $row['table_name']), 
                'constraint_name' => str_replace(SQL_TABLE_PREFIX, '', $row['constraint_name']));
        }
        
        return $foreignKeyNames;
    }
    
    /**
     * Get schema of existing table
     *
     * @param String $_tableName
     *
     * @return Setup_Backend_Schema_Table_Pgsql
     */
    public function getExistingSchema($_tableName)
    {
        // Get common table information
        $select = $this->_db->select()
            ->from('information_schema.tables')
            ->where($this->_db->quoteIdentifier('TABLE_SCHEMA') . ' = ?', $this->_config->database->dbname)
            ->where($this->_db->quoteIdentifier('TABLE_NAME') . ' = ?', SQL_TABLE_PREFIX . $_tableName);


        $stmt = $select->query();
        $tableInfo = $stmt->fetchObject();

        //$existingTable = new Setup_Backend_Schema_Table($tableInfo);
        $existingTable = Setup_Backend_Schema_Table_Factory::factory('Pgsql', $tableInfo);
        // get field informations
        $select = $this->_db->select()
            ->from('information_schema.COLUMNS')
            ->where($this->_db->quoteIdentifier('TABLE_NAME') . ' = ?', SQL_TABLE_PREFIX . $_tableName);

        $stmt = $select->query();
        $tableColumns = $stmt->fetchAll();

        foreach ($tableColumns as $tableColumn) {
            $field = Setup_Backend_Schema_Field_Factory::factory('Pgsql', $tableColumn);
            $existingTable->addField($field);

            if ($field->primary === 'true' || $field->unique === 'true' || $field->mul === 'true') {
                $index = Setup_Backend_Schema_Index_Factory::factory('Pgsql', $tableColumn);

                // get foreign keys
                $select = $this->_db->select()
                        ->from('information_schema.KEY_COLUMN_USAGE')
                        ->where($this->_db->quoteIdentifier('TABLE_NAME') . ' = ?', SQL_TABLE_PREFIX . $_tableName)
                        ->where($this->_db->quoteIdentifier('COLUMN_NAME') . ' = ?', $tableColumn['COLUMN_NAME']);

                $stmt = $select->query();
                $keyUsage = $stmt->fetchAll();

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
     * @param string tableName
     * @param Setup_Backend_Schema_Field_Abstract declaration
     * @param int position of future column
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
            $_query = 'ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName);
        } else {
            $_query .= ',';
        }

        $_query .= ' ADD COLUMN ' . $this->getFieldDeclarations($_declaration);

        return $_query;
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
            $_query = 'ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName);
        } else {
            $_query .= ',';
        }

        $quotedName = $this->_db->quoteIdentifier($_declaration->name);

        // rename the column if needed
        if ($_oldName !== NULL) {
            return $_query . ' RENAME COLUMN ' . $this->_db->quoteIdentifier($_oldName) . ' TO ' . $quotedName;
        }

        $fieldDeclaration = $this->getFieldDeclarations($_declaration);

        // strip of column name from the beginning
        $type      = trim(str_replace($quotedName, null, $fieldDeclaration));
        // cut of NOT NULL and DEFAULT from the end
        $type      = preg_replace(array('/ (NOT NULL|DEFAULT .*)/'), null, $type);

        $_query .= ' ALTER COLUMN ' . $quotedName . ' TYPE ' . $type . ' USING CAST(' . $quotedName . ' AS ' . $type . ')';

        if (preg_match('/NOT NULL/', $fieldDeclaration)) {
            $this->execQueryVoid($_query);
            $_query = null;
            $statement = 'ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . ' ALTER COLUMN ' . $quotedName . ' SET NOT NULL ';
            $this->execQueryVoid($statement);
        }

        if (isset($_declaration->default)) {
            if (null !== $_query) {
                $this->execQueryVoid($_query);
                $_query = null;
            }
            $statement = 'ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . ' ALTER COLUMN ' . $quotedName . ' SET ' . $this->_db->quoteInto("DEFAULT ?", $_declaration->default);
            $this->execQueryVoid($statement);
        }

        return $_query;
    }
    /**
     * rename or redefines column/field in database table
     *
     * @param string tableName
     * @param Setup_Backend_Schema_Field_Abstract declaration
     * @param string old column/field name
     */
    public function alterCol($_tableName, Setup_Backend_Schema_Field_Abstract $_declaration, $_oldName = NULL) 
    {
        $query = $this->addAlterCol(null, $_tableName, $_declaration, $_oldName);
        if (null !== $query) {
            $this->execQueryVoid($query);
        }
    }

    /**
     * add a key to database table
     *
     * @param string $_query
     * @param string tableName
     * @param Setup_Backend_Schema_Index_Abstract declaration
     * @return string
     */
    public function addAddIndex($_query, $_tableName, Setup_Backend_Schema_Index_Abstract $_declaration)
    {
        if (!empty($_declaration->primary)) {
            $identifier = SQL_TABLE_PREFIX . $_tableName . '_pkey';
        } elseif (!empty($_declaration->unique)) {
            $identifier = SQL_TABLE_PREFIX . $_tableName . '_' . $_declaration->name . '_key';
        } else {
            $identifier = SQL_TABLE_PREFIX . $_tableName . '_' . $_declaration->name;
        }

        if ($this->_constraintExists($identifier)) {
            throw new Zend_Db_Statement_Exception('index does exist already');
        }

        if (empty($indexSnippet = $this->getIndexDeclarations($_declaration, $_tableName))) {
            return $_query;
        }

        if (strpos($indexSnippet, 'CREATE INDEX') !== false) {
            $this->execQueryVoid($_query);
            $_query = null;
            $this->execQueryVoid($indexSnippet);
        } else {
            if (empty($_query)) {
                $_query = 'ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName);
            } else {
                $_query .= ',';
            }
            $_query .= ' ADD ' . $indexSnippet;
        }

        return $_query;
    }

    /**
     * add a key to database table
     *
     * @param string tableName
     * @param Setup_Backend_Schema_Index_Abstract declaration
     */
    public function addIndex($_tableName, Setup_Backend_Schema_Index_Abstract $_declaration) 
    {
        $this->execQueryVoid($this->addAddIndex(null, $_tableName, $_declaration));
    }

    /**
     * create the right pgsql-statement-snippet for keys.
     * return constraints to add to create table statement or
     * return create index statement
     * @param   Setup_Backend_Schema_Index_Abstract $_key
     * @param String | optional $_tableName [is not used in this Backend (PgSQL)]
     * @return  string
     * @throws  Setup_Exception_NotFound
     */
    public function getIndexDeclarations(Setup_Backend_Schema_Index_Abstract $_key, $_tableName = '')
    {
        if (!empty($_key->primary)) {
            $identifier = $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName . '_pkey');
            $snippet = " CONSTRAINT $identifier PRIMARY KEY";
        } elseif (!empty($_key->unique)) {
            $identifier  = $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName . '_' . $_key->name . '_key');
            $snippet = "CONSTRAINT $identifier UNIQUE";
        } elseif (!empty($_key->fulltext)) {
            if (Setup_Core::isLogLevel(Zend_Log::WARN)) Setup_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .
                ' full text search is only supported on mysql/mariadb 5.6.4+ ... do yourself a favor and migrate. You need to add the missing full text indicies yourself manually now after migrating. Skipping creation of full text index!');
            return '';
        } else {
            $identifier = $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName . '_' . $_key->name);
            $snippet = "CREATE INDEX $identifier ON " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName);
        }
        
        $keys = array();
        
        foreach ((array) $_key->field as $keyfield) {
            $keys[] = $this->_db->quoteIdentifier((string) $keyfield);
        }

        if (empty($keys)) {
            throw new Setup_Exception_NotFound('no keys for index found');
        }

        $snippet .= ' (' . implode(',', $keys) . ')';

        return $snippet;
    }

    /**
     *  create the right mysql-statement-snippet for foreign keys
     *
     * @param object $_key the xml index definition
     * @return string
     */
    public function getForeignKeyDeclarations(Setup_Backend_Schema_Index_Abstract $_key)
    {
        $snippet = ' CONSTRAINT ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_key->name) . 
            ' FOREIGN KEY (' . $this->_db->quoteIdentifier($_key->field) . ')' . 
            ' REFERENCES ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_key->referenceTable) . 
                ' (' . $this->_db->quoteIdentifier($_key->referenceField) . ')';

        if (!empty($_key->referenceOnDelete)) {
            $snippet .= ' ON DELETE ' . strtoupper($_key->referenceOnDelete);
        }
        if (!empty($_key->referenceOnUpdate)) {
            $snippet .= ' ON UPDATE ' . strtoupper($_key->referenceOnUpdate);
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
        // does nothing
    }

    /**
     * takes the xml stream and creates a table
     *
     * @param object $_table xml stream
     * @return boolean
     */
    public function createTable(Setup_Backend_Schema_Table_Abstract $_table)
    {
        if (false === $this->_checkTableRequirements($_table)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Do not create table ' . $_table->name . ': Requirement missing.');
            return false;
        }

        // receives an array with CREATE TABLE and CREATE INDEX statements
        $statements = $this->getCreateStatement($_table);

        try {
            // creates sequence
            if (!empty($statements['primary'])) {
                $sequence = $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_table->name . '_' . $statements['primary'] . '_seq');

                $createSequence = 'CREATE SEQUENCE ' . $sequence;

                $this->execQueryVoid($createSequence);
            }

            // creates table
            $this->execQueryVoid($statements['table']);

            // creates indexes
            foreach ($statements['index'] as $createIndex) {
                $this->execQueryVoid($createIndex);
            }

            // alters sequence
            if (!empty($statements['primary'])) {
                $alterSequence = 'ALTER SEQUENCE ' . $sequence . ' OWNED BY ' . SQL_TABLE_PREFIX . $_table->name . '.' . $statements['primary'];
                $this->execQueryVoid($alterSequence);
            }

            return true;

        } catch (Exception $e) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Exception: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
        }

        return false;
    }
    
    /**
     * removes a foreign key from database table
     * 
     * @param string tableName
     * @param string foreign key name
     */     
    public function dropForeignKey($_tableName, $_name)
    {
        if (! $this->_constraintExists(SQL_TABLE_PREFIX . $_name)) {
            throw new Zend_Db_Statement_Exception('foreign key does not exist');
        }
        
        $statement = "ALTER TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) 
            . " DROP CONSTRAINT " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_name);
        
        $this->execQueryVoid($statement);
    }
    
    /**
     * removes a key from database table
     * 
     * @param string tableName 
     * @param string key name
     */    
    public function dropIndex($_tableName, $_indexName)
    {
        $indexName = SQL_TABLE_PREFIX . $_tableName . '_' . $_indexName;
        if ($this->_constraintExists($indexName)) {
            $statement = "DROP INDEX " . $this->_db->quoteIdentifier($indexName);
    
            $this->execQueryVoid($statement);
            
            return;
        }
        
        $indexName = SQL_TABLE_PREFIX . $_tableName . '_' . $_indexName . '_key';
        if ($this->_constraintExists($indexName)) {
            $statement = "ALTER TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . " DROP CONSTRAINT " . $this->_db->quoteIdentifier($indexName);
    
            $this->execQueryVoid($statement);
            
            return;
        }
        
        throw new Zend_Db_Statement_Exception("index $_indexName does not exist");
    }
    
    /**
     * removes a primary key from database table
     * 
     * @param string tableName (there is just one primary key...)
     */         
    public function dropPrimaryKey($_tableName)
    {
        $indexName = SQL_TABLE_PREFIX . $_tableName . '_pkey';
        
        if (! $this->_constraintExists($indexName)) {
            throw new Zend_Db_Statement_Exception("primary key for table $_tableName does not exist");
        }
        
        $statement = "ALTER TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . " DROP CONSTRAINT " . $this->_db->quoteIdentifier($indexName);
        $this->execQueryVoid($statement);
    }
    
    /**
     * create the right postgreSql-statement-snippet for columns/fields
     * PostgreSQL has not unsigned modifier
     *
     * @param Setup_Backend_Schema_Field_Abstract field / column
     * @param String | optional $_tableName [Not used in this backend (PostgreSQL)]
     * @return string
     */
    public function getFieldDeclarations(Setup_Backend_Schema_Field_Abstract $_field, $_tableName = '')
    {
        $_field->unsigned = false;

        $fieldDeclarations = parent::getFieldDeclarations($_field, $_tableName);
        
        $fieldTypes = array ('tinyint', 'smallint', 'mediumint', 'bigint', 'int', 'integer');

        if(strpos($fieldDeclarations, 'auto_increment') !== false) {
            foreach ($fieldTypes as $fieldType) {
                $fieldDeclarations = preg_replace('/ ' . $fieldType . '\(\d*\)/', ' integer', $fieldDeclarations);
            }
            $fieldDeclarations = str_replace('integer NOT NULL auto_increment', 'SERIAL NOT NULL', $fieldDeclarations);
        } else {
            foreach ($fieldTypes as $fieldType) {
                $fieldDeclarations = preg_replace('/ ' . $fieldType . '\(\d*\)/', ' ' . $fieldType, $fieldDeclarations);
            }
        }
        
        return $fieldDeclarations;
    }
}
