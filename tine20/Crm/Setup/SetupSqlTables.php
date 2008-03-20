<?php
/**
 * Tine 2.0
 * 
 * @package     CRM
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
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
                    `lead` varchar(255) NOT NULL default '',
                    `leadstate_id` int(11) NOT NULL default '0',
                    `id` int(11) NOT NULL default '0',
                    `leadsource_id` int(11) NOT NULL default '0',
                    `container` int(11) NOT NULL default '0',
                    `lead_modifier` int(11) default NULL,
                    `lead_start` DATETIME NOT NULL,
                    `lead_modified` int(11) NOT NULL default '0',
                    `lead_created` int(11) unsigned NOT NULL default '0',
                    `description` text,
                    `end` DATETIME default NULL,
                    `turnover` double default NULL,
                    `probability` decimal(3,0) default NULL,
                    `end_scheduled` DATETIME default NULL,
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
                    `leadsource_id` int(11) NOT NULL auto_increment,
                    `leadsource` varchar(255) NOT NULL,
                    `leadsource_translate` tinyint(4) default '1',
                    PRIMARY KEY  (`leadsource_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
        
        $leadSourceTable   = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadsource'));
        
        $leadSourceTable->insert(array(
            'leadsource_id'    => 1,
            'leadsource'       => 'telephone'
        ));
        $leadSourceTable->insert(array(
            'leadsource_id'    => 2,
            'leadsource'       => 'email'
        ));
        $leadSourceTable->insert(array(
            'leadsource_id'    => 3,
            'leadsource'       => 'website'
        ));
        $leadSourceTable->insert(array(
            'leadsource_id'    => 4,
            'leadsource'       => 'fair'
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
                    `id` int(11) NOT NULL auto_increment,
                    `leadtype` varchar(255) default NULL,
                    `leadtype_translate` tinyint(4) default '1',
                    PRIMARY KEY  (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
        
        $leadTypeTable   = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadtype'));
        
        $leadTypeTable->insert(array(
            'id'    => 1,
            'leadtype'       => 'customer'
        ));
        $leadTypeTable->insert(array(
            'id'    => 2,
            'leadtype'       => 'partner'
        ));
        $leadTypeTable->insert(array(
            'id'    => 3,
            'leadtype'       => 'reseller'
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
                    `leadstate_id` int(11) NOT NULL auto_increment,
                    `leadstate` varchar(255) default NULL,
                    `leadstate_probability` tinyint(3) unsigned NOT NULL default '0',
                    `leadstate_endslead` tinyint(1) default NULL,
                    `leadstate_translate` tinyint(4) default '1',
                    PRIMARY KEY  (`leadstate_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
        
        $leadStateTable   = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'metacrm_leadstate'));
        
        $leadStateTable->insert(array(
            'leadstate_id'           => 1,
            'leadstate'              => 'open',
            'leadstate_probability'  => 0
        ));    
        $leadStateTable->insert(array(
            'leadstate_id'           => 2,
            'leadstate'              => 'contacted',
            'leadstate_probability'  => 10
        ));
        $leadStateTable->insert(array(
            'leadstate_id'           => 3,
            'leadstate'              => 'waiting for feedback',
            'leadstate_probability'  => 30
        ));
        $leadStateTable->insert(array(
            'leadstate_id'           => 4,
            'leadstate'              => 'quote sent',
            'leadstate_probability'  => 50
        ));
        $leadStateTable->insert(array(
            'leadstate_id'           => 5,
            'leadstate'              => 'accepted',
            'leadstate_probability'  => 100,
            'leadstate_endslead'  => 1
        ));
        $leadStateTable->insert(array(
            'leadstate_id'           => 6,
            'leadstate'              => 'lost',
            'leadstate_probability'  => 0,
            'leadstate_endslead'  => 1
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
                    `id` int(10) unsigned NOT NULL auto_increment,
                    `productsource` varchar(200) NOT NULL default '',
                    `price` decimal(12,2) unsigned NOT NULL default '0.00',
                    PRIMARY KEY  (`id`)
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
                    `lead_id` int(11) NOT NULL,
                    `product_id` int(11) NOT NULL,
                    `product_desc` varchar(255) default NULL,
                    `product_price` decimal(12,2) unsigned NOT NULL default '0.00',
                    PRIMARY KEY  (`lead_id`),
                    KEY `lead_id` (`lead_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8"
            );
        }
    }    
}