<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
            'defaultLength' => self::INTEGER_DEFAULT_LENGTH),            
        'boolean' => array(
            'defaultType' => 'NUMERIC',
            'defaultScale' => 0,
            'defaultLength' => 1),
        'text' => array(
            'lengthTypes' => array(
                256 => 'character varying', //@todo this should be 255 indeed but we have 256 in our setup.xml files
                65535 => 'character varying',
                16777215 => 'character varying',
                4294967295 => 'character varying'),
            'defaultType' => 'text',
            'defaultLength' => null),
        'float' => array(
            'defaultType' => 'double precision',
            'defaultLength' => null ),
        'decimal' => array(
            'defaultType' => 'numeric',
            'defaultLength' => null ),
        'datetime' => array(
            'defaultType' => 'timestamp with time zone',
            'defaultLength' => null ),
        'time' => array(
            'defaultType' => 'time with timezone',
            'defaultLength' => null ),
        'date' => array(
            'defaultType' => 'date',
            'defaultLength' => null ),
        'blob' => array(
            'defaultType' => 'text',
            'defaultLength' => null ),
        'clob' => array(
            'defaultType' => 'text',
            'defaultLength' => null ),
        'enum' => array(
            'defaultType' => 'enum',
            'defaultLength' => null     )
    );
    
    /**
     * Generates an SQL CREATE STATEMENT
     * @param Setup_Backend_Schema_Table_Abstract $_table
     * @return array CREATE TABLE statement, sequence, indexes
     * @throws Setup_Exception_NotFound
     */
    public function getCreateStatement(Setup_Backend_Schema_Table_Abstract  $_table)
    {
        
        $enums = array();
        $statement = "CREATE TABLE " . SQL_TABLE_PREFIX . $_table->name . " (\n";
        $statementSnippets = array();

        foreach ($_table->fields as $field) {
            if (isset($field->name)) {
                // getFieldDeclarations() adds 'unsigned' that it doesn't exist in PortgreSQL
                if (isset($field->unsigned)) $field->unsigned = false;
                $fieldDeclarations = $this->getFieldDeclarations($field,$_table->name);
                // removes length of integer between parenthesis 
                $fieldDeclarations = preg_replace('/integer\([0-9][0-9]\)/', 'integer', $fieldDeclarations);
                $fieldDeclarations = preg_replace('/smallint\([0-9][0-9]\)/', 'smallint', $fieldDeclarations);
                $fieldDeclarations = preg_replace('/bigint\([0-9][0-9]\)/', 'bigint', $fieldDeclarations);
                // replaces integer auto_increment with serial
                $fieldDeclarations = str_replace('integer NOT NULL auto_increment', "serial NOT NULL", $fieldDeclarations);
                $statementSnippets[] = $fieldDeclarations;
            }
        }

        $createIndexStatement = '';
        
        foreach ($_table->indices as $index) {
            if ($index->foreign) {
                $statementSnippets[] = $this->getForeignKeyDeclarations($index);
            } else {
                $statementSnippet = $this->getIndexDeclarations($index,$_table->name);
                if (strpos($statementSnippet, 'CREATE INDEX')!==false)
                {
                    $createIndexStatement = $statementSnippet;
                }
                else
                {
                    $statementSnippets[] = $statementSnippet;
                }
            }
        }

        $statement .= implode(",\n", $statementSnippets) . "\n)";

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . "\n" .  $statement . "\n" . $createIndexStatement);

        return array('table'=>$statement,'index'=>$createIndexStatement);
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
        ->where($this->_db->quoteIdentifier('TABLE_NAME') . ' = ?',  SQL_TABLE_PREFIX . $_tableName);


        $stmt = $select->query();
        $tableInfo = $stmt->fetchObject();

        //$existingTable = new Setup_Backend_Schema_Table($tableInfo);
        $existingTable = Setup_Backend_Schema_Table_Factory::factory('Pgsql', $tableInfo);
        // get field informations
        $select = $this->_db->select()
        ->from('information_schema.COLUMNS')
        ->where($this->_db->quoteIdentifier('TABLE_NAME') . ' = ?', SQL_TABLE_PREFIX .  $_tableName);

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
                ->where($this->_db->quoteIdentifier('TABLE_NAME') . ' = ?', SQL_TABLE_PREFIX .  $_tableName)
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
        $statement = "ALTER TABLE '" . SQL_TABLE_PREFIX . $_tableName . "' ADD COLUMN " ;

        $statement .= $this->getFieldDeclarations($_declaration);

        if ($_position !== NULL) {
            if ($_position == 0) {
                $statement .= ' FIRST ';
            } else {
                $before = $this->execQuery('DESCRIBE \'' . SQL_TABLE_PREFIX . $_tableName . '\' ');
                $statement .= ' AFTER \'' . $before[$_position]['Field'] . '\'';
            }
        }

        $this->execQueryVoid($statement);
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
        $statement = "ALTER TABLE '" . SQL_TABLE_PREFIX . $_tableName . "' CHANGE COLUMN " ;

        if ($_oldName === NULL) {
            $oldName = $_declaration->name;
        } else {
            $oldName = $_oldName;
        }

        $statement .= " '" . $oldName .  "' " . $this->getFieldDeclarations($_declaration);
        $this->execQueryVoid($statement);
    }

    /**
     * add a key to database table
     *
     * @param string tableName
     * @param Setup_Backend_Schema_Index_Abstract declaration
     */
    public function addIndex($_tableName ,  Setup_Backend_Schema_Index_Abstract $_declaration)
    {
        $statement = "ALTER TABLE '" . SQL_TABLE_PREFIX . $_tableName . "' ADD "
        . $this->getIndexDeclarations($_declaration);
        $this->execQueryVoid($statement);
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
        $isNotIndex = false;
        
        $keys = array();

        $indexes = str_replace('-', ',', $_key->name);
        $snippet = 'CREATE INDEX  ' . $_tableName . '_' . $_key->name . ' ON ' . SQL_TABLE_PREFIX . $_tableName . "($indexes);";
        $snippet = str_replace('-', '_', $snippet);
        if (!empty($_key->primary)) {
            $pkey = $_tableName . '_pkey';
            $pkey = str_replace('-', '_', $pkey);
            $snippet = " CONSTRAINT $pkey PRIMARY KEY ";
            $isNotIndex = true;
        } else if (!empty($_key->unique)) {
            $unique = $_tableName . '_' . $_key->name . '_' . 'key';
            $unique = str_replace('-', '_', $unique);
            $snippet = "CONSTRAINT $unique UNIQUE " ;
            $isNotIndex = true;
        }

        foreach ($_key->field as $keyfield) {
            $key = (string)$keyfield;
            $keys[] = $key;
        }

        if (empty($keys)) {
            throw new Setup_Exception_NotFound('no keys for index found');
        }

        if ($isNotIndex)
        {
            $snippet .= ' (' . implode(",", $keys) . ')';
        }

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
        $snippet = '  CONSTRAINT ' . SQL_TABLE_PREFIX . $_key->referenceTable . '_' . $_key->field . ' FOREIGN KEY ';
        $snippet .= '(' . $_key->field . ") REFERENCES " . SQL_TABLE_PREFIX
        . $_key->referenceTable .
                    " (" . $_key->referenceField . ")";

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
     * takes the xml stream and creates a table
     *
     * @param object $_table xml stream
     */ 
    public function createTable(Setup_Backend_Schema_Table_Abstract  $_table)
    {
        // receives an array with CREATE TABLE and CREATE INDEX statements 
        $statements = $this->getCreateStatement($_table);
        
        try {
            // creates table
            $this->execQueryVoid($statements['table']);
            
            // creates indexes
            if (!empty($statements['index'])) $this->execQueryVoid($statements['index']);
        }
        catch (Exception $e)
        {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Exception: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
        }
    }
        
}
