<?php
/**
 * egroupware 2.0
 * 
 * @package     Tasks
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: $
 *
 */

/**
 * Tables setup for Tasks 2.0
 * @package Tasks
 * @subpackage  Setup
 */
class Tasks_Setup_SetupSqlTables
{
    /**
     * Creates Tasks 2.0 tables
     * @return void
     */
    public static function createTasksTables() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable(SQL_TABLE_PREFIX . 'tasks');
        } catch (Zend_Db_Statement_Exception $e) {
            $db->getConnection()->exec("CREATE TABLE " . SQL_TABLE_PREFIX . "class (
                `identifier` INT(11) NOT NULL auto_increment,
                `created_by` INT(11) NOT NULL,
                `creation_time` DATETIME NOT NULL,
                `last_modified_by` INT(11),
                `last_modified_time` DATETIME DEFAULT NULL,
                `is_deleted` BOOLEAN NOT NULL DEFAULT FALSE,
                `deleted_time` DATETIME DEFAULT NULL,
                `deleted_by` INT(11),
                `class` VARCHAR(64) NOT NULL,
                PRIMARY KEY  (`identifier`),
                UNIQUE (`class`)) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
            
            $db->getConnection()->exec("CREATE TABLE " . SQL_TABLE_PREFIX . "tasks_status (
                `identifier` INT(11) NOT NULL auto_increment,
                `created_by` INT(11) NOT NULL,
                `creation_time` DATETIME NOT NULL,
                `last_modified_by` INT(11),
                `last_modified_time` DATETIME DEFAULT NULL,
                `is_deleted` BOOLEAN NOT NULL DEFAULT FALSE,
                `deleted_time` DATETIME DEFAULT NULL,
                `deleted_by` INT(11),
                `status` VARCHAR(64) NOT NULL,
                PRIMARY KEY  (`identifier`),
                UNIQUE (`status`)) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );

            $db->getConnection()->exec("CREATE TABLE " . SQL_TABLE_PREFIX . "tasks (
                `identifier` INT(11) NOT NULL auto_increment,
                `container` INT(11) NOT NULL,
                `created_by` INT(11) NOT NULL,
                `creation_time` DATETIME NOT NULL,
                `last_modified_by` INT(11),
                `last_modified_time` DATETIME DEFAULT NULL,
                `is_deleted` BOOLEAN NOT NULL DEFAULT FALSE,
                `deleted_time` DATETIME DEFAULT NULL,
                `deleted_by` INT(11),
                `percent` INT(3),
                `completed` DATETIME DEFAULT NULL,
                `due` DATETIME DEFAULT NULL,
                `class` INT(11),
                `description` LONGTEXT,
                `geo` FLOAT,
                `location` VARCHAR(256),
                `organizer` INT(11),
                `priority` INT(11),
                `status` INT(11),
                `summaray` VARCHAR(256),
                `url` VARCHAR(256),
                PRIMARY KEY  (`identifier`),
                KEY `" . SQL_TABLE_PREFIX . "tasks_container` (`container`),
                KEY `" . SQL_TABLE_PREFIX . "tasks_organizer` (`organizer`),
                FOREIGN KEY (`class`) REFERENCES " . SQL_TABLE_PREFIX . "class(`identifier`) ON DELETE RESTRICT,
                FOREIGN KEY (`status`) REFERENCES " . SQL_TABLE_PREFIX . "tasks_status(`identifier`) ON DELETE RESTRICT)
                ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
            
            $db->getConnection()->exec("CREATE TABLE " . SQL_TABLE_PREFIX . "tasks_tag (
                `task_identifier` INT(11) NOT NULL,
                `tag_identifier` INT(11) NOT NULL,
                PRIMARY KEY  (`task_identifier`, `tag_identifier`),
                FOREIGN KEY (`task_identifier`) REFERENCES " . SQL_TABLE_PREFIX . "tasks(`identifier`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
            
            $db->getConnection()->exec("CREATE TABLE " . SQL_TABLE_PREFIX . "tasks_contact (
                `task_identifier` INT(11) NOT NULL,
                `contact_identifier` INT(11) NOT NULL,
                PRIMARY KEY  (`task_identifier`, `contact_identifier`),
                FOREIGN KEY (`task_identifier`) REFERENCES " . SQL_TABLE_PREFIX . "tasks(`identifier`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
            
            $db->getConnection()->exec("CREATE TABLE " . SQL_TABLE_PREFIX . "tasks_related (
                `task_identifier` INT(11) NOT NULL,
                `related_identifier` INT(11) NOT NULL,
                PRIMARY KEY  (`task_identifier`, `related_identifier`),
                FOREIGN KEY (`task_identifier`) REFERENCES " . SQL_TABLE_PREFIX . "tasks(`identifier`) ON DELETE CASCADE,
                FOREIGN KEY (`related_identifier`) REFERENCES " . SQL_TABLE_PREFIX . "tasks(`identifier`)) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
    }
    
    /**
     * inserts default records
     * @return void
     */
    public static function insertDefaultRecords() {
        $db = Zend_Registry::get('dbAdapter');
        $accountId = Zend_Registry::get('currentAccount')->accountId;
        $now = $db->quote(Zend_Date::now()->getIso());
        
        // egw_class
        $db->getConnection()->exec("INSERT INTO `" . SQL_TABLE_PREFIX . "class` (
            `created_by`, `creation_time`, `class` ) VALUES (
            $accountId, $now, 'PUBLIC')"
        );
        
        $db->getConnection()->exec("INSERT INTO `" . SQL_TABLE_PREFIX . "class` (
            `created_by`, `creation_time`, `class` ) VALUES (
            $accountId, $now ,'PRIVATE')"
        );
        
        $db->getConnection()->exec("INSERT INTO `" . SQL_TABLE_PREFIX . "class` (
            `created_by`, `creation_time`, `class` ) VALUES (
            $accountId, $now ,'CONFIDENTIAL')"
        );
        
        // egw_tasks_status
        $db->getConnection()->exec("INSERT INTO `" . SQL_TABLE_PREFIX . "tasks_status` (
            `created_by`, `creation_time`, `status` ) VALUES (
            $accountId, $now, 'NEEDS-ACTION')"
        );
        
        $db->getConnection()->exec("INSERT INTO `" . SQL_TABLE_PREFIX . "tasks_status` (
            `created_by`, `creation_time`, `status` ) VALUES (
            $accountId, $now, 'COMPLETED')"
        );
        
        $db->getConnection()->exec("INSERT INTO `" . SQL_TABLE_PREFIX . "tasks_status` (
            `created_by`, `creation_time`, `status` ) VALUES (
            $accountId, $now, 'IN-PROCESS')"
        );
        
        $db->getConnection()->exec("INSERT INTO `" . SQL_TABLE_PREFIX . "tasks_status` (
            `created_by`, `creation_time`, `status` ) VALUES (
            $accountId, $now, 'CANCELLED')"
        );
    }
    
}