<?php
/**
 * egroupware 2.0
 * 
 * @package     CRM
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>, Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: $
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
    public static function createTasksTables() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable('egw_metacrm_project');
        } catch (Zend_Db_Statement_Exception $e) {
            $db->getConnection()->exec("CREATE TABLE `egw_metacrm_leadsource` (
                  `pj_leadsource_id` int(11) NOT NULL auto_increment,
                  `pj_leadsource` varchar(255) default NULL,
                  `pj_leadsource_translate` tinyint(4) default '1',
                  PRIMARY KEY  (`pj_leadsource_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            );

            $db->getConnection()->exec("CREATE TABLE `egw_metacrm_leadtype` (
                  `pj_leadtype_id` int(11) NOT NULL auto_increment,
                  `pj_leadtype` varchar(255) default NULL,
                  `pj_leadtype_translate` tinyint(4) default '1',
                  PRIMARY KEY  (`pj_leadtype_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            );            

            $db->getConnection()->exec("CREATE TABLE `egw_metacrm_product` (
                  `pj_id` int(11) NOT NULL auto_increment,
                  `pj_project_id` int(11) NOT NULL default '0',
                  `pj_product_id` int(11) NOT NULL default '0',
                  `pj_product_desc` varchar(255) default NULL,
                  `pj_product_price` decimal(12,2) unsigned NOT NULL default '0.00',
                  PRIMARY KEY  (`pj_id`),
                  KEY `pj_id` (`pj_id`),
                  KEY `pj_project_id` (`pj_project_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;"
            );            
            
            $db->getConnection()->exec("CREATE TABLE `egw_metacrm_productsource` (
                  `pj_productsource_id` int(10) unsigned NOT NULL auto_increment,
                  `pj_productsource` varchar(200) NOT NULL default '',
                  PRIMARY KEY  (`pj_productsource_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;"
            );            
            
            $db->getConnection()->exec("CREATE TABLE `egw_metacrm_project` (
                  `pj_id` int(11) NOT NULL auto_increment,
                  `pj_name` varchar(255) NOT NULL default '',
                  `pj_distributionphase_id` int(11) NOT NULL default '0',
                  `pj_customertype_id` int(11) NOT NULL default '0',
                  `pj_leadsource_id` int(11) NOT NULL default '0',
                  `pj_owner` int(11) NOT NULL default '0',
                  `pj_modifier` int(11) default NULL,
                  `pj_start` int(11) NOT NULL default '0',
                  `pj_modified` int(11) NOT NULL default '0',
                  `pj_created` int(11) unsigned NOT NULL default '0',
                  `pj_description` text,
                  `pj_end` int(11) default NULL,
                  `pj_turnover` double default NULL,
                  `pj_probability` decimal(3,0) default NULL,
                  `pj_end_scheduled` int(11) NOT NULL default '0',
                  `pj_lastread` int(11) NOT NULL default '0',
                  `pj_lastreader` int(11) NOT NULL default '0',
                  PRIMARY KEY  (`pj_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;"
            );            
            
            $db->getConnection()->exec("CREATE TABLE `egw_metacrm_projectstate` (
                  `pj_projectstate_id` int(11) NOT NULL auto_increment,
                  `pj_projectstate` varchar(255) default NULL,
                  `pj_projectstate_translate` tinyint(4) default '1',
                  PRIMARY KEY  (`pj_projectstate_id`)
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