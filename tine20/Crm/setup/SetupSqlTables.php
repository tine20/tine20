<?php
/**
 * egroupware 2.0
 * 
 * @package     CRM
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>, Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Sql.php 199 2008-01-15 15:10:04Z twadewitz $ 
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
     * Creates CRM 2.0 tables
     * @return void
     */
    public static function createCrmTables() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable('egw_metacrm_project');
        } catch (Zend_Db_Statement_Exception $e) {
            $db->getConnection()->exec("CREATE TABLE `egw_metacrm_leadsource` (
                  `lead_leadsource_id` int(11) NOT NULL auto_increment,
                  `lead_leadsource` varchar(255) default NULL,
                  `lead_leadsource_translate` tinyint(4) default '1',
                  PRIMARY KEY  (`lead_leadsource_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            );

            $db->getConnection()->exec("CREATE TABLE `egw_metacrm_leadtype` (
                  `lead_leadtype_id` int(11) NOT NULL auto_increment,
                  `lead_leadtype` varchar(255) default NULL,
                  `lead_leadtype_translate` tinyint(4) default '1',
                  PRIMARY KEY  (`lead_leadtype_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            );            

            $db->getConnection()->exec("CREATE TABLE `egw_metacrm_product` (
                  `lead_id` int(11) NOT NULL auto_increment,
                  `lead_project_id` int(11) NOT NULL default '0',
                  `lead_product_id` int(11) NOT NULL default '0',
                  `lead_product_desc` varchar(255) default NULL,
                  `lead_product_price` decimal(12,2) unsigned NOT NULL default '0.00',
                  PRIMARY KEY  (`lead_id`),
                  KEY `lead_id` (`lead_id`),
                  KEY `lead_project_id` (`lead_project_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;"
            );            
            
            $db->getConnection()->exec("CREATE TABLE `egw_metacrm_productsource` (
                  `lead_productsource_id` int(10) unsigned NOT NULL auto_increment,
                  `lead_productsource` varchar(200) NOT NULL default '',
                  `lead_productsource_price` decimal(12,2) unsigned NOT NULL default '0.00',
                  PRIMARY KEY  (`lead_productsource_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;"
            );            
            
            $db->getConnection()->exec("CREATE TABLE `egw_metacrm_project` (
                  `lead_id` int(11) NOT NULL auto_increment,
                  `lead_name` varchar(255) NOT NULL default '',
                  `lead_leadstate_id` int(11) NOT NULL default '0',
                  `lead_leadtype_id` int(11) NOT NULL default '0',
                  `lead_leadsource_id` int(11) NOT NULL default '0',
                  `lead_container` int(11) NOT NULL default '0',
                  `lead_modifier` int(11) default NULL,
                  `lead_start` int(11) NOT NULL default '0',
                  `lead_modified` int(11) NOT NULL default '0',
                  `lead_created` int(11) unsigned NOT NULL default '0',
                  `lead_description` text,
                  `lead_end` int(11) default NULL,
                  `lead_turnover` double default NULL,
                  `lead_probability` decimal(3,0) default NULL,
                  `lead_end_scheduled` int(11) NOT NULL default '0',
                  `lead_lastread` int(11) NOT NULL default '0',
                  `lead_lastreader` int(11) NOT NULL default '0',
                  PRIMARY KEY  (`lead_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;"
            );            
            
            $db->getConnection()->exec("CREATE TABLE `egw_metacrm_leadstate` (
                  `lead_leadstate_id` int(11) NOT NULL auto_increment,
                  `lead_leadstate` varchar(255) default NULL,
                  `lead_leadstate_probability` tinyint(3) unsigned NOT NULL default '0',
                  `lead_leadstate_endsproject` tinyint(1) NOT NULL default '0'
                  `lead_leadstate_translate` tinyint(4) default '1',
                  PRIMARY KEY  (`lead_leadstate_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;"
            );                                                
        }
    }
    
    /**
     * inserts default records
     * @return void
     */
    public static function insertDefaultRecords() {
        $db = Zend_Registry::get('dbAdapter');
        $accountId = Zend_Registry::get('currentAccount')->account_id;
        $now = $db->quote(Zend_Date::now()->getIso());
        
        // TODO: should curren options be default entries?
/*        $db->getConnection()->exec("INSERT INTO `egw_class` (
            `created_by`, `creation_time`, `class` ) VALUES (
            $accountId, $now, 'PUBLIC')"
        );
  */      
    }
    
}