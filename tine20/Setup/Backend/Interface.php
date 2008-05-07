<?php

/**
 * interface for backend class
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @copyright   Copyright (c); 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de);
 * @version     $Id: Interface.php 1735 2008-04-05 20:08:37Z lkneschke $
 *
 */

/**
 * interface for backend class
 * 
 * @package     Setup
 */
interface Setup_Backend_Interface
{
    
    /**
     * takes the xml stream and creates a table
     *
     * @param object $_table xml stream
     */
    public function createTable(Setup_Backend_Schema_Table_Abstract $_table);
    
    
    /**
    * add table to tine registry
    *
    * @param Tinebase_Model_Application
    * @param string name of table
    * @param int version of table
    * @return int
    */
    public function addTable(Tinebase_Model_Application $_application, $_name, $_version);
    
    /*
    * removes table from database
    * 
    * @param string tableName
    */
    public function dropTable($_tableName);

    /*
    * renames table in database
    * 
    * @param string tableName
    */
    public function renameTable($_tableName, $_newName);

    /*
    * add column/field to database table
    * 
    * @param string tableName
    * @param Setup_Backend_Schema_Field declaration
    * @param int position of future column
    */    
    public function addCol($_tableName, Setup_Backend_Schema_Field $_declaration, $_position = NULL);
    
    /*
    * rename or redefines column/field in database table
    * 
    * @param string tableName
    * @param Setup_Backend_Schema_Field declaration
    * @param string old column/field name 
    */    
    public function alterCol($_tableName, Setup_Backend_Schema_Field $_declaration, $_oldName = NULL);

    /*
    * drop column/field in database table
    * 
    * @param string tableName
    * @param string column/field name 
    */    
    public function dropCol($_tableName, $_colName);

     /*
    * add a foreign key to database table
    * 
    * @param string tableName
    * @param Setup_Backend_Schema_Index declaration
    */       
    public function addForeignKey($_tableName, Setup_Backend_Schema_Index $_declaration);

    /*
    * removes a foreign key from database table
    * 
    * @param string tableName
    * @param string foreign key name
    */     
    public function dropForeignKey($_tableName, $_name);
    
    /*
    * removes a primary key from database table
    * 
    * @param string tableName (there is just one primary key...);
    */         
    public function dropPrimaryKey($_tableName);
    
    /*
    * add a primary key to database table
    * 
    * @param string tableName 
    * @param Setup_Backend_Schema_Index declaration
    */         
    public function addPrimaryKey($_tableName, Setup_Backend_Schema_Index $_declaration);
 
    /*
    * add a key to database table
    * 
    * @param string tableName 
    * @param Setup_Backend_Schema_Index declaration
    */     
    public function addIndex($_tableName ,  Setup_Backend_Schema_Index$_declaration);
    
    /*
    * removes a key from database table
    * 
    * @param string tableName 
    * @param string key name
    */    
    public function dropIndex($_tableName, $_indexName);
    
    
    public function applicationExists($_application);
    
    /**
     * checks if a given table exists
     *
     * @param string $_tableSchema
     * @param string $_tableName
     * @return boolean return true if the table exists, otherwise false
     */
    public function tableExists($_tableName);

    /**
     * /***checks a given database table version 
     *
     * @param string $_tableName
     * @return boolean return string "version" if the table exists, otherwise false
     */
    
    public function tableVersionQuery($_tableName);
    
    /**
     * checks a given application version
     *
     * @param string $_application
     * @return boolean return string "version" if the table exists, otherwise false
     */
    public function applicationVersionQuery($_application);

    public function getExistingSchema($_tableName);

    public function checkTable(Setup_Backend_Schema_Table_Abstract $_table);
 
    public function getFieldDeclarations(Setup_Backend_Schema_Field_Abstract $_field);

    public function getIndexDeclarations(Setup_Backend_Schema_Index_Abstract $_index);
}
