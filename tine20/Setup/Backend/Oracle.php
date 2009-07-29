<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Mysql.php 2335 2008-05-07 14:07:23Z metagreiling $
 *
 */

/**
 * setup backend class for MySQL 5.0 +
 *
 * @package     Setup
 */
class Setup_Backend_Oracle extends Setup_Backend_Abstract
{
    protected $_table ='';
   
    protected $_autoincrementID = '';
    
    protected static $_sequence_postfix = '_seq';
    
    /**
     * takes the xml stream and creates a table
     *
     * @param object $_table xml stream
     */
    public function createTable(Setup_Backend_Schema_Table_Abstract $_table)
    {
        $this->_table = $_table->name;
        $statement = $this->getCreateStatement($_table);
        // auto shutup by cweiss: echo "<hr color=green>";

     //   // auto shutup by cweiss: echo $statement;
        
        $this->execQueryVoid($statement);
        
        if (!empty($this->_autoincrementId)) {
            $statement = $this->getIncrementSequence($_table->name);
            $this->execQueryVoid($statement);
            $statement = $this->getIncrementTrigger($_table->name);
            $this->execQueryVoid($statement);
            
     //       // auto shutup by cweiss: echo $statement;
            unset($this->_autoincrementId);
        }
        // auto shutup by cweiss: echo "<hr color=red>";
    }
    
    public function getIncrementSequence($_tableName) 
    { 
        $statement = 'CREATE SEQUENCE "' . SQL_TABLE_PREFIX . substr($_tableName, 0, 20) . self::$_sequence_postfix . '" 
            MINVALUE 1
            MAXVALUE 999999999999999999999999999 
            INCREMENT BY 1
            START WITH 1 
            NOCACHE  
            NOORDER  
            NOCYCLE
        ';
            
        return $statement;
    }
                
    public function getIncrementTrigger($_tableName) 
    {
        $statement = 'CREATE TRIGGER "' . SQL_TABLE_PREFIX .  substr($_tableName, 0, 20) . '_tri"
            BEFORE INSERT ON "' .  SQL_TABLE_PREFIX . $_tableName . '"
            FOR EACH ROW
            BEGIN
            SELECT "' . SQL_TABLE_PREFIX .  substr($_tableName, 0, 20) . '_seq".NEXTVAL INTO :NEW."' . $this->_autoincrementId .'" FROM DUAL;
            END;
        ';
    
        return $statement;
    }
    
    public function getCreateStatement(Setup_Backend_Schema_Table_Abstract $_table)
    {
        $statement = 'CREATE TABLE "' . SQL_TABLE_PREFIX . $_table->name . "\" (\n";
        $statementSnippets = array();
     
        foreach ($_table->fields as $field) {
           $statementSnippets[] = $this->getFieldDeclarations($field);
        }

        foreach ($_table->indices as $index) {    
            if ($index->foreign) {
               $statementSnippets[] = $this->getForeignKeyDeclarations($index);
            } else if ($index->primary || $index->unique) {
               $statementSnippets[] = $this->getIndexDeclarations($index);
            }
        }
        
        $statement .= implode(",\n", $statementSnippets) . "\n)";
        
        // auto shutup by cweiss: echo "<pre>$statement</pre>";
        
        return $statement;
    }
    
    /**
     * check's if a given table exists
     *
     * @param string $_tableName
     * @return boolean return true if the table exists, otherwise false
     */
    public function tableExists($_tableName)
    {
        $tableName = SQL_TABLE_PREFIX . $_tableName;
        try {
            $table = $this->_db->fetchOne('SELECT table_name FROM ALL_TABLES WHERE table_name=?', array($tableName));
            return $table === $tableName;
        } catch (Zend_Db_Exception $e){
            Tinebase_Core::getLogger()->warn("An exception was thrown while checking if table $tableName exists: " . $e->getMessage() . "; returnbing false");
        }
        return false; 
    }
    
    /**
     * check's if a given column {@param $_columnName} exists in table {@param $_tableName}.
     *
     * @param string $_columnName
     * @param string $_tableName
     * @return boolean return true if the table exists, otherwise false
     */
    public function columnExists($_columnName, $_tableName)
    {
        $tableName = SQL_TABLE_PREFIX . $_tableName;
        try {
            $column = $this->_db->fetchOne('SELECT column_name FROM ALL_TAB_COLUMNS WHERE TABLE_NAME=? AND COLUMN_NAME=?', array($tableName, $_columnName));
            return $column === $_columnName;
        } catch (Zend_Db_Exception $e){
            Tinebase_Core::getLogger()->warn("An exception was thrown while checking if column $_columnName exists $tableName: " . $e->getMessage() . "; returnbing false");
        }
        return false; 
    }
    
    public function getExistingSchema($_tableName)
    {
        $tableInfo = $this->_getTableInfo($_tableName);
        $existingTable = Setup_Backend_Schema_Table_Factory::factory('Oracle', $tableInfo);
            
        return $existingTable;
    }
    
    protected function _getTableInfo($_tableName)
    {
        $tableName = SQL_TABLE_PREFIX . $_tableName;
        $tableInfo = $this->_db->describeTable($tableName);
        foreach ($tableInfo as $field) {
            //@todo aggregate more information liek auto_increment, indices, constraints etc. that have not been returned by describeTable
        }
        return $tableInfo;
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
    public function renameTable($_tableName, $_newName )
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` RENAME TO `" . SQL_TABLE_PREFIX . $_newName . "` ;";
        $this->execQueryVoid($statement);
    }
    
    /**
     * add column/field to database table
     * 
     * @param string tableName
     * @param Setup_Backend_Schema_Field declaration
     * @param int position of future column
     */    
    public function addCol($_tableName, Setup_Backend_Schema_Field_Abstract $_declaration, $_position = NULL)
    {
        $statement = "ALTER TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . " ADD (" ;
        
        $statement .= $this->getFieldDeclarations($_declaration);
        
        $statement .= ")";
        
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
     * @param Setup_Backend_Schema_Field declaration
     * @param string old column/field name 
     */    
    public function alterCol($_tableName, Setup_Backend_Schema_Field_Abstract $_declaration, $_oldName = NULL)
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` CHANGE COLUMN " ;
        $oldName = $_oldName ;
        
        if ($_oldName == NULL) {
            $oldName = SQL_TABLE_PREFIX . $_declaration->name;
        }
        
        $statement .= " `" . $oldName .  "` " . $this->getFieldDeclarations($_declaration);
        $this->execQueryVoid($statement);    
    }
    
    /**
     * removes table from database
     * 
     * @param string tableName
     */
    public function dropTable($_tableName)
    {
        parent::dropTable($_tableName);
        try {
    	    $statement = "DROP SEQUENCE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName . self::$_sequence_postfix);
    	    $this->execQueryVoid($statement);
        } catch (Zend_Db_Statement_Exception $e) {
        	Tinebase_Core::getLogger()->debug("An exception was thrown while dropping sequence for table {$_tableName}: " . $e->getMessage() . "; This might be OK if the table had no sequencer.");
        }
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
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` DROP FOREIGN KEY `" . $_name . "`" ;
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
     * @param Setup_Backend_Schema_Field field / column
     * @todo how gets unsigned handled
     * @return string
     */
    public function getFieldDeclarations(Setup_Backend_Schema_Field_Abstract $_field)
    {
        $buffer[] = '  "' . $_field->name . '"';

        switch ($_field->type) {
            case 'varchar': 
                if ($_field->length !== NULL) {
                    $buffer[] = 'VARCHAR2(' . $_field->length . ')';
                } else {
                    $buffer[] = 'VARCHAR2(255)';
                }
                break;
            
            case 'integer':
                if ($_field->length !== NULL) {
                    if ($_field->length < 5){
                        $buffer[] = 'NUMBER(1,0)';
                    } else {
                        $buffer[] = 'NUMBER(' . $_field->length . ',0)';
                    }
                } else {
                    $buffer[] = 'NUMBER(11,0)';
                }                
                break;
                
            case 'clob':
                $buffer[] = 'CLOB';
                break;
                
            case 'longblob':
            case 'blob':
                $buffer[] = 'BLOB';
                break;
            
            case 'enum':
                $length = 0;
                foreach ($_field->value as $value) {
                    $values[] = $value;
                    $tempLength = strlen($value);
                    if ($tempLength > $length) {
                        $length = $tempLength;
                    }
                }
                
                $additional = ''; 
                if ($_field->notnull === true) {
                    $additional .= ' NOT NULL ';
                }
                if (isset($_field->default)) {
                    if($_field->default === NULL) {
                        $buffer[] = "DEFAULT NULL" ;
                    } else {
                        $buffer[] = $this->_db->quoteInto("DEFAULT ?", $_field->default) ;
                    }
                }    
                
                $buffer[] = 'VARCHAR2(' . $length . ')' . $additional . ', CONSTRAINT "cons_' . substr($this->_table, 0, 10) . "_" . substr($_field->name, 0, 9) . '_enum" CHECK ("'. $_field->name . "\" IN ('" . implode("','", $values) . "'))";
                break;
            
            case 'datetime':
                $buffer[] = 'VARCHAR2(25)';
                break;
            
            case 'double':
                $buffer[] = 'BINARY_DOUBLE';
                break;
            
            case 'float':
                $buffer[] = 'BINARY_FLOAT';
                break;
            
            case 'decimal':
                $buffer[] = "NUMBER(" . $_field->value . ")";
                break;
                
            case 'text':
                $buffer[] = 'BLOB';
                break;
                
            default:
                $buffer[] = $_field->type;
        }
        
        if ($_field->type != 'enum') {
            if (isset($_field->default)) {
                if($_field->default === NULL) {
                    $buffer[] = "DEFAULT NULL" ;
                } else {
                    $buffer[] = $this->_db->quoteInto("DEFAULT ?", $_field->default) ;
                }
            }    

            if ($_field->notnull === true) {
                $buffer[] = 'NOT NULL';
            }
        }
        
        if (isset($_field->autoincrement)) {
            $this->_autoincrementId = $_field->name;
        }
       
        $definition = implode(' ', $buffer);
        
        return $definition;
    }

    /**
     * create the right mysql-statement-snippet for keys
     *
     * @param   Setup_Backend_Schema_Index_Abstract key
     * @return  string
     * @throws  Setup_Exception_NotFound
     */
    public function getIndexDeclarations(Setup_Backend_Schema_Index_Abstract $_key)
    {    
        $keys = array();
        if (!empty($_key->primary)) {
            $snippet = "CONSTRAINT \"pk_" . $this->_table . "\" PRIMARY KEY";        
        
        } else if (!empty($_key->unique)) {
            $snippet = "CONSTRAINT \"uni_" . substr($this->_table, 0, 13) . "_" . substr($_key->name, 0, 12) . "\" UNIQUE" ;
        }

        else {
            $snippet = "CONSTRAINT \"idx_" . $this->_table . "_" . $_key->name . "\" INDEX ";
        }
        
        foreach ($_key->field as $keyfield) {
            $key = '"' . (string)$keyfield . '"';
            if (!empty($keyfield->length)) {
                $key .= ' (' . $keyfield->length . ')';
            }
            $keys[] = $key;
        }

        if (empty($keys)) {
            throw new Setup_Exception_NotFound('No keys for index found.');
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
        $snippet = '  CONSTRAINT "fk_' . substr($this->_table, 0, 13) . "_" . substr($_key->field, 0, 13). '" FOREIGN KEY ';
        $snippet .= '("' . $_key->field . '") REFERENCES "' . SQL_TABLE_PREFIX
            . $_key->referenceTable  
            . '" ("' . $_key->referenceField . '")';

        if (!empty($_key->referenceOnDelete)) {
            $snippet .= " ON DELETE " . strtoupper($_key->referenceOnDelete);
        }
        if (!empty($_key->referenceOnUpdate)) {
            $snippet .= " ON UPDATE " . strtoupper($_key->referenceOnUpdate);
        }
        
        return $snippet;
    }
}