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
	* the constructor
	*/
	public function __construct($_backend)
	{
	    $this->_backend = $_backend;
	}
	
	/*
	 * get version number of a given application 
	 * version is stored in database table "applications"
	 *
	 * @params string application
	 * @returns string version number major.minor release 
	 */
	public function getApplicationVersion($_application)
	{
		$select = Zend_Registry::get('dbAdapter')->select()
				->from( SQL_TABLE_PREFIX . 'applications')
				->where('name = ?', $_application);

		$stmt = $select->query();
		$version = $stmt->fetchAll();
		
		return $version[0]['version'];
	}

	/*
	 * set version number of a given application 
	 * version is stored in database table "applications"
	 *
	 * @params string application
	 * @params int new version number
	 */	
	public function setApplicationVersion($_application, $_version)
	{
		$applicationsTable = new Tinebase_Db_Table(array('name' =>  SQL_TABLE_PREFIX . 'applications'));
		$where  = array(
                    $applicationsTable->getAdapter()->quoteInto('name = ?', $_application),
                );
		$applicationsTable->update(array('version' => $_version), $where);
	}
	
	/*
	 * get version number of a given table
	 * version is stored in database table "applications_tables"
	 *
	 * @params Tinebase_Application application
	 * @returns int version number 
	 */
	public function getTableVersion($_tableName)
    {
        $select = Zend_Registry::get('dbAdapter')->select()
                ->from( SQL_TABLE_PREFIX . 'application_tables')
                ->where('name = ?', SQL_TABLE_PREFIX . $_tableName);

        $stmt = $select->query();
        $rows = $stmt->fetchAll();
        
        return $rows[0]['version'];
    }
	
	/*
	 * set version number of a given table
	 * version is stored in database table "applications_tables"
	 *
	 * @params string tableName
	 * @returns int version number 
	 */	 
    public function setTableVersion($_tableName, $_version)
    {
        $applicationsTables = new Tinebase_Db_Table(array('name' =>  SQL_TABLE_PREFIX . 'application_tables'));
        $where  = array(
                    $applicationsTables->getAdapter()->quoteInto('name = ?', SQL_TABLE_PREFIX . $_tableName),
                );
        $result = $applicationsTables->update(array('version' => $_version), $where);
    }
    
	/*
	 * compares version numbers of given table and given number
	 *
	 * @params string tableName
	 * @params int version number
	 */	 
    public function validateTableVersion($_tableName, $_version)
    {
        $currentVersion = $this->getTableVersion($_tableName);
        if($_version != $currentVersion) {
            throw new Exception("wrong table version for $_tableName. expected $_version go $currentVersion");
        }
    }
}