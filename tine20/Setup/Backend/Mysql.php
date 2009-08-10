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
     * takes the xml stream and creates a table
     *
     * @param object $_table xml stream
     */
    public function createTable(Setup_Backend_Schema_Table_Abstract  $_table)
    {
        $statement = $this->getCreateStatement($_table);
        $this->execQueryVoid($statement);
        #// auto shutup by cweiss: echo "<pre>$statement</pre><hr>";
    }
    
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
     * check's if a given table exists
     *
     * @param string $_tableSchema
     * @param string $_tableName
     * @return boolean return true if the table exists, otherwise false
     */
    public function tableExists($_tableName)
    {
        $select = $this->_db->select()
            ->from('information_schema.tables')
            ->where($this->_db->quoteIdentifier('TABLE_SCHEMA') . ' = ?', $this->_config->database->dbname)
            ->where($this->_db->quoteIdentifier('TABLE_NAME') . ' = ?',  SQL_TABLE_PREFIX . $_tableName);

        $stmt = $select->query();
        $table = $stmt->fetchObject();
        
        if ($table === false) {
            return false;
        }
        
        return true; 
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
     * checkTable
     * 
     * @param   Setup_Backend_Schema_Table_Abstract $_table
     * @throws  Setup_Exception
     */
    public function checkTable(Setup_Backend_Schema_Table_Abstract $_table)
    {
        $string = $this->getCreateStatement($_table);
        $dump = $this->execQuery('SHOW CREATE TABLE ' . SQL_TABLE_PREFIX . $_table->name);
        $compareString = preg_replace('/ AUTO_INCREMENT=\d*/', '', $dump[0]['Create Table']);
        
        if ($compareString != $string) {
            // auto shutup by cweiss: echo "XML<br/>" . $string;
            // auto shutup by cweiss: echo "<hr color=red>MYSQL<br/>";
            for ($i = 0 ; $i < (strlen($compareString)+1) ; $i++) {
                if ($compareString[$i] == $string[$i]) {
                    // auto shutup by cweiss: echo $compareString[$i];
                } else {
                    // auto shutup by cweiss: echo "<font color=red>" . $compareString[$i] . "</font>";
                }
            }
            throw new Setup_Exception("<h1>Failure</h1>");
        }
        
        /*
        foreach ($existentTable->fields as $existingFieldKey => $existingField) {
            
            foreach ($_table->fields as $spalte) {
                if ($spalte->name == $existingField->name) {
                
                    if (NULL != (array_diff($spalte->toArray(), $existingField->toArray()))) {
                        
                        print_r("Differences between database and newest xml declarations\n");
                        // auto shutup by cweiss: echo $_table->name . " database: ";
                       // var_dump($existingField);
                        var_dump($existingField->toArray());
                        // auto shutup by cweiss: echo "XML field: ";
                       // var_dump($spalte);
                        var_dump($spalte->toArray());
                        
                    }
                }
            }
        }
        */
    }
    
    /**
     * removes table from database
     * 
     * @param string tableName
     */
#    public function dropTable($_tableName)
#    {
#        $statement = "DROP TABLE " . $this->_db->quoteTableAs(SQL_TABLE_PREFIX . $_tableName);
#        $this->execQueryVoid($statement);
#    }
    
    /**
     * renames table in database
     * 
     * @param string tableName
     */
    public function renameTable($_tableName, $_newName)
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` RENAME TO `" . SQL_TABLE_PREFIX . $_newName . "` ;";
        $this->execQueryVoid($statement);
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
        
        if ($_position != NULL) {
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
     * drop column/field in database table
     * 
     * @param string tableName
     * @param string column/field name 
     */    
    public function dropCol($_tableName, $_colName)
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` DROP COLUMN `" . $_colName . "`";
        $this->execQueryVoid($statement);    
    }


    /**
     * add a foreign key to database table
     * 
     * @param string tableName
     * @param Setup_Backend_Schema_Index_Abstract declaration
     */       
    public function addForeignKey($_tableName, Setup_Backend_Schema_Index_Abstract $_declaration)
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` ADD " 
                    . $this->getForeignKeyDeclarations($_declaration);
        $this->execQueryVoid($statement);    
    }

    /**
     * removes a foreign key from database table
     * 
     * @param string tableName
     * @param string foreign key name
     */     
    public function dropForeignKey($_tableName, $_name)
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` DROP FOREIGN KEY `" . SQL_TABLE_PREFIX .  $_name . "`" ;
        $this->execQueryVoid($statement);    
    }
    
    /**
     * removes a primary key from database table
     * 
     * @param string tableName (there is just one primary key...)
     */         
    public function dropPrimaryKey($_tableName)
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` DROP PRIMARY KEY " ;
        $this->execQueryVoid($statement);    
    }
    
    /**
     * add a primary key to database table
     * 
     * @param string tableName 
     * @param Setup_Backend_Schema_Index_Abstract declaration
     */         
    public function addPrimaryKey($_tableName, Setup_Backend_Schema_Index_Abstract $_declaration)
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` ADD "
                    . $this->getIndexDeclarations($_declaration);
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
     * removes a key from database table
     * 
     * @param string tableName 
     * @param string key name
     */    
    public function dropIndex($_tableName, $_indexName)
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` DROP INDEX `"  . $_indexName. "`" ;
        $this->execQueryVoid($statement);    
    }
    
    /**
     * create the right mysql-statement-snippet for columns/fields
     *
     * @param Setup_Backend_Schema_Field_Abstract field / column
     * @return string
     */
    public function getFieldDeclarations(Setup_Backend_Schema_Field_Abstract $_field)
    {
        $buffer[] = '  `' . $_field->name . '`';

        switch ($_field->type) {
            case 'varchar': 
                if ($_field->length !== NULL) {
                    $buffer[] = 'varchar(' . $_field->length . ')';
                } else {
                    $buffer[] = 'varchar(255)';
                }
                break;
            
            case 'integer':
                if ($_field->length !== NULL) {
                    if ($_field->length > 19) {
                        $buffer[] = 'bigint(' . $_field->length . ')';
                    } else if ($_field->length < 5){
                        $buffer[] = 'tinyint(4)';
                    } else {
                        $buffer[] = 'int(' . $_field->length . ')';
                    }
                } else {
                    $buffer[] = 'int(11)';
                }
                break;
            
            case 'clob':
                $buffer[] = 'text';
                break;
            
            case 'blob':
                $buffer[] = 'longblob';
                break;
            
            case 'enum':
                foreach ($_field->value as $value) {
                    $values[] = $value;
                }
                $buffer[] = "enum('" . implode("','", $values) . "')";
                break;
            
            case 'datetime':
                $buffer[] = 'datetime';
                break;
            
            case 'double':
                $buffer[] = 'double';
                break;
            
            case 'float':
                $buffer[] = 'float';
                break;
            
            case 'decimal':
                $buffer[] = "decimal(" . $_field->value . ")";
                break;
                
            default:
                $buffer[] = $_field->type;
        }

        if (isset($_field->unsigned) && $_field->unsigned === true) {
            $buffer[] = 'unsigned';
        }

        if ($_field->notnull === true) {
            $buffer[] = 'NOT NULL';
        }

        if (isset($_field->default)) {
            if($_field->default === NULL) {
                $buffer[] = "default NULL" ;
            } else {
                $buffer[] = $this->_db->quoteInto("default ?", $_field->default) ;
            }
        }    

        if (isset($_field->autoincrement) && $_field->autoincrement === true) {
            $buffer[] = 'auto_increment';
        }
        
        if (isset($_field->comment)) {
            $buffer[] = "COMMENT '" .  $_field->comment . "'";
        }

        $definition = implode(' ', $buffer);

        return $definition;
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
        
        foreach ($_key->field as $keyfield) {
            $key = '`' . (string)$keyfield . '`';
            if (!empty($keyfield->length)) {
                $key .= ' (' . $keyfield->length . ')';
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
