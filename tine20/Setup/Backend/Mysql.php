<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * setup backend class for MySQL 5.0 +
 *
 * @package     Setup
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
                256 => 'varchar', //@todo this should be 255 indeed but we have 256 in our setup.xml files
                65535 => 'text',
                16777215 => 'mediumtext',
                4294967295 => 'longtext'),
            'defaultType' => 'text',
            'defaultLength' => null),
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
 
    
    public function getCreateStatement(Setup_Backend_Schema_Table_Abstract  $_table)
    {
        $statement = "CREATE TABLE `" . SQL_TABLE_PREFIX . $_table->name . "` (\n";
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

        $statement .= implode(",\n", $statementSnippets) . "\n)";

        if (isset($_table->engine)) {
            $statement .= " ENGINE=" . $_table->engine . " DEFAULT CHARSET=" . $_table->charset;
        } else {
            $statement .= " ENGINE=InnoDB DEFAULT CHARSET=utf8 ";
        }

        if (isset($_table->comment)) {
            $statement .= " COMMENT='" . $_table->comment . "'";
        }
        
        return $statement;
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
        
        //$existingTable = new Setup_Backend_Schema_Table($tableInfo);
        $existingTable = Setup_Backend_Schema_Table_Factory::factory('Mysql', $tableInfo);
       // get field informations
        $select = $this->_db->select()
            ->from('information_schema.COLUMNS')
            ->where($this->_db->quoteIdentifier('TABLE_NAME') . ' = ?', SQL_TABLE_PREFIX .  $_tableName);

        $stmt = $select->query();
        $tableColumns = $stmt->fetchAll();

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
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` ADD COLUMN " ;
        
        $statement .= $this->getFieldDeclarations($_declaration);
        
        if ($_position !== NULL) {
            if ($_position == 0) {
                $statement .= ' FIRST ';
            } else {
                $before = $this->execQuery('DESCRIBE `' . SQL_TABLE_PREFIX . $_tableName . '` ');
                $statement .= ' AFTER `' . $before[$_position]['Field'] . '`';
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
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` CHANGE COLUMN " ;
        
        if ($_oldName === NULL) {
            $oldName = $_declaration->name;
        } else {
            $oldName = $_oldName;
        }
        
        $statement .= " `" . $oldName .  "` " . $this->getFieldDeclarations($_declaration);
        $this->execQueryVoid($statement);    
    }
 
    /**
     * add a key to database table
     * 
     * @param string tableName 
     * @param Setup_Backend_Schema_Index_Abstract declaration
     */     
    public function addIndex($_tableName ,  Setup_Backend_Schema_Index_Abstract$_declaration)
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` ADD "
                    . $this->getIndexDeclarations($_declaration);
        $this->execQueryVoid($statement);    
    }

    /**
     * create the right mysql-statement-snippet for keys
     *
     * @param   Setup_Backend_Schema_Index_Abstract $_key
     * @param String | optional $_tableName [is not used in this Backend (MySQL)]
     * @return  string
     * @throws  Setup_Exception_NotFound
     */
    public function getIndexDeclarations(Setup_Backend_Schema_Index_Abstract $_key, $_tableName = '')
    {    
        $keys = array();

        $snippet = "  KEY `" . $_key->name . "`";
        if (!empty($_key->primary)) {
            $snippet = '  PRIMARY KEY ';
        } else if (!empty($_key->unique)) {
            $snippet = "  UNIQUE KEY `" . $_key->name . "`" ;
        }
        
        if ($_key->length !== NULL) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' .$_key->length);
        }
        
        foreach ($_key->field as $keyfield) {
            $key = '`' . (string)$keyfield . '`';
            if ($_key->length !== NULL) {
                $key .= ' (' . $_key->length . ')';
            }
            $keys[] = $key;
        }

        if (empty($keys)) {
            throw new Setup_Exception_NotFound('no keys for index found');
        }

        $snippet .= ' (' . implode(",", $keys) . ')';
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' . $snippet);
        
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
        $snippet = '  CONSTRAINT `' . SQL_TABLE_PREFIX .  $_key->name . '` FOREIGN KEY ';
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
}
