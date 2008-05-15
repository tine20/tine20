<?php
/**
 * Tine 2.0
 * 
 * @package     Setup
 * @subpackage  Update
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
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
	    $this->_db = Zend_Registry::get('dbAdapter');
	}
	
	/*
	 * get version number of a given application 
	 * version is stored in database table "applications"
	 *
	 * @param string application
	 * @returns string version number major.minor release 
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

	/*
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
	
	/*
	 * get version number of a given table
	 * version is stored in database table "applications_tables"
	 *
	 * @param Tinebase_Application application
	 * @returns int version number 
	 */
	public function getTableVersion($_tableName)
    {
        $select = $this->_db->select()
                ->from( SQL_TABLE_PREFIX . 'application_tables')
                ->where($this->_db->quoteIdentifier('name') . ' = ?', SQL_TABLE_PREFIX . $_tableName);

        $stmt = $select->query();
        $rows = $stmt->fetchAll();
        
        $result = ( isset($rows[0]['version']) ) ? $rows[0]['version'] : 0; 
        
        return $result;
    }
	
	/*
	 * set version number of a given table
	 * version is stored in database table "applications_tables"
	 *
	 * @param string tableName
	 * @returns int version number 
	 */	 
    public function setTableVersion($_tableName, $_version)
    {
        $applicationsTables = new Tinebase_Db_Table(array('name' =>  SQL_TABLE_PREFIX . 'application_tables'));
        $where  = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', SQL_TABLE_PREFIX . $_tableName),
        );
        $result = $applicationsTables->update(array('version' => $_version), $where);
    }
    
    /*
     * set version number of a given table
     * version is stored in database table "applications_tables"
     *
     * @param string tableName
     * @returns int version number 
     */  
    public function increaseTableVersion($_tableName)
    {
        $currentVersion = $this->getTableVersion($_tableName);

        $version = ++$currentVersion;
        
        $applicationsTables = new Tinebase_Db_Table(array('name' =>  SQL_TABLE_PREFIX . 'application_tables'));
        $where  = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', SQL_TABLE_PREFIX . $_tableName),
        );
        $result = $applicationsTables->update(array('version' => $version), $where);
    }
    
    /*
	 * compares version numbers of given table and given number
	 *
	 * @param string $_tableName
	 * @param int version number
	 */	 
    public function validateTableVersion($_tableName, $_version)
    {
        $currentVersion = $this->getTableVersion($_tableName);
        if($_version != $currentVersion) {
            throw new Exception("wrong table version for $_tableName. expected $_version got $currentVersion");
        }
    }
    
    /*
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
            $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', SQL_TABLE_PREFIX . $_oldTableName),
        );
        $result = $applicationsTables->update(array('name' => SQL_TABLE_PREFIX . $_newTableName), $where);
    }
    
    /*
     * drop table
     *
     * @param string $_tableName
     */  
    public function dropTable($_tableName)
    {
        $result = $this->_backend->dropTable($_tableName);
    }
    
}