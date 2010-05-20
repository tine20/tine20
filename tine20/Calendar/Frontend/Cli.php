<?php
/**
 * Tine 2.0
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * Cli frontend for Calendar
 *
 * This class handles cli requests for the Calendar
 *
 * @package     Calendar
 */
class Calendar_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
{
    /**
     * the internal name of the application
     * 
     * @var string
     */
    protected $_applicationName = 'Calendar';
    
    /**
     * help array with function names and param descriptions
     * 
     * @return void
     */
    protected $_help = array(
        'importegw14' => array(
            'description'   => 'imports calendars/events from egw 1.4',
            'params'        => array(
                'host'     => 'dbhost',
                'username' => 'username',
                'password' => 'password',
                'dbname'   => 'dbname'
            )
        ),
    );
    
    public function importegw14($_opts) {
        //$args = $_opts->getRemainingArgs();
        list($host, $username, $password, $dbname, $charset) = $_opts->getRemainingArgs();
        
        $egwDb = Zend_Db::factory('PDO_MYSQL', array(
            'host'     => $host,
            'username' => $username,
            'password' => $password,
            'dbname'   => $dbname
        ));
        $egwDb->query("SET NAMES $charset");
        
        $writer = new Zend_Log_Writer_Stream('php://output');
        $logger = new Zend_Log($writer);

        
        $config = new Zend_Config(array(
            /**
             * egw stores its events in server timezone
             */
            'egwServerTimezone'           => 'UTC',
            /**
             * convert egw owner grants to tine container 
             * grants for newly created calendars
             */ 
            'setPersonalCalendarGrants'   => TRUE,
            /**
             * forece converting grants regardless if calendars are new or not
             */ 
            'forcePersonalCalendarGrants' => FALSE,
        ));
        
        $importer = new Calendar_Setup_Import_Egw14($egwDb, $config, $logger);
        $importer->import();
    }
}