<?php

/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @copyright   Copyright (c); 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de);
 * @version     $Id: Abstract.php 1735 2008-04-05 20:08:37Z lkneschke $
 *
 */

/**
 * interface for backend class
 * 
 * @package     Setup
 */
abstract class Setup_Backend_Abstract implements Setup_Backend_Interface
{
    /**
     * Maximum length of table-, index-, contraint- and field names.
     * 
     * @var int
     */
    const MAX_NAME_LENGTH = 30;
    
    const INTEGER_DEFAULT_LENGTH = 11;

    /**
     * Define how database agnostic data types get mapped to database sepcific data types
     * 
     * @var array
     */
    protected $_typeMappings = array();
 
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db = NULL;
    
    /**
     * config object
     *
     * @var Zend_Config
     */
    protected $_config = NULL;
    
    /**
     * Return the mapping from the given database-agnostic data {@param $_type} to the
     * corresponding database specific data type
     * 
     * @param String $_type
     * @return array | null
     */
    public function getTypeMapping($_type)
    {
        if (array_key_exists($_type, $this->_typeMappings)) {
            return $this->_typeMappings[$_type];
        }
        return null;
    }
    
    /**
     * constructor
     *
     */
    public function __construct()
    {
        $this->_config = Tinebase_Core::getConfig();
        $this->_db = Tinebase_Core::getDb();
    }
    
    /**
     * checks if application is installed at all
     *
     * @param unknown_type $_application
     * @return boolean
     */
    public function applicationExists($_application)
    {
        if ($this->tableExists('applications')) {
            if ($this->applicationVersionQuery($_application) != false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * check's a given database table version 
     *
     * @param string $_tableName
     * @return boolean return string "version" if the table exists, otherwise false
     */
    public function tableVersionQuery($_tableName)
    {
        $select = $this->_db->select()
            ->from( SQL_TABLE_PREFIX . 'application_tables')
            ->where($this->_db->quoteIdentifier('name') . ' = ?', SQL_TABLE_PREFIX . $_tableName);

        $stmt = $select->query();
        $version = $stmt->fetchAll();
        
        return $version[0]['version'];
    }
        
    /**
     * check's a given application version
     *
     * @param string $_application
     * @return boolean return string "version" if the table exists, otherwise false
     */
    public function applicationVersionQuery($_application)
    {
        $select = $this->_db->select()
            ->from( SQL_TABLE_PREFIX . 'applications')
            ->where($this->_db->quoteIdentifier('name') . ' = ?', $_application);

        $stmt = $select->query();
        $version = $stmt->fetchAll();
        
        if (empty($version)) {
            return false;
        } else {
            return $version[0]['version'];
        }
    }
    
    /**
     * execute insert statement for default values (records)
     * handles some special fields, which can't contain static values
     * 
     * @param   SimpleXMLElement $_record
     * @throws  Setup_Exception
     */
    public function execInsertStatement(SimpleXMLElement $_record)
    {
        $data = array();
        
        foreach ($_record->field as $field) {
            if (isset($field->value['special'])) {
                switch(strtolower($field->value['special'])) {
                    case 'now':
                        $value = Zend_Date::now()->get(Tinebase_Record_Abstract::ISO8601LONG);
                        break;
                    
                    case 'account_id':
                        break;
                    
                    case 'application_id':
                        $application = Tinebase_Application::getInstance()->getApplicationByName((string) $field->value);
                        $value = $application->id;
                        break;
                    
                    case 'uid':
                        $value = Tinebase_Record_Abstract::generateUID();
                        break;
                        
                    default:
                        throw new Setup_Exception('Unsupported special type ' . strtolower($field->value['special']));                    
                    }
            } else {
                $value = $field->value;
            }
            // buffer for insert statement
            $data[(string)$field->name] = (string)$value;
        }
        
        #$table = new Tinebase_Db_Table(array(
        #   'name' => SQL_TABLE_PREFIX . $_record->table->name
        #));

        #// final insert process
        #$table->insert($data);
        
        #var_dump($data);
        #var_dump(SQL_TABLE_PREFIX . $_record->table->name);
        $this->_db->insert(SQL_TABLE_PREFIX . $_record->table->name, $data);
    }

    /**
     * execute statement without return values
     * 
     * @param string statement
     */    
    public function execQueryVoid($_statement, $bind = array())
    {
        $stmt = $this->_db->query($_statement, $bind);
    }
    
    /**
     * execute statement  return values
     * 
     * @param string statement
     * @return stdClass object
     */       
    public function execQuery($_statement, $bind = array())
    {
        $stmt = $this->_db->query($_statement, $bind);
        
        return $stmt->fetchAll();
    }
    
    /**
     * checks if a given table exists
     *
     * @param string $_tableSchema
     * @param string $_tableName
     * @return boolean return true if the table exists, otherwise false
     */
    public function tableExists($_tableName)
    {
        $tableName = SQL_TABLE_PREFIX . $_tableName;
        try {
            $tableInfo = $this->_db->describeTable($tableName);
        } catch (Zend_Db_Statement_Exception $e) {
            $tableInfo = null;
        }
        return !empty($tableInfo);
    }
    
    /**
     * takes the xml stream and creates a table
     *
     * @param object $_table xml stream
     */
    public function createTable(Setup_Backend_Schema_Table_Abstract  $_table)
    {
        $statement = $this->getCreateStatement($_table);
        $this->execQueryVoid($statement);
    }
    
    /**
     * removes table from database
     * 
     * @param string tableName
     */
    public function dropTable($_tableName)
    {
    	Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Dropping table ' . $_tableName);
        $statement = "DROP TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName);
        $this->execQueryVoid($statement);
    }
    
    /**
     * renames table in database
     * 
     * @param string tableName
     */
    public function renameTable($_tableName, $_newName)
    {
        $statement = 'ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . ' RENAME TO ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_newName);
        $this->execQueryVoid($statement);
    }
    
    /**
     * checks if a given column {@param $_columnName} exists in table {@param $_tableName}.
     *
     * @param string $_columnName
     * @param string $_tableName
     * @return boolean
     */
    public function columnExists($_columnName, $_tableName)
    {
        $tableName = SQL_TABLE_PREFIX . $_tableName;
        $tableInfo = $this->_db->describeTable($tableName);
        return array_key_exists($_columnName, $tableInfo);
    }
    
    /**
     * drop column/field in database table
     * 
     * @param string tableName
     * @param string column/field name 
     */    
    public function dropCol($_tableName, $_colName)
    {
        $statement = 'ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . ' DROP COLUMN ' . $this->_db->quoteIdentifier($_colName);
        $this->execQueryVoid($statement);    
    }
    
    /**
     * add a primary key to database table
     * 
     * Delegates to {@see addPrimaryKey()}
     * 
     * @param string tableName 
     * @param Setup_Backend_Schema_Index_Abstract declaration
     */         
    public function addPrimaryKey($_tableName, Setup_Backend_Schema_Index_Abstract $_declaration)
    {
        $this->addIndex($_tableName, $_declaration);
    }
    
    /**
     * removes a primary key from database table
     * 
     * @param string tableName (there is just one primary key...)
     */         
    public function dropPrimaryKey($_tableName)
    {
        $statement = "ALTER TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . " DROP PRIMARY KEY " ;
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
        $statement = "ALTER TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . " ADD " 
                    . $this->getForeignKeyDeclarations($_declaration, $_tableName);
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
        $statement = "ALTER TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) 
            . " DROP FOREIGN KEY `" . $_name . "`" ;
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
        $statement = "ALTER TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . " DROP INDEX `"  . $_indexName. "`" ;
        $this->execQueryVoid($statement);    
    }

    /**
     * create the right mysql-statement-snippet for columns/fields
     *
     * @param Setup_Backend_Schema_Field_Abstract field / column
     * @param String | optional $_tableName [Not used in this backend (MySQL)]
     * @return string
     */
    public function getFieldDeclarations(Setup_Backend_Schema_Field_Abstract $_field, $_tableName = '')
    {
        $buffer = $this->_getFieldDeclarations($_field, $_tableName);

        $definition = implode(' ', $buffer);

        return $definition;
    }
    
    /**
     * Concrete implementation of 
     * @see tine20/Setup/Backend/Setup_Backend_Interface#checkTable($_table)
     */
    public function checkTable(Setup_Backend_Schema_Table_Abstract $_table)
    {
        $dbTable = $this->getExistingSchema($_table->name);
        return $dbTable->equals($_table);
    }
    
    /**
     * create the right mysql-statement-snippet for columns/fields
     *
     * @param Setup_Backend_Schema_Field_Abstract field / column
     * @param String | optional $_tableName [Not used in this backend (MySQL)]
     * @return string
     */
    protected function _getFieldDeclarations(Setup_Backend_Schema_Field_Abstract $_field, $_tableName = '')
    {
        $buffer = array();
        $buffer[] = '  ' . $this->_db->quoteIdentifier($_field->name);

        $buffer = $this->_addDeclarationFieldType($buffer, $_field, $_tableName);
        $buffer = $this->_addDeclarationUnsigned($buffer, $_field);
        $buffer = $this->_addDeclarationDefaultValue($buffer, $_field);
        $buffer = $this->_addDeclarationNotNull($buffer, $_field);
        $buffer = $this->_addDeclarationAutoincrement($buffer, $_field);
        $buffer = $this->_addDeclarationComment($buffer, $_field);
        
        return $buffer;
    }
    
    protected function _addDeclarationFieldType(array $_buffer, Setup_Backend_Schema_Field_Abstract $_field, $_tableName = '')
    {
        $typeMapping = $this->getTypeMapping($_field->type);
        if ($typeMapping) {
            $fieldType = $typeMapping['defaultType'];
            if (isset($typeMapping['declarationMethod'])) {
                $fieldBuffer = call_user_func(array($this, $typeMapping['declarationMethod']), $_field, $_tableName);
                $_buffer = array_merge($_buffer, $fieldBuffer); 
            } else {
                if ($_field->length !== NULL) {
                    if (isset($typeMapping['lengthTypes']) && is_array($typeMapping['lengthTypes'])) {
                        foreach ($typeMapping['lengthTypes'] as $maxLength => $type) {
                            if ($_field->length <= $maxLength) {
                                $fieldType = $type;
                                $scale  = '';
                                if (isset($_field->scale)) {
                                    $scale = ',' . $_field->scale;
                                } elseif(isset($typeMapping['defaultScale'])) {
                                    $scale = ',' . $typeMapping['defaultScale'];
                                }
                                 
                                $options = "({$_field->length}{$scale})";
                                break;
                            }
                        }
                        if (!isset($options)) {
                            throw new Setup_Backend_Exception_InvalidSchema("Could not get field declaration for field {$_field->name}: The given length of {$_field->length} is not supported by field type {$_field->type}");
                        }
                    } else {
                        throw new Setup_Backend_Exception_InvalidSchema("Could not get field declaration for field {$_field->name}: Length option was specified but is not supported by field type {$_field->type}");
                    }
                } else {
                    $options = '';
                    if (isset($_field->value)) {
                        foreach ($_field->value as $value) {
                            $values[] = $value;
                        }
                        $options = "('" . implode("','", $values) . "')";
                    } elseif(isset($typeMapping['defaultLength'])) {
                        $scale = isset($typeMapping['defaultScale']) ? ',' . $typeMapping['defaultScale'] : '';
                        $options = "({$typeMapping['defaultLength']}{$scale})";
                    }
                }
    
                $_buffer[] = $fieldType . $options;
            }
        } else {
            throw new Setup_Backend_Exception_InvalidSchema("Could not get field declaration for field {$_field->name}: The given field type {$_field->type} is not supported");
        }
        return $_buffer;
    }
    
    protected function _addDeclarationDefaultValue(array $_buffer, Setup_Backend_Schema_Field_Abstract $_field)
    {
        if (isset($_field->default)) {
            $_buffer[] = $this->_db->quoteInto("DEFAULT ?", $_field->default) ;
        }
        return $_buffer;
    }
    
    protected function _addDeclarationNotNull(array $_buffer, Setup_Backend_Schema_Field_Abstract $_field)
    {
        if ($_field->notnull === true) {
            $_buffer[] = 'NOT NULL';
        }
        return $_buffer;
    }
    
    protected function _addDeclarationUnsigned(array $_buffer, Setup_Backend_Schema_Field_Abstract $_field)
    {
        if (isset($_field->unsigned) && $_field->unsigned === true) {
            $_buffer[] = 'unsigned';
        }
        return $_buffer;
    }

    protected function _addDeclarationAutoincrement(array $_buffer, Setup_Backend_Schema_Field_Abstract $_field)
    {
        if (isset($_field->autoincrement) && $_field->autoincrement === true) {
            $_buffer[] = 'auto_increment';
        }
        return $_buffer;
    }

    protected function _addDeclarationComment(array $_buffer, Setup_Backend_Schema_Field_Abstract $_field)
    {
        if (isset($_field->comment)) {
            $_buffer[] = "COMMENT '" .  $_field->comment . "'";
        }
        return $_buffer;
    }
    
    protected function _sanititzeName($_name)
    {
        if (strlen($_name) > Setup_Backend_Abstract::MAX_NAME_LENGTH) {
            $_name = substr(md5($_name), 0 , Setup_Backend_Abstract::MAX_NAME_LENGTH);
        }
        return $_name;
    }
    

}