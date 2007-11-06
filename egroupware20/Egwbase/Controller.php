<?php
/**
 * the class provides functions to handle applications
 *
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

class Egwbase_Controller
{
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        Zend_Session::start();

        $this->setupLogger();

        $this->setupDatabaseConnection();

        $this->setupUserLocale();

        $this->setupUserTimezone();

        $egwBaseNamespace = new Zend_Session_Namespace('egwbase');

        if(!isset($egwBaseNamespace->jsonKey)) {
            $egwBaseNamespace->jsonKey = md5(mktime());
        }

        if(isset($egwBaseNamespace->currentAccount)) {
            Zend_Registry::set('currentAccount', $egwBaseNamespace->currentAccount);
        }

    }

    /**
     * Enter description here...
     * 
     * @todo implement json key check
     *
     */
    public function handle()
    {

        $auth = Zend_Auth::getInstance();

        if($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' && !empty($_REQUEST['method'])) {
            Zend_Registry::get('logger')->debug('is json request. method: ' . $_REQUEST['method']);
            //Json request from ExtJS

            // is it save to use the jsonKey from $_GET too???
            // can we move this to the Zend_Json_Server???
            //  if($_POST['jsonKey'] != $egwBaseNamespace->jsonKey) {
            //      error_log('wrong JSON Key sent!!!');
            //      die('wrong JSON Key sent!!!');
            //  }

            $server = new Zend_Json_Server();

            $server->setClass('Egwbase_Json', 'Egwbase');

            if($auth->hasIdentity()) {
                $server->setClass('Addressbook_Json', 'Addressbook');
                $server->setClass('Admin_Json', 'Admin');
                //$server->setClass('Asterisk_Json', 'Asterisk');
                //$server->setClass('Felamimail_Json', 'Felamimail');
                $server->setClass('Calendar_Json', 'Calendar');
            }

            $server->handle($_REQUEST);

        } else {
            Zend_Registry::get('logger')->debug('is http request. method: ' . $_REQUEST['method']);
            // HTTP request
    
            $server = new Egwbase_Http_Server();
    
            $server->setClass('Egwbase_Http', 'Egwbase');
    
            if($auth->hasIdentity()) {
                $server->setClass('Addressbook_Http', 'Addressbook');
                $server->setClass('Admin_Http', 'Admin');
                //$server->setClass('Felamimail_Http', 'Felamimail');
            }
    
            if(empty($_REQUEST['method'])) {
                if($auth->hasIdentity()) {
                    $_REQUEST['method'] = 'Egwbase.mainScreen';
                } else {
                    $_REQUEST['method'] = 'Egwbase.login';
                }
            }
    
            $server->handle($_REQUEST);
    
        }
    }
    
    
    protected function setupLogger()
    {
        $logger = new Zend_Log();
        
        try {
            $loggerConfig = new Zend_Config_Ini('../../config.ini', 'logger');
            
            $filename = $loggerConfig->filename;
            $priority = (int)$loggerConfig->priority;

            $writer = new Zend_Log_Writer_Stream($filename);
            $logger->addWriter($writer);

            $filter = new Zend_Log_Filter_Priority($priority);
            $logger->addFilter($filter);

        } catch (Exception $e) {
            $writer = new Zend_Log_Writer_Null;
            $logger->addWriter($writer);
        }

        

        //
        Zend_Registry::set('logger', $logger);

        Zend_Registry::get('logger')->debug('logger initialized');
    }
    
    protected function setupDatabaseConnection()
    {
        $dbConfig = new Zend_Config_Ini('../../config.ini', 'database');
        Zend_Registry::set('dbConfig', $dbConfig);
    
        $db = Zend_Db::factory('PDO_MYSQL', Zend_Registry::get('dbConfig')->toArray());
        Zend_Db_Table_Abstract::setDefaultAdapter($db);
        Zend_Registry::set('dbAdapter', $db);
    }
    
    protected function setupUserLocale()
    {
        $locale = new Zend_Locale();
        Zend_Registry::set('locale', $locale);
    }
    
    protected function setupUserTimezone()
    {
        Zend_Registry::set('userTimeZone', 'Europe/Berlin');
    }
}