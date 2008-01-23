<?php
/**
 * egroupware 2.0
 * 
 * @package     CRM
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>, Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$ 
 *
 */

/**
 * Tables setup for CRM 2.0
 * @package CRM
 * @subpackage  Setup
 */
class Crm_Setup_SetupSqlTables
{    
    /**
     * temporary function to create the egw_metacrm_lead table on demand
     *
     */
    public static function createLeadTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable(SQL_TABLE_PREFIX . 'metacrm_lead');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $result = $db->getConnection()->exec("CREATE TABLE `" . SQL_TABLE_PREFIX . "metacrm_lead` (
                    `lead_id` int(11) NOT NULL auto_increment,
                    `lead_name` varchar(255) NOT NULL default '',
                    `lead_leadstate_id` int(11) NOT NULL default '0',
                    `lead_leadtype_id` int(11) NOT NULL default '0',
                    `lead_leadsource_id` int(11) NOT NULL default '0',
                    `lead_container` int(11) NOT NULL default '0',
                    `lead_modifier` int(11) default NULL,
                    `lead_start` DATETIME NOT NULL,
                    `lead_modified` int(11) NOT NULL default '0',
                    `lead_created` int(11) unsigned NOT NULL default '0',
                    `lead_description` text,
                    `lead_end` DATETIME default NULL,
                    `lead_turnover` double default NULL,
                    `lead_probability` decimal(3,0) default NULL,
                    `lead_end_scheduled` DATETIME default NULL,
                    `lead_lastread` int(11) NOT NULL default '0',
                    `lead_lastreader` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`lead_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
    }
    
    /**
     * temporary function to create the egw_metacrm_leadsource table on demand
     *
     */
    public static function createLeadSourceTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable(SQL_TABLE_PREFIX . 'metacrm_leadsource');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $result = $db->getConnection()->exec("CREATE TABLE `" . SQL_TABLE_PREFIX . "metacrm_leadsource` (
                    `lead_leadsource_id` int(11) NOT NULL auto_increment,
                    `lead_leadsource` varchar(255) NOT NULL,
                    `lead_leadsource_translate` tinyint(4) default '1',
                    PRIMARY KEY  (`lead_leadsource_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
        
        $leadSourceTable   = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadsource'));
        
        $leadSourceTable->insert(array(
            'lead_leadsource_id'    => 1,
            'lead_leadsource'       => 'telephone'
        ));
        $leadSourceTable->insert(array(
            'lead_leadsource_id'    => 2,
            'lead_leadsource'       => 'email'
        ));
        $leadSourceTable->insert(array(
            'lead_leadsource_id'    => 3,
            'lead_leadsource'       => 'website'
        ));
        $leadSourceTable->insert(array(
            'lead_leadsource_id'    => 4,
            'lead_leadsource'       => 'fair'
        ));
    }
        
    /**
     * temporary function to create the egw_metacrm_leadtype table on demand
     *
     */
    public static function createLeadTypeTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable(SQL_TABLE_PREFIX . 'metacrm_leadtype');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $result = $db->getConnection()->exec("CREATE TABLE `" . SQL_TABLE_PREFIX . "metacrm_leadtype` (
                    `lead_leadtype_id` int(11) NOT NULL auto_increment,
                    `lead_leadtype` varchar(255) default NULL,
                    `lead_leadtype_translate` tinyint(4) default '1',
                    PRIMARY KEY  (`lead_leadtype_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
        
        $leadTypeTable   = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadtype'));
        
        $leadTypeTable->insert(array(
            'lead_leadtype_id'    => 1,
            'lead_leadtype'       => 'customer'
        ));
        $leadTypeTable->insert(array(
            'lead_leadtype_id'    => 2,
            'lead_leadtype'       => 'partner'
        ));
        $leadTypeTable->insert(array(
            'lead_leadtype_id'    => 3,
            'lead_leadtype'       => 'reseller'
        ));
    }
        
    /**
     * temporary function to create the egw_metacrm_leadstate table on demand
     *
     */
    public static function createLeadStateTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable(SQL_TABLE_PREFIX . 'metacrm_leadstate');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $result = $db->getConnection()->exec("CREATE TABLE `" . SQL_TABLE_PREFIX . "metacrm_leadstate` (
                    `lead_leadstate_id` int(11) NOT NULL auto_increment,
                    `lead_leadstate` varchar(255) default NULL,
                    `lead_leadstate_probability` tinyint(3) unsigned NOT NULL default '0',
                    `lead_leadstate_endslead` tinyint(1) default NULL,
                    `lead_leadstate_translate` tinyint(4) default '1',
                    PRIMARY KEY  (`lead_leadstate_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
        
        $leadStateTable   = new Egwbase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadstate'));
        
        $leadStateTable->insert(array(
            'lead_leadstate_id'           => 1,
            'lead_leadstate'              => 'open',
            'lead_leadstate_probability'  => 0
        ));    
        $leadStateTable->insert(array(
            'lead_leadstate_id'           => 2,
            'lead_leadstate'              => 'contacted',
            'lead_leadstate_probability'  => 10
        ));
        $leadStateTable->insert(array(
            'lead_leadstate_id'           => 3,
            'lead_leadstate'              => 'waiting for feedback',
            'lead_leadstate_probability'  => 30
        ));
        $leadStateTable->insert(array(
            'lead_leadstate_id'           => 4,
            'lead_leadstate'              => 'quote sent',
            'lead_leadstate_probability'  => 50
        ));
        $leadStateTable->insert(array(
            'lead_leadstate_id'           => 5,
            'lead_leadstate'              => 'accepted',
            'lead_leadstate_probability'  => 100,
            'lead_leadstate_endslead'  => 1
        ));
        $leadStateTable->insert(array(
            'lead_leadstate_id'           => 6,
            'lead_leadstate'              => 'lost',
            'lead_leadstate_probability'  => 0,
            'lead_leadstate_endslead'  => 1
        ));
    }
        
    /**
     * temporary function to create the egw_metacrm_productsource table on demand
     *
     */
    public static function createProductSourceTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable(SQL_TABLE_PREFIX . 'metacrm_productsource');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $result = $db->getConnection()->exec("CREATE TABLE `" . SQL_TABLE_PREFIX . "metacrm_productsource` (
                    `lead_productsource_id` int(10) unsigned NOT NULL auto_increment,
                    `lead_productsource` varchar(200) NOT NULL default '',
                    `lead_productsource_price` decimal(12,2) unsigned NOT NULL default '0.00',
                    PRIMARY KEY  (`lead_productsource_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
    }
    
    /**
     * temporary function to create the egw_metacrm_product table on demand
     *
     */
    public static function createProductTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable(SQL_TABLE_PREFIX . 'metacrm_product');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $result = $db->getConnection()->exec("CREATE TABLE `" . SQL_TABLE_PREFIX . "metacrm_product` (
                    `lead_id` int(11) NOT NULL auto_increment,
                    `lead_lead_id` int(11) NOT NULL,
                    `lead_product_id` int(11) NOT NULL,
                    `lead_product_desc` varchar(255) default NULL,
                    `lead_product_price` decimal(12,2) unsigned NOT NULL default '0.00',
                    PRIMARY KEY  (`lead_id`),
                    KEY `lead_lead_id` (`lead_lead_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
    }    
}