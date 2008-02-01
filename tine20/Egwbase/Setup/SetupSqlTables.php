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
class Egwbase_Setup_SetupSqlTables
{    
    /**
     * temporary function to create the egw_metacrm_lead table on demand
     *
     */
    public static function createAccessLogTable() {
        $db = Zend_Registry::get('dbAdapter');
        
        try {
            $tableData = $db->describeTable(SQL_TABLE_PREFIX . 'access_log');
        } catch (Zend_Db_Statement_Exception $e) {
            // table does not exist
            $result = $db->getConnection()->exec("CREATE TABLE `" . SQL_TABLE_PREFIX . "access_log` (
                      `sessionid` varchar(128) NOT NULL default '',
                      `loginid` varchar(64) NOT NULL default '',
                      `ip` varchar(40) NOT NULL default '',
                      `li` int(11) NOT NULL,
                      `lo` int(11) default '0',
                      `account_id` int(11) NOT NULL default '0',
                      `log_id` int(10) unsigned NOT NULL auto_increment,
                      `result` int(11) NOT NULL default '0',
                      PRIMARY KEY  (`log_id`)
                    ) ENGINE=Innodb DEFAULT CHARSET=utf8"
            );
        }
    }
 }