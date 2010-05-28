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
 * setup backend class for Oracle
 *
 * @package     Setup
 */
class Setup_Backend_Oracle extends Setup_Backend_Abstract
{
    /**
     * Define how database agnostic data types get mapped to oracle data types
     * 
     * @var array
     */
    protected $_typeMappings = array(
        'integer' => array( //integer in oracle is NUMBER with a scale of 0
            'lengthTypes' => array(
                38 => 'NUMBER'),
            'defaultScale' => 0,
            'defaultType' => 'NUMBER',
            'defaultLength' => self::INTEGER_DEFAULT_LENGTH),
        'boolean' => array(
            'defaultType' => 'NUMBER',
            'defaultScale' => 0,
            'defaultLength' => 1),
        'text' => array(
            'lengthTypes' => array(
                256 => 'VARCHAR2', //@todo this should be 255 indeed but we have 256 in our setup.xml files
                4294967295 => 'CLOB'),
            'defaultType' => 'CLOB',
            'defaultLength' => null),
        'float' => array( //float in oracle is NUMBER without precision and scale options
            'defaultType' => 'NUMBER'),
        'decimal' => array( //decimal in oracle is NUMBER with length (precision) and scale options
            'lengthTypes' => array(
                38 => 'NUMBER'),
            'defaultType' => 'NUMBER',
            'defaultScale' => '0'),
        'datetime' => array(
            'defaultType' => 'VARCHAR2',
            'defaultLength' => 25),
        'date' => array(
            'defaultType' => 'date'),
        'blob' => array(
            'defaultType' => 'BLOB'),
        'clob' => array(
            'defaultType' => 'CLOB'),
        'enum' => array(
            'defaultType' => 'VARCHAR2',
            'declarationMethod' => '_getSpecialFieldDeclarationEnum')
    );
 
    CONST CONSTRAINT_TYPE_PRIMARY   = 'P';
    CONST CONSTRAINT_TYPE_FOREIGN   = 'R';
    CONST CONSTRAINT_TYPE_CHECK     = 'C';
    CONST CONSTRAINT_TYPE_UNIQUE    = 'U';
    
    protected $_table ='';
   
    protected $_autoincrementId = '';
    
    protected static $_sequence_postfix = '_seq';
    
    /**
     * takes the xml stream and creates a table
     *
     * @param object $_table xml stream
     */
    public function createTable(Setup_Backend_Schema_Table_Abstract $_table)
    {
        $this->_table = $_table->name;
        
        parent::createTable($_table);
        
        if (!empty($this->_autoincrementId)) {
            $statement = $this->getIncrementSequence($_table->name);
            $this->execQueryVoid($statement);
            $statement = $this->getIncrementTrigger($_table->name);
            $this->execQueryVoid($statement);
            

            unset($this->_autoincrementId);
        }
        
       foreach ($_table->indices as $index) {    
            if (empty($index->primary) && empty($index->unique) && !$index->foreign) {
               $this->addIndex($_table->name, $index);
            }
        }  

        foreach ($_table->fields as $field) {
            if (isset($field->comment)) {
                $this->setFieldComment($_table->name, $field->name, $field->comment);
            }         
        }
        

    }
    
    protected function _getIncrementSequenceName($_tableName)
    {
        return SQL_TABLE_PREFIX . substr($_tableName, 0, 20) . self::$_sequence_postfix;
    }
    
    public function getIncrementSequence($_tableName) 
    { 
        $statement = 'CREATE SEQUENCE ' . $this->_db->quoteIdentifier($this->_getIncrementSequenceName($_tableName)) . ' 
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

    
    protected function _getIncrementTriggerName($_tableName) 
    {
        return SQL_TABLE_PREFIX . substr($_tableName, 0, 20) . '_tri';
    }
    
    public function getIncrementTrigger($_tableName) 
    {
        $statement = 'CREATE TRIGGER ' . $this->_db->quoteIdentifier($this->_getIncrementTriggerName($_tableName)) . '
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
           $statementSnippets[] = $this->getFieldDeclarations($field, $_table->name);
        }

        foreach ($_table->indices as $index) {    
            if ($index->foreign) {
               $statementSnippets[] = $this->getForeignKeyDeclarations($index, $_table->name);
            } else if ($index->primary || $index->unique) {
               $statementSnippets[] = $this->getIndexDeclarations($index, $_table->name);
            }
        }
        
        if (isset($_table->comment)) {
            //@todo support comments
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . '  ignoring comment because comments are currently not supported by oracle adapter.');
        }
        
        $statement .= implode(",\n", $statementSnippets) . "\n)";
        
        // auto shutup by cweiss: echo "<pre>$statement</pre>";
        
        return $statement;
    }
    
    public function getExistingSchema($_tableName)
    {
        $tableInfo = $this->_getTableInfo($_tableName);       
        $existingTable = Setup_Backend_Schema_Table_Factory::factory('Oracle', $tableInfo);
        foreach ($tableInfo as $index => $tableColumn) {
            $field = Setup_Backend_Schema_Field_Factory::factory('Oracle', $tableColumn);
            $existingTable->addField($field);
            if ($field->primary === 'true' || $field->unique === 'true' || $field->mul === 'true') {
                $index = Setup_Backend_Schema_Index_Factory::factory('Oracle', $tableColumn);
                $existingTable->addIndex($index);
            }
        }
        
        $foreignKeys = $this->getConstraintsForTable($_tableName, Setup_Backend_Oracle::CONSTRAINT_TYPE_FOREIGN, true);
        foreach ($foreignKeys as $foreignKey) {
            $index = Setup_Backend_Schema_Index_Factory::factory('Oracle', $tableColumn);
            $index->setForeignKey($foreignKey);
            $existingTable->addIndex($index);
        }

        return $existingTable;
    }
    
    protected function _getTableInfo($_tableName)
    {
        $tableName = SQL_TABLE_PREFIX . $_tableName;
        $tableInfo = $this->_db->describeTable($tableName);
        $trigger = $this->_db->fetchRow("SELECT * FROM USER_TRIGGERS WHERE TRIGGER_NAME=?", array($this->_getIncrementTriggerName($_tableName)));
        $fieldComments = $this->_getFieldComments($_tableName);
        
        foreach ($tableInfo as $index => $field) {
            $field['COLUMN_COMMENT'] = isset($fieldComments[$field['COLUMN_NAME']]) ? $fieldComments[$field['COLUMN_NAME']] : null;
         
            switch ($field['DATA_TYPE']) {
                case 'VARCHAR2':
                    $constraint = $this->_db->fetchOne("SELECT SEARCH_CONDITION FROM USER_CONSTRAINTS WHERE CONSTRAINT_NAME=?", array($this->_getConstraintEnumName($_tableName, $field['COLUMN_NAME'])));
                    if ($constraint) {
                        $field['DATA_TYPE'] = 'enum';
                        //extract allowed enum values to $field['TYPE_SPECIAL']
                        preg_match('/.* IN \((.*)\)$/', $constraint, $matches);
                        $field['TYPE_SPECIAL'] = $matches[1]; 
                    }
                    break;
            }
            
            $field['EXTRA'] = '';
            if (isset($trigger['TRIGGER_BODY']) &&
                strstr($trigger['TRIGGER_BODY'], ':NEW.' . $this->_db->quoteIdentifier($field['COLUMN_NAME'])))
               {
                $field['EXTRA'] = 'auto_increment';
            }
            //@todo aggregate more information liek auto_increment, indices, constraints etc. that have not been returned by describeTable
            
            $tableInfo[$index] = $field;
        }
        
        

        
 
        return $tableInfo;
    }
    
    protected function _sequenceExists($_tableName)
    {
        return (bool)$this->_db->fetchOne("SELECT SEQUENCE_NAME FROM USER_SEQUENCES WHERE SEQUENCE_NAME=?", array($this->_getIncrementSequenceName($_tableName)));
    }
   
    /**
     * Get a list of index names belonging to the given {@param $_tableName}
     * 
     * @param String $_tableName
     * @return Array
     */
    public function getIndexesForTable($_tableName)
    {
        $tableName = SQL_TABLE_PREFIX . $_tableName;
        $sql = 'SELECT INDEX_NAME FROM ' . $this->_db->quoteIdentifier('ALL_INDEXES') . ' WHERE TABLE_NAME=:tableName';
        return $this->_db->fetchCol($sql, array('tableName' => $tableName));
    }
    
    /**
     * Get a list of constraints belonging to the given {@param $_tableName}
     * 
     * @param String $_tableName
     * @param String | optional [restrict returned constraints to this type]
     * @return Array
     */
    public function getConstraintsForTable($_tableName, $_constraintType = null)
    {
        $select = $this->_db->select();
        $select->from('ALL_CONSTRAINTS')->where('TABLE_NAME=?', SQL_TABLE_PREFIX . $_tableName);
        if ($_constraintType) {
            $select->where('CONSTRAINT_TYPE=?', $_constraintType);
        }
        return $this->_db->fetchAll($select);
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
        if ($_position != NULL) {
            throw new Setup_Backend_Exception_NotImplemented(__METHOD__ . ' parameter "$_position" is not supported in Oracle adapter');
        }

        if ($_declaration->autoincrement) {
            throw new Setup_Backend_Exception_NotImplemented('Add column autoincrement option is not implemented in Orcale adapter');
        }
     
        $statement = "ALTER TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . " ADD (" ;
        
        $statement .= $this->getFieldDeclarations($_declaration, $_tableName);
        
        $statement .= ")";
        
        $this->execQueryVoid($statement);
        
        if (isset($_declaration->comment)) {
            $this->setFieldComment($_tableName, $_declaration->name, $_declaration->comment);
        }
    }
    
    public function setFieldComment($_tableName, $_fieldName, $_comment)
    {
        $statement = "COMMENT ON COLUMN " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . "." . $this->_db->quoteIdentifier($_fieldName) . " IS " . $this->_db->quote($_comment);
        $this->execQueryVoid($statement); 
    }
    
    public function getFieldComment($_tableName, $_fieldName)
    {
        return $this->_db->fetchOne("SELECT COMMENTS FROM USER_COL_COMMENTS WHERE TABLE_NAME=:table_name AND COLUMN_NAME=:column_name", 
            array(
                'table_name' => SQL_TABLE_PREFIX . $_tableName,
                'column_name' => $_fieldName
            )
        ); 
    }
    
    protected function _getFieldComments($_tableName)
    {
        $fieldComments = array();
        $fieldCommentsRaw = $this->_db->fetchAll("SELECT COLUMN_NAME, COMMENTS FROM USER_COL_COMMENTS WHERE TABLE_NAME = :table_name", array('table_name' => SQL_TABLE_PREFIX . $_tableName));
        foreach ($fieldCommentsRaw as $fieldComment) {
            if (!empty($fieldComment['COMMENTS'])) {
                $fieldComments[$fieldComment['COLUMN_NAME']] = $fieldComment['COMMENTS'];
            }
        }
        return $fieldComments;
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
        if (isset($_oldName) && $_oldName != $_declaration->name) {
            $this->_renameCol($_tableName, $_oldName, $_declaration->name);
        }

        $statement = "ALTER TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . " MODIFY " ;
        $oldName = $_oldName ;
        
        if ($_oldName == NULL) {
            $oldName = SQL_TABLE_PREFIX . $_declaration->name;
        }
        
        $statement .= $this->getFieldDeclarations($_declaration, $_tableName);
        $this->execQueryVoid($statement);    
    }
    
    /**
     * rename column/field in database table
     * 
     * @param string $_tableName
     * @param string $_oldName
     * @param string $_newName 
     */    
    protected function _renameCol($_tableName, $_oldName, $_newName)
    {
        $statement = "ALTER TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . " RENAME  COLUMN " . $this->_db->quoteIdentifier($_oldName) . ' TO ' . $this->_db->quoteIdentifier($_newName);
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
    	    $statement = 'DROP SEQUENCE ' . $this->_db->quoteIdentifier($this->_getIncrementSequenceName($_tableName));
    	    $this->execQueryVoid($statement);
        } catch (Zend_Db_Statement_Exception $e) {
        	if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " An exception was thrown while dropping sequence for table {$_tableName}: " . $e->getMessage() . "; This might be OK if the table had no sequencer.");
        }
    }
 
    /**
     * add a key to database table
     * 
     * @param string tableName 
     * @param Setup_Backend_Schema_Index_Abstract declaration
     */     
    public function addIndex($_tableName, Setup_Backend_Schema_Index_Abstract $_declaration)
    {
       $statement = $this->getIndexDeclarations($_declaration, $_tableName);
       if (!empty($_declaration->primary) || !empty($_declaration->unique)) {
            $statement = "ALTER TABLE " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName) . " ADD " . $statement;
        }
        
        $this->execQueryVoid($statement);    
    }
    
    protected function _getConstraintEnumName($_tableName, $_fieldName)
    {
        $tableName = SQL_TABLE_PREFIX . $_tableName;
        return $this->_sanititzeName('cons_' . $tableName . "_" . $_fieldName . '_enum');
    }
    
    /**
     * create the right mysql-statement-snippet for columns/fields
     *
     * @param Setup_Backend_Schema_Field field / column
     * @param String $_tableName [required in this backend (Oracle)]
     * @todo how gets unsigned handled
     * @return string
     */
    public function getFieldDeclarations(Setup_Backend_Schema_Field_Abstract $_field, $_tableName = '')
    {
        if (empty($_tableName)) {
            throw new Tinebase_Exception_InvalidArgument('Missing required argument $_tableName');
        }
        
        return parent::getFieldDeclarations($_field, $_tableName);
    }
    
    /**
     * Override method: unsigned option is not supported by oracle backend
     * @see tine20/Setup/Backend/Setup_Backend_Abstract#_addDeclarationUnsigned($_buffer, $_field)
     */
    protected function _addDeclarationUnsigned(array $_buffer, Setup_Backend_Schema_Field_Abstract $_field)
    {
        if (isset($_field->unsigned) && $_field->unsigned === true) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' $_field has property unsgined set which is currently not supported by oracle adapter; unsigned property is ignored.');
        }
        return $_buffer;
    }
    
    /**
     * Override method: default value option has to be handled differently for enum data type
     * @see tine20/Setup/Backend/Setup_Backend_Abstract#_addDeclarationDefaultValue($_buffer, $_field)
     */
    protected function _addDeclarationDefaultValue(array $_buffer, Setup_Backend_Schema_Field_Abstract $_field)
    {
        if ($_field->type == 'enum') {
            return $_buffer;
        }
        return parent::_addDeclarationDefaultValue($_buffer, $_field);
    }
    
    /**
     * Override method: not null option has to be handled differently for enum data type
     * @see tine20/Setup/Backend/Setup_Backend_Abstract#_addDeclarationNotNull($_buffer, $_field)
     */
    protected function _addDeclarationNotNull(array $_buffer, Setup_Backend_Schema_Field_Abstract $_field)
    {
        if ($_field->type == 'enum') {
            return $_buffer;
        }
        return parent::_addDeclarationNotNull($_buffer, $_field);
    }
    
    /**
     * Override method: autoincrementation is set up on table creation in oracle {@see createTable()}
     * => store the name of the autoincrement field in {@see $_autoincrementId} 
     * @see tine20/Setup/Backend/Setup_Backend_Abstract#_addDeclarationAutoincrement($_buffer, $_field)
     */
    protected function _addDeclarationAutoincrement(array $_buffer, Setup_Backend_Schema_Field_Abstract $_field)
    {
        if (isset($_field->autoincrement)) {
            $this->_autoincrementId = $_field->name;
        }
        return $_buffer;
    }
    
    /**
     * Override method: comments are added after creating/aletering the table in {@see addCol()} and {@see createTable()}.
     * 
     * @see tine20/Setup/Backend/Setup_Backend_Abstract#_addDeclarationAutoincrement($_buffer, $_field)
     */
    protected function _addDeclarationComment(array $_buffer, Setup_Backend_Schema_Field_Abstract $_field)
    {
        return $_buffer;
    }
    
    /**
     * enum datatype is not supported by oracle so we have to emulate the behaviour using constraint checks
     * 
     * @param Setup_Backend_Schema_Field_Abstract $_field
     * @param $_tableName
     * @return array
     */
    protected function _getSpecialFieldDeclarationEnum(Setup_Backend_Schema_Field_Abstract $_field, $_tableName)
    {
        $buffer = array();

        $length = 0;
        foreach ($_field->value as $value) {
            $values[] = $value;
            $tempLength = strlen($value);
            if ($tempLength > $length) {
                $length = $tempLength;
            }
        }
        
        $buffer[] = 'VARCHAR2(' . $length . ')';
        
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
        
        $buffer[] = $additional . ', CONSTRAINT ' . $this->_db->quoteIdentifier($this->_getConstraintEnumName($_tableName, $_field->name)) . ' CHECK ("'. $_field->name . "\" IN ('" . implode("','", $values) . "'))";

        return $buffer;
    }

    /**
     * create the right mysql-statement-snippet for keys
     *
     * @param   Setup_Backend_Schema_Index_Abstract key
     * @param   String $_tableName [parameter is required in this (Oracle) Backend. It is used to create unique index names spanning all tables of the database] 
     * @return  String
     * @throws  Setup_Exception_NotFound
     */
    public function getIndexDeclarations(Setup_Backend_Schema_Index_Abstract $_key, $_tableName = '')
    {   
        if (empty($_tableName)) {
            throw new Tinebase_Exception_InvalidArgument('Missing required argument $_tableName');
        }

        $keys = array();
        if (!empty($_key->primary)) {
            $name = $this->_sanititzeName(SQL_TABLE_PREFIX . 'pk_' . $_tableName);
            $snippet = '  CONSTRAINT ' . $this->_db->quoteIdentifier($name) . " PRIMARY KEY";
        } else if (!empty($_key->unique)) {
            $name = $this->_sanititzeName(SQL_TABLE_PREFIX . "uni_" . $_tableName . "_" . $_key->name);
            $snippet = '  CONSTRAINT ' . $this->_db->quoteIdentifier($name) . " UNIQUE";
        } else {
            $name = $this->_sanititzeName(SQL_TABLE_PREFIX . 'idx_' . $_tableName . "_" . $_key->name);
            $snippet = '  CREATE INDEX ' . $this->_db->quoteIdentifier($name) . ' ON ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_tableName);
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
     * @param String $_tableName [required in this backend (Oracle)]
     * @return string
     */
    public function getForeignKeyDeclarations(Setup_Backend_Schema_Index_Abstract $_key, $_tableName = '')
    {
        if (empty($_tableName)) {
            throw new Tinebase_Exception_InvalidArgument('Missing required argument $_tableName');
        }

        if (!empty($_key->referenceOnUpdate)) {
            //$snippet .= " ON UPDATE " . strtoupper($_key->referenceOnUpdate);
            // comment for now, because we can't install if we throw exception (what ca we do with ON UPDATE?)
            //throw new Setup_Backend_Exception_NotImplemented('ON UPDATE CONSTRAINTS are not supported by Oracle adapter');
        }
        
        $constraintName = $this->_sanititzeName(SQL_TABLE_PREFIX . 'fk_' . $_tableName . "_" . $_key->field);
        $snippet = '  CONSTRAINT ' . $this->_db->quoteIdentifier($constraintName) . ' FOREIGN KEY ';
        $snippet .= '("' . $_key->field . '") REFERENCES ' . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . $_key->referenceTable) . ' ("' . $_key->referenceField . '")';

        if (!empty($_key->referenceOnDelete)) {
            $snippet .= " ON DELETE " . strtoupper($_key->referenceOnDelete);
        }
        
        return $snippet;
    }

}