<?php
/**
 * Tine 2.0
 * 
 * @package     CRM
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$ 
 *
 */

/**
 * Tables setup for Tinebase
 * @package Tinebase
 * @subpackage  Setup
 */
class Tinebase_Setup_SetupSqlTables
{    
    public static function createPersistentObserverTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable(SQL_TABLE_PREFIX . 'record_persistentobserver');
        } catch (Zend_Db_Statement_Exception $e) {
            $db->getConnection()->exec("CREATE TABLE " . SQL_TABLE_PREFIX . "record_persistentobserver (
                `identifier` INT(11) NOT NULL auto_increment,
                `created_by` INT(11) NOT NULL,
                `creation_time` DATETIME NOT NULL,
                `last_modified_by` INT(11),
                `last_modified_time` DATETIME DEFAULT NULL,
                `is_deleted` BOOLEAN NOT NULL DEFAULT FALSE,
                `deleted_time` DATETIME DEFAULT NULL,
                `deleted_by` INT(11),
                `observable_application` INT(11) NOT NULL,
                `observable_identifier` INT(11) NOT NULL,
                `observer_application` INT(11) NOT NULL,
                `observer_identifier` INT(11) NOT NULL,
                `observed_event` VARCHAR(64) NOT NULL,
                PRIMARY KEY  (`identifier`),
                KEY  (`observer_application`),
                KEY  (`observer_identifier`),
                UNIQUE (`observable_application`, `observable_identifier`, `observer_application`, `observer_identifier`, `observed_event`)) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
    }
    
    /**
     * temporary function to create the SQL_TABLE_PREFIX . record_relations table on demand
     *
     */
    public static function createRelationTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable(SQL_TABLE_PREFIX . 'record_relations');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $db->getConnection()->exec("CREATE TABLE " . SQL_TABLE_PREFIX . "record_relations (
                `identifier` INT(11) NOT NULL auto_increment,
                `created_by` INT(11) NOT NULL,
                `creation_time` DATETIME NOT NULL,
                `last_modified_by` INT(11),
                `last_modified_time` DATETIME DEFAULT NULL,
                `is_deleted` BOOLEAN NOT NULL DEFAULT FALSE,
                `deleted_time` DATETIME DEFAULT NULL,
                `deleted_by` INT(11),
                `own_application` INT(11) NOT NULL,
                `own_identifier` INT(11) NOT NULL,
                `related_application` INT(11) NOT NULL,
                `related_identifier` INT(11) NOT NULL,
                `related_role` VARCHAR(16) NOT NULL,
                PRIMARY KEY  (`identifier`),
                KEY (`own_identifier`),
                UNIQUE  (`own_application`, `own_identifier`, `related_role`, `related_application`, `related_identifier` ))
                ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
    }
 }
 ?>