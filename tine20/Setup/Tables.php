<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * class to handle setup of Tine 2.0
 *
 * @package     Setup
 */
class Setup_Tables
{
    private $_backend;
    private $_config;

    public function __construct()
    {
        try {
            $this->_config = new Zend_Config_Ini($_SERVER['DOCUMENT_ROOT'] . '/../config.ini');
        } catch (Zend_Config_Exception $e) {
            die ('central configuration file ' . $_SERVER['DOCUMENT_ROOT'] . '/../config.ini not found');
        }

        $this->setupLogger();
        $this->setupDatabaseConnection();
		
		switch ($this->_config->database->database)
		{
			case('mysql'):
			{
				$this->_backend = new Setup_Backend_Mysql();
				break;
			}
			
			default:
			{
				echo "you have to define a dbms = yourdbms (like mysql) in your config.ini file";
			}
		}
		
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

            echo "setting table prefix to: " . SQL_TABLE_PREFIX . " <hr>";

            $db = Zend_Db::factory('PDO_MYSQL', $dbConfig->toArray());
            Zend_Db_Table_Abstract::setDefaultAdapter($db);

            Zend_Registry::set('dbAdapter', $db);
        } else {
            die ('database section not found in central configuration file');
        }
    }

	public function applicationExists($_application)
	{
		if($this->tableExists(SQL_TABLE_PREFIX . 'applications'))
		{
			if($this->applicationVersionQuery($_application) != false)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
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
		
		if (false == $this->applicationExists($xml->name)) 
		{
			// just insert tables
			if(isset($xml->tables)) 
			{
				foreach ($xml->tables[0] as $table) 
				{
					if (false == $this->tableExists(SQL_TABLE_PREFIX . $table->name)) 
					{
						$this->_backend->_createTable($table);
						$createdTables[] = $table;
					}
				}
			}
		}
		else
		{
		
			switch($this->applicationNeedsUpdate($xml->name, $xml->version)) 
			{
				case (-1):
				{
					$this->applicationUpdate($xml->name, $xml->version);
					
					echo "<strong>" . $xml->name . " had different specifications. Now up-to-date to version " .  $xml->version . "</strong>";
					break; 
				}
				case (0):
				{
					echo "<i>" . $xml->name . " no changes " . $xml->version . "</i>";
					break;
				}
				case(1):
				{
					echo "<span style=color:#ff0000>database schema newer as your new definition - giving up</span>";
					break;
				}
			}
		
		}
		
		/*
        if(isset($xml->tables)) {
            foreach ($xml->tables[0] as $table) {
				$tableName = SQL_TABLE_PREFIX . $table->name;
                if(!$this->_backend->tableExists($this->_config->database->dbname, $tableName)) 
				{
					$this->_backend->createTable($table);
                    $createdTables[] = $table;
                    }
                    else
                    {
					echo "{$tableName} . Table exists already.<br>";
					//if(!$this->_backend->tableCheck($this->_config->database->dbname, $tableName, $table)) {
					//	$this->_backend->alterTable($this->_config->database->dbname, $tableName, $table);
					//	$createdTables[] = $table;
					//
					//	echo $tableName . " had different specifications. Now up-to-date to version " . $table->version;
					//}
				}
            }
        }
*/
        try {
            $application = Tinebase_Application::getInstance()->getApplicationByName($xml->name);
        } catch (Exception $e) {
            $application = new Tinebase_Model_Application(array(
                'name'      => $xml->name,
                'status'    => Tinebase_Application::ENABLED,
                'order'     => $xml->order ? $xml->order : 99,
                'version'   => $xml->version
            ));

            $application = Tinebase_Application::getInstance()->addApplication($application);
        }

        foreach($createdTables as $table) {
            $this->addTable($application, SQL_TABLE_PREFIX . $table->name, $table->version);
        }

        if(isset($xml->defaultRecords)) {
            foreach ($xml->defaultRecords[0] as $record) {
                $this->_backend->execInsertStatement($record);
            }
        }
    }



    /**
     * check's if a given table exists
     *
     * @param string $_tableSchema
     * @param string $_tableName
     * @return boolean return true if the table exists, otherwise false
     */
    public function tableExists($_tableName)
    {
	 	$select = Zend_Registry::get('dbAdapter')->select()
          ->from('information_schema.tables')
		  ->where('TABLE_SCHEMA = ?', $this->_config->database->dbname)
          ->where('TABLE_NAME = ?', $_tableName);

        $stmt = $select->query();

        $table = $stmt->fetchObject();
 

		if($table === false) {
          return false;
        }

        return true; 
    }
	
	/**
     * check's a given database table version 
     *
     * @param string $_tableName
     * @return boolean return string "version" if the table exists, otherwise false
     */
    
	public function tableVersionQuery($_tableName)
	{
		$select = Zend_Registry::get('dbAdapter')->select()
				->from( SQL_TABLE_PREFIX . 'application_tables')
				->where('name = ?', $_tableName);

        $stmt = $select->query();
		$version = $stmt->fetchAll();
		
		return $version[0]['version'];
	}
	/**
     * check's a given application version
     *
     * @param string $_application
     * @return boolean return string "version" if the table exists, otherwise false
     */
    
	public function applicationVersionQuery($_application)
	{	
		$select = Zend_Registry::get('dbAdapter')->select()
				->from( SQL_TABLE_PREFIX . 'applications')
				->where('name = ?', $_application);

        $stmt = $select->query();
		$version = $stmt->fetchAll();
		if(empty($version))
		{
			return false;
		}
		else
		{
			return $version[0]['version'];
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

	/**
     * update database tables via including the right files
     *
     * @param string $_xmlVersion (numbers read from xml-file)
	 * @param string $_application (name of updateable application)
	 * @param string $_tableName (name of database table)
	 * @return true
     */
	 
	 /*
	 hirnknoten: update ich eine tabelle oder eine application ... 
	 
	 */
	 
	public function tableUpdate($_xmlVersion, $_application, $_tableName)
	{
		$tableVersion = $this->tableVersionQuery($_tableName);
		
		$noGoArea = array('.', '..', 'setup.xml');
		
		$path = $_application . "/Setup/";
		
		foreach ( new DirectoryIterator( $path ) as $item ) 
		{
			if(! in_array($item->getFileName(), $noGoArea)) {
				$fileBuffer[] = $item->getPathname();
			}
		}
		
		sort($fileBuffer);
	
		foreach ($fileBuffer as $file)
		{
			$fileVersion = substr($file, (strpos($file, 'up-') + 3), (strlen($file) - 4 - (strpos($file, 'up-') + 3)));
			if ((version_compare($fileVersion, $tableVersion) == 1) && ((version_compare($_xmlVersion, $fileVersion ) == 0 || version_compare($_xmlVersion, $fileVersion) == 1)))
			{
				try
				{
					include ($file);
				}
				catch (Exception $e)
				{
					echo $e->getMessage();
				}
			}
		}
		exit;
		
	}
	
	public function applicationUpdate($_name, $_version)
	{
		$number = explode('.', $_version);
		$className = $_name . '_Setup_Update_' . $number[0] . '_' . $number[1];
		$update = new $className($this->_backend, $_name);
		
		$update->make();
	}
	
	
    public function execInsertStatement($_record)
    {
        $table = new Tinebase_Db_Table(array(
           'name' => SQL_TABLE_PREFIX . $_record->table->name
        ));

        foreach ($_record->field as $field) {
            if(isset($field->value['special'])) {
                switch(strtolower($field->value['special'])) {
                    case 'now':
                    {
                        $value = Zend_Date::now()->getIso();
                        break;
                    }
                    case 'account_id':
                    {
                        break;
                    }
                    case 'application_id':
                    {
                        $application = Tinebase_Application::getInstance()->getApplicationByName($field->value);

                        $value = $application->id;

                        break;
                    }
                    default:
                    {
                        throw new Exception('unsupported special type ' . strtolower($field->value['special']));
                        break;
                    }
                }
            } else {
                $value = $field->value;
            }

            $data[(string)$field->name] = $value;
        }

        $table->insert($data);
    }

    public function addTable(Tinebase_Model_Application $_application, $_name, $_version)
    {
        $applicationTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'application_tables'));

        $applicationData = array(
            'application_id'    => $_application->id,
            'name'              => $_name,
            'version'           => $_version
        );

        $applicationID = $applicationTable->insert($applicationData);

        return $applicationID;
    }
}
