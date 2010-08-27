<?php
/**
 * Tine 2.0
 * 
 * @package     Setup
 * @subpackage  Update
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $Id: Abstract.php 1013 2008-03-11 21:45:31Z nelius_weiss $
 */

/**
 * Common class for a Tine 2.0 Update
 * 
 * @package     Setup
 * @subpackage  Update
 */
class Setup_Update_Abstract
{
    /**
     * backend for databse handling and extended database queries
     *
     * @var Setup_Backend_Mysql
     */
	protected $_backend;
    
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
	
	/** 
	* the constructor
	*/
	public function __construct($_backend)
	{
	    $this->_backend = $_backend;
	    $this->_db = Tinebase_Core::getDb();
	}
	
	/**
	 * get version number of a given application 
	 * version is stored in database table "applications"
	 *
	 * @param string application
	 * @return string version number major.minor release 
	 */
	public function getApplicationVersion($_application)
	{
		$select = $this->_db->select()
				->from( SQL_TABLE_PREFIX . 'applications')
				->where($this->_db->quoteIdentifier('name') . ' = ?', $_application);

		$stmt = $select->query();
		$version = $stmt->fetchAll();
		
		return $version[0]['version'];
	}

	/**
	 * set version number of a given application 
	 * version is stored in database table "applications"
	 *
	 * @param string application
	 * @param int new version number
	 */	
	public function setApplicationVersion($_application, $_version)
	{
		$applicationsTable = new Tinebase_Db_Table(array('name' =>  SQL_TABLE_PREFIX . 'applications'));
		$where  = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', $_application),
        );
		$applicationsTable->update(array('version' => $_version), $where);
	}
	
	/**
	 * get version number of a given table
	 * version is stored in database table "applications_tables"
	 *
	 * @param Tinebase_Application application
	 * @return int version number 
	 */
	public function getTableVersion($_tableName)
    {
        $select = $this->_db->select()
                ->from(SQL_TABLE_PREFIX . 'application_tables')
                ->where(    $this->_db->quoteIdentifier('name') . ' = ?', $_tableName)
                ->orwhere(  $this->_db->quoteIdentifier('name') . ' = ?', SQL_TABLE_PREFIX . $_tableName);

        $stmt = $select->query();
        $rows = $stmt->fetchAll();
        
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $select->__toString());
        
        $result = ( isset($rows[0]['version']) ) ? $rows[0]['version'] : 0; 
        
        return $result;
    }
	
	/**
	 * set version number of a given table
	 * version is stored in database table "applications_tables"
	 *
	 * @param string tableName
	 * @return int version number
	 * @throws Setup_Exception_NotFound
	 */	 
    public function setTableVersion($_tableName, $_version)
    {
        if ($this->getTableVersion($_tableName) == 0) {
            throw new Setup_Exception_NotFound('Table ' . $_tableName . ' not found in applications table or previous version number invalid.');
        }
        
        $applicationsTables = new Tinebase_Db_Table(array('name' =>  SQL_TABLE_PREFIX . 'application_tables'));
        $where  = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', $_tableName),
        );
        $result = $applicationsTables->update(array('version' => $_version), $where);
    }
    
    /**
     * set version number of a given table
     * version is stored in database table "applications_tables"
     *
     * @param string tableName
     * @return int version number 
     */  
    public function increaseTableVersion($_tableName)
    {
        $currentVersion = $this->getTableVersion($_tableName);

        $version = ++$currentVersion;
        
        $applicationsTables = new Tinebase_Db_Table(array('name' =>  SQL_TABLE_PREFIX . 'application_tables'));
        $where  = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', $_tableName),
        );
        $result = $applicationsTables->update(array('version' => $version), $where);
    }
    
    /**
	 * compares version numbers of given table and given number
	 *
	 * @param  string $_tableName
	 * @param  int version number
	 * @throws Setup_Exception
	 */	 
    public function validateTableVersion($_tableName, $_version)
    {
        $currentVersion = $this->getTableVersion($_tableName);
        if($_version != $currentVersion) {
            throw new Setup_Exception("Wrong table version for $_tableName. expected $_version got $currentVersion");
        }
    }
    
    /**
     * create new table and add it to application tables
     * 
     * @param string $_tableName
     * @param Setup_Backend_Schema_Table_Abstract $_table
     * @param string $_application
     */
    public function createTable($_tableName, Setup_Backend_Schema_Table_Abstract $_table, $_application = 'Tinebase')
    {
        $this->_backend->createTable($_table);
        Tinebase_Application::getInstance()->addApplicationTable(
            Tinebase_Application::getInstance()->getApplicationByName($_application), 
            $_tableName, 
            1
        );        
    }
    
    /**
     * rename table in applications table
     *
     * @param string $_oldTableName
     * @param string $_newTableName
     */  
    public function renameTable($_oldTableName, $_newTableName)
    {
        $this->_backend->renameTable($_oldTableName, $_newTableName);   
        
        $applicationsTables = new Tinebase_Db_Table(array('name' =>  SQL_TABLE_PREFIX . 'application_tables'));
        $where  = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', $_oldTableName),
        );
        $result = $applicationsTables->update(array('name' => $_newTableName), $where);
    }
    
    /**
     * drop table
     *
     * @param string $_tableName
     */  
    public function dropTable($_tableName)
    {
        $result = $this->_backend->dropTable($_tableName);
    }
    
}