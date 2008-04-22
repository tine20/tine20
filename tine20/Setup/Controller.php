<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Tables.php 2020 2008-04-18 23:00:10Z lkneschke $
 *
 */

/**
 * class to handle setup of Tine 2.0
 *
 * @package     Setup
 */
class Setup_Controller
{
    private $_backend;
    private $_config;
	private $_update;
	
    /**
     * the contructor
     *
     */
    public function __construct()
    {
        $this->_config = Zend_Registry::get('configFile');

        $this->setupLogger();
        $this->setupDatabaseConnection();
        
        #switch ($this->_config->database->database) {
        #    case('mysql'):
                $this->_backend = new Setup_Backend_Mysql();
        #        break;
        #    
        #    default:
        #        echo "you have to define a dbms = yourdbms (like mysql) in your config.ini file";
        #}
		$this->_update = false;
		
    }

    /**
     * initializes the logger
     *
     */
    protected function setupLogger()
    {
        $logger = new Zend_Log();

        if(isset($this->_config->logger)) {
            $loggerConfig = $this->_config->logger;

            $filename = $loggerConfig->filename;
            $priority = (int)$loggerConfig->priority;

            $writer = new Zend_Log_Writer_Stream($filename);
            $logger->addWriter($writer);

            $filter = new Zend_Log_Filter_Priority($priority);
            $logger->addFilter($filter);

        } else {
            $writer = new Zend_Log_Writer_Null;
            $logger->addWriter($writer);
        }

        Zend_Registry::set('logger', $logger);

        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' logger initialized');
    }

    /**
     * initializes the database connection
     *
     */
    protected function setupDatabaseConnection()
    {
        if(isset($this->_config->database)) {
            $dbConfig = $this->_config->database;

            define('SQL_TABLE_PREFIX', $dbConfig->get('tableprefix') ? $dbConfig->get('tableprefix') : 'tine20_');

            echo "<hr>setting table prefix to: " . SQL_TABLE_PREFIX . " <hr>";

            $db = Zend_Db::factory('PDO_MYSQL', $dbConfig->toArray());
            Zend_Db_Table_Abstract::setDefaultAdapter($db);

            Zend_Registry::set('dbAdapter', $db);
        } else {
            die ('database section not found in central configuration file');
        }
    }
    
    /**
     * setup the Database - read from the XML-files
     *
     */
    public function run($_tinebaseFile = 'Tinebase/Setup/setup.xml', $_setupFilesPath = '/Setup/setup.xml')
    {
        
        if(file_exists($_tinebaseFile)) {
            echo "Processing tables definitions for <b>Tinebase</b> ($_tinebaseFile)<br>";
           $this->parseFile($_tinebaseFile);
        }
        
        foreach ( new DirectoryIterator('./') as $item ) {
            if($item->isDir() && $item->getFileName() != 'Tinebase') {
                $fileName = $item->getFileName() . $_setupFilesPath ;
                if(file_exists($fileName)) {
                    echo "Processing tables definitions for <b>" . $item->getFileName() . "</b>($fileName)<br>";
                    $this->parseFile($fileName);
                }
            }
        }
    }
    
    /**
     * fill the Database with default values and initialise admin account 
     *
     */    
    
    public function initialLoad()
    {
        if($this->_update === false) {		
				
	       echo "Creating initial user(tine20admin) and groups...<br>";
	        # or initialize the database ourself
	        # add the admin group
	        $groupsBackend = Tinebase_Group_Factory::getBackend(Tinebase_Group_Factory::SQL);

	        $adminGroup = new Tinebase_Group_Model_Group(array(
	            'name'          => 'Administrators',
	            'description'   => 'Group of administrative accounts'
	        ));
	        $adminGroup = $groupsBackend->addGroup($adminGroup);

	        # add the user group
	        $userGroup = new Tinebase_Group_Model_Group(array(
	            'name'          => 'Users',
	            'description'   => 'Group of user accounts'
	        ));
	        $userGroup = $groupsBackend->addGroup($userGroup);

	        # add the admin account
	        $accountsBackend = Tinebase_Account_Factory::getBackend(Tinebase_Account_Factory::SQL);

	        $account = new Tinebase_Account_Model_FullAccount(array(
	            'accountLoginName'      => 'tine20admin',
	            'accountStatus'         => 'enabled',
	            'accountPrimaryGroup'   => $userGroup->getId(),
	            'accountLastName'       => 'Account',
	            'accountDisplayName'    => 'Tine 2.0 Admin Account',
	            'accountFirstName'      => 'Tine 2.0 Admin'
	        ));

	        $accountsBackend->addAccount($account);

	        Zend_Registry::set('currentAccount', $account);

	        # set the password for the tine20admin account
	        Tinebase_Auth::getInstance()->setPassword('tine20admin', 'lars', 'lars');

	        # add the admin account to all groups
	        Tinebase_Group::getInstance()->addGroupMember($adminGroup, $account);
	        Tinebase_Group::getInstance()->addGroupMember($userGroup, $account);

	        # enable the applications for the user group
	        # give admin rights to the admin group for all applications
	        $applications = Tinebase_Application::getInstance()->getApplications();
	        foreach( $applications as $application) {
	            
	            //@todo    use 'right' field with const from Tinebase_Acl_Rights
	            if(strtolower($application->name) !== 'admin') {
	                // run right for user group
	                $right = new Tinebase_Acl_Model_Right(array(
	                    'application_id'    => $application->getId(),
	                    'account_id'        => $userGroup->getId(),
	                    'account_type'      => 'group',
	                    'right'             => Tinebase_Acl_Rights::RUN
	                ));
	                Tinebase_Acl_Rights::getInstance()->addRight($right);
	                
	                // run for admin group
	                $right->account_id = $adminGroup->getId();            
	                Tinebase_Acl_Rights::getInstance()->addRight($right);
	                
	                // admin for admin group
	                $right->right = Tinebase_Acl_Rights::ADMIN;            
	                Tinebase_Acl_Rights::getInstance()->addRight($right);
	                
	            } else {
	                // run right for admin group
	                $right = new Tinebase_Acl_Model_Right(array(
	                    'application_id'    => $application->getId(),
	                    'account_id'        => $adminGroup->getId(),
	                    'account_type'      => 'group',
	                    'right'             => Tinebase_Acl_Rights::RUN
	                ));
	                Tinebase_Acl_Rights::getInstance()->addRight($right);
	                
	                // admin for admin group
	                $right->right = Tinebase_Acl_Rights::ADMIN;     
	                Tinebase_Acl_Rights::getInstance()->addRight($right);            
	            }
	        }

	        # give Users group read rights to the internal addressbook
	        # give Adminstrators group read/write rights to the internal addressbook
	        $internalAddressbook = Tinebase_Container::getInstance()->getContainerByName('Addressbook', 'Internal Contacts', Tinebase_Container::TYPE_INTERNAL);
	        Tinebase_Container::getInstance()->addGrants($internalAddressbook, 'group', $userGroup, array(
	            Tinebase_Container::GRANT_READ
	        ), TRUE);
	        Tinebase_Container::getInstance()->addGrants($internalAddressbook, 'group', $adminGroup, array(
	            Tinebase_Container::GRANT_READ,
	            Tinebase_Container::GRANT_ADD,
	            Tinebase_Container::GRANT_EDIT,
	            Tinebase_Container::GRANT_DELETE,
	            Tinebase_Container::GRANT_ADMIN
	        ), TRUE);
	            
	        echo "TINE 2.0 now ready to use try <a href=\"./index.php\">TINE 2.0 Login</a>";
		}	
    }
    
    
    /**
     * parses the xml stream and creates the tables if needed
     *
     * @param string $_file path to xml file
     */
    public function parseFile($_file)
    {
        $createdTables = array();
    
        $xml = simplexml_load_file($_file);
        
        if (!$this->_backend->applicationExists($xml->name)) {
            // just insert tables
            if(isset($xml->tables)) {
                foreach ($xml->tables[0] as $table) {
                    if (false == $this->_backend->tableExists($table->name)) {
                        try {
                            $this->_backend->createTable($table);
                            $createdTables[] = $table;
                        } catch (Exception $e) {
                            echo $e->getMessage();
                        }
                    }
                }
            }
            
            try {
                $application = Tinebase_Application::getInstance()->getApplicationByName($xml->name);
            } catch (Exception $e) {
                $application = new Tinebase_Model_Application(array(
                    'name'      => $xml->name,
                    'status'    => $xml->status ? $xml->status : Tinebase_Application::ENABLED,
                    'order'     => $xml->order ? $xml->order : 99,
                    'version'   => $xml->version
                ));

                $application = Tinebase_Application::getInstance()->addApplication($application);
            }

            foreach($createdTables as $table) {
                $this->_backend->addTable($application, $table->name, $table->version);
            }

            if(isset($xml->defaultRecords)) {
                foreach ($xml->defaultRecords[0] as $record) {
                    $this->_backend->execInsertStatement($record);
                }
            }    
                
        } else {
            $application = Tinebase_Application::getInstance()->getApplicationByName($xml->name);
			$this->_update = true;
            switch(version_compare($application->version, $xml->version)) {
                case -1:
                    $this->updateApplication($xml->name, $application->version, $xml->version);
                    
                    echo "<strong>" . $xml->name . " had different specifications. Now up-to-date to version " .  $xml->version . "</strong><br>";
                    break; 
                case 0:
                    echo "<i>" . $xml->name . " no changes " . $xml->version . "</i><br>";
                    break;
                case 1:
                    echo "<span style=color:#ff0000>database schema newer as your new definition - giving up</span>";
                    break;
            }
            return 'update';
        }
    }




    /**
     * compare versions
     *
     * @param string $_tableName (database)
     * @param string $_tableVersion (xml-file)
     * @return 1,0,-1
     */
    public function applicationNeedsUpdate($_applicationName, $_applicationVersion)
    {
        return version_compare($this->applicationVersionQuery($_applicationName),  $_applicationVersion);
    }
    
    
    /**
     * compare versions
     *
     * @param string $_tableName (database)
     * @param string $_tableVersion (xml-file)
     * @return 1,0,-1
     */
    public function tableNeedsUpdate($_tableName, $_tableVersion)
    {
        return version_compare($this->tableVersionQuery($_tableName),  $_tableVersion);
    }

    public function updateApplication($_name, $_updateFrom, $_updateTo)
    {
        echo "Updating $_name from $_updateFrom to $_updateTo<br>";
        
        list($fromMajorVersion, $fromMinorVersion) = explode('.', $_updateFrom);
        list($toMajorVersion, $toMinorVersion) = explode('.', $_updateTo);

        $minor = $fromMinorVersion;
        
        for($major = $fromMajorVersion; $major <= $toMajorVersion; $major++) {
            $className = ucfirst($_name) . '_Setup_Update_Release' . $major;
            
            $update = new $className($this->_backend);
            
            $classMethods = get_class_methods($update);
            
            // we must do at least one update
            do {
                $functionName = 'update_' . $minor;
                //echo "FUNCTIONNAME: $functionName<br>";
                $update->$functionName();
                $minor++;
            } while(array_search('update_' . $minor, $classMethods) !== false);
            
            //reset minor version to 0
            $minor = 0;
        }
    }
}
