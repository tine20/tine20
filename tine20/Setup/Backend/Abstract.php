<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Setup
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @copyright   Copyright (c) 2007-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * interface for backend class
 * 
 * @package     Setup
 * @subpackage  Backend
 */
abstract class Setup_Backend_Abstract implements Setup_Backend_Interface
{
    /**
     * Maximum length of table-, index-, contraint- and field names.
     * 
     * @var integer
     */
    const MAX_NAME_LENGTH = 30;
    
    /**
     * default length of integer fields
     * 
     * @var integer
     */
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
        if ((isset($this->_typeMappings[$_type]) || array_key_exists($_type, $this->_typeMappings))) {
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
     * get db adapter
     * 
     * @return Zend_Db_Adapter_Abstract
     */
    public function getDb()
    {
        return $this->_db;
    }
    
    /**
     * checks if application is installed at all
     *
     * @param mixed $_application
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
     * @return boolean|string "version" if the table exists, otherwise false
     */
    public function tableVersionQuery($_tableName)
    {
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'application_tables')
            ->where($this->_db->quoteIdentifier('name') . ' = ?', SQL_TABLE_PREFIX . $_tableName)
            ->orWhere($this->_db->quoteIdentifier('name') . ' = ?', $_tableName);

        $stmt = $select->query();
        $version = $stmt->fetchAll();
        
        return (! empty($version)) ? $version[0]['version'] : FALSE;
    }
    
    /**
     * truncate table in database
     * 
     * @param string $_tableName
     */
    public function truncateTable($_tableName)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Truncate table ' . $_tableName);
        $statement = "TRUNCATE TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName);
        $this->execQueryVoid($statement);
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
                        $value = Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG);
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
        
        $this->_db->insert(SQL_TABLE_PREFIX . $_record->table->name, $data);
    }

    /**
     * execute statement without return values
     * 
     * @param string $_statement
     * @param array $bind
     */    
    public function execQueryVoid($_statement, $bind = array())
    {
        if (!empty($_statement)) {
            $this->_db->query($_statement, $bind);
        }
    }
    
    /**
     * execute statement  return values
     * 
     * @param string $_statement
     * @param array $bind
     * @return array
     */
    public function execQuery($_statement, $bind = array())
    {
        $stmt = $this->_db->query($_statement, $bind);
        
        return $stmt->fetchAll();
    }
    
    /**
     * checks if a given table exists
     *
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
     * @param  Setup_Backend_Schema_Table_Abstract $_table xml stream
     * @param  string $_appName if appname and tablename are given, we create an entry in the application table
     * @param  string $_tableName
     * @return bool return true on success, false in case of graceful failure, due to missing requirements for example
     */
    public function createTable(Setup_Backend_Schema_Table_Abstract $_table, $_appName = NULL, $_tableName = NULL)
    {
        if (false === $this->_checkTableRequirements($_table)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Do not create table ' . $_table->name . ': Requirement missing.');
            return false;
        }

        $statement = $this->getCreateStatement($_table);
        $this->execQueryVoid($statement);
        
        if ($_appName !== NULL && $_tableName !== NULL) {
            Tinebase_Application::getInstance()->addApplicationTable(
                Tinebase_Application::getInstance()->getApplicationByName($_appName), 
                $_tableName, 
                1
            );
        }

        return true;
    }

    /**
     * checks the requirements whether to install this table or not
     *
     * @param Setup_Backend_Schema_Table_Abstract $_table
     * @return bool return whether the requirements to install this table are met or not
     */
    protected function _checkTableRequirements(Setup_Backend_Schema_Table_Abstract $_table)
    {
        foreach($_table->requirements as $requirement) {
            if (false === $this->supports($requirement)) {
                return false;
            }
        }
        return true;
    }

    /**
     * checks whether this backend supports a specific requirement or not
     *
     * @param $requirement
     * @return bool
     */
    public function supports($requirement)
    {
        return false;
    }
    
    /**
     * removes table from database (and from application table if app id or name is given
     * 
     * @param string $_tableName
     * @param ?string $_applicationId
     */
    public function dropTable($_tableName, $_applicationId = 'Tinebase')
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' Dropping table ' . $_tableName);
        $statement = "DROP TABLE IF EXISTS " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName);
        $this->execQueryVoid($statement);
        
        if ($_applicationId !== NULL) {
            Tinebase_Application::getInstance()->removeApplicationTable($_applicationId, $_tableName);
        }
    }
    
    /**
     * renames table in database
     * 
     * @param string $_tableName
     * @param string $_newName
     */
    public function renameTable($_tableName, $_newName)
    {
        $statement = 'ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName)
            . ' RENAME TO ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_newName);
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
        // read description from database
        $columns = $this->_db->describeTable(SQL_TABLE_PREFIX . $_tableName);
        return (isset($columns[$_columnName]) || array_key_exists($_columnName, $columns));
    }
    
    /**
     * drop column/field in database table
     * 
     * @param string $_tableName
     * @param string $_colName column/field name
     */    
    public function dropCol($_tableName, $_colName)
    {
        $this->execQueryVoid($this->addDropCol(null, $_tableName, $_colName));
    }

    /**
     * drop column/field in database table
     *
     * @param string $_query
     * @param string $_tableName
     * @param string $_colName column/field name
     * @return string
     */
    public function addDropCol($_query, $_tableName, $_colName)
    {
        if (empty($_query)) {
            $_query = 'ALTER TABLE ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName);
        } else {
            $_query .= ',';
        }

        $_query .= ' DROP COLUMN ' . $this->_db->quoteIdentifier($_colName);

        return $_query;
    }
    
    /**
     * add a primary key to database table
     * 
     * Delegates to {@see addPrimaryKey()}
     * 
     * @param string $_tableName
     * @param Setup_Backend_Schema_Index_Abstract $_declaration
     */
    public function addPrimaryKey($_tableName, Setup_Backend_Schema_Index_Abstract $_declaration)
    {
        $this->addIndex($_tableName, $_declaration);
    }
    
    /**
     * removes a primary key from database table
     * 
     * @param string $_tableName (there is just one primary key...)
     */
    public function dropPrimaryKey($_tableName)
    {
        $statement = "ALTER TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . " DROP PRIMARY KEY " ;
        $this->execQueryVoid($statement);
    }

    /**
     * add a foreign key to database table
     * 
     * @param string $_tableName
     * @param Setup_Backend_Schema_Index_Abstract $_declaration
     */
    public function addForeignKey($_tableName, Setup_Backend_Schema_Index_Abstract $_declaration)
    {
        $statement = "ALTER TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . " ADD " 
                    . $this->getForeignKeyDeclarations($_declaration);
        $this->execQueryVoid($statement);
    }
    
    /**
     * removes a foreign key from database table
     * 
     * @param string $_tableName
     * @param string $_name key name
     */
    public function dropForeignKey($_tableName, $_name)
    {
        try {
            $this->_dropForeignKey($_tableName, SQL_TABLE_PREFIX . $_name);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // try it again without table prefix
            try {
                $this->_dropForeignKey($_tableName, $_name);
            } catch (Zend_Db_Statement_Exception $zdse) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' ' . $zdse);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' At first remove constraint, then remove key ...');
                
                $constraint = str_replace(array(
                    '::',
                    '--'
                ), '??', $_name);
                try {
                    $this->_dropForeignKey($_tableName, SQL_TABLE_PREFIX . $constraint);
                    $this->_dropForeignKey($_tableName, SQL_TABLE_PREFIX . $_name, FALSE);
                } catch (Zend_Db_Statement_Exception $zdse) {
                    // do it again without prefix
                    $this->_dropForeignKey($_tableName, $constraint);
                    $this->_dropForeignKey($_tableName, $_name, FALSE);
                }
            }
        }
    }
    
    /**
     * helper function for removing (foreign) keys
     * 
     * @param string $tableName
     * @param string $keyName
     * @param boolean $foreign
     */
    protected function _dropForeignKey($tableName, $keyName, $foreign = TRUE)
    {
        $statement = "ALTER TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $tableName) 
            . " DROP" . ($foreign ? ' FOREIGN' : '') . " KEY `" . $keyName . "`" ;
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' ' . $statement);
        $this->execQueryVoid($statement);
    }
    
    /**
     * removes a key from database table
     * 
     * @param string $_tableName
     * @param string $_indexName name
     */
    public function dropIndex($_tableName, $_indexName)
    {
        $statement = "ALTER TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . " DROP INDEX " . $this->_db->quoteIdentifier($_indexName);
        try {
            $this->execQueryVoid($statement);
        } catch (Zend_Db_Statement_Exception $zdse) {
            if (Setup_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' ' . $zdse);
            
            // try it again with table prefix
            $statement = "ALTER TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . " DROP INDEX " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_indexName);
            $this->execQueryVoid($statement);
        }
    }

    /**
     * create the right mysql-statement-snippet for columns/fields
     *
     * @param Setup_Backend_Schema_Field_Abstract $_field field / column
     * @param String $_tableName [Not used in this backend (MySQL)]
     * @return string
     */
    public function getFieldDeclarations(Setup_Backend_Schema_Field_Abstract $_field, $_tableName = '')
    {
        $buffer = $this->_getFieldDeclarations($_field, $_tableName);

        $definition = implode(' ', $buffer);

        return $definition;
    }

    /**
     * Compare Setup_Backend_Schema_Table_Abstract table schema with the corresponding schema
     * read from db using {@see getExistingSchema()}
     *
     * @param Setup_Backend_Schema_Table_Abstract $_table
     * @return bool
     */
    public function checkTable(Setup_Backend_Schema_Table_Abstract $_table)
    {
        $dbTable = $this->getExistingSchema($_table->name);
        return $dbTable->equals($_table);
    }

    /**
     * Backup Database
     *
     * @param $options
     * @throws Setup_Backend_Exception_NotImplemented
     */
    public function backup($options)
    {
        throw new Setup_Backend_Exception_NotImplemented('backup not yet implemented');
    }

    /**
     * Restore Database
     *
     * @param $options
     * @throws Setup_Backend_Exception_NotImplemented
     */
    public function restore($options)
    {
        throw new Setup_Backend_Exception_NotImplemented('restore not yet implemented');
    }

    /**
     * create the right mysql-statement-snippet for columns/fields
     *
     * @param Setup_Backend_Schema_Field_Abstract $_field field / column
     * @param String $_tableName [Not used in this backend (MySQL)]
     * @return string
     */
    protected function _getFieldDeclarations(Setup_Backend_Schema_Field_Abstract $_field, $_tableName = '')
    {
        $buffer = array();
        $buffer[] = '  ' . $this->_db->quoteIdentifier($_field->name);

        $buffer = $this->_addDeclarationFieldType($buffer, $_field, $_tableName);
        $buffer = $this->_addDeclarationUnsigned($buffer, $_field);
        $buffer = $this->_addDeclarationCollation($buffer, $_field);
        $buffer = $this->_addDeclarationDefaultValue($buffer, $_field);
        $buffer = $this->_addDeclarationNotNull($buffer, $_field);
        $buffer = $this->_addDeclarationAutoincrement($buffer, $_field);
        $buffer = $this->_addDeclarationComment($buffer, $_field);
        
        return $buffer;
    }

    protected function _addDeclarationCollation(array $_buffer, Setup_Backend_Schema_Field_Abstract $_field)
    {
        return $_buffer;
    }

    protected function _addDeclarationFieldType(array $_buffer, Setup_Backend_Schema_Field_Abstract $_field, $_tableName = '')
    {
        $typeMapping = $this->getTypeMapping($_field->type);
        if (!$typeMapping) {
            throw new Setup_Backend_Exception_InvalidSchema("Could not get field declaration for field {$_field->name}: The given field type {$_field->type} is not supported");
        }
        
        $fieldType = $typeMapping['defaultType'];
        if (isset($typeMapping['declarationMethod'])) {
            $fieldBuffer = call_user_func(array($this, $typeMapping['declarationMethod']), $_field, $_tableName);
            $_buffer = array_merge($_buffer, $fieldBuffer);
        } else {
            if ($_field->length !== NULL) {
                if ($this->_db instanceof Zend_Db_Adapter_Oracle) {
                    if ($_field->type == 'integer' && $_field->length == '64') {
                        $_field->length = '38';
                    }
                }
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

                            if (!isset($typeMapping['lengthLessTypes']) || ! in_array($type, $typeMapping['lengthLessTypes'])) {
                                $options = "({$_field->length}{$scale})";
                            } else {
                                $options = '';
                            }
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
                    $values = array();
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
