<?php
/**
 * Tine 2.0
 * 
 * @package     Setup
 * @subpackage  Update
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
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
     * @param string $_applicationName
     * @param string $_version new version number
     * @return Tinebase_Model_Application
     */    
    public function setApplicationVersion($_applicationName, $_version)
    {
        $application = Tinebase_Application::getInstance()->getApplicationByName($_applicationName);
        $application->version = $_version;
        
        return Tinebase_Application::getInstance()->updateApplication($application);
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
        
        $result = (count($rows) > 0 && isset($rows[0]['version'])) ? $rows[0]['version'] : 0;
        
        return $result;
    }
    
    /**
     * set version number of a given table
     * version is stored in database table "applications_tables"
     *
     * @param string tableName
     * @param int|string $_version
     * @param boolean $_createIfNotExist
     * @param string $_application
     * @return void
     * @throws Setup_Exception_NotFound
     */     
    public function setTableVersion($_tableName, $_version, $_createIfNotExist = TRUE, $_application = 'Tinebase')
    {
        if ($this->getTableVersion($_tableName) == 0) {
            if ($_createIfNotExist) {
                Tinebase_Application::getInstance()->addApplicationTable(
                    Tinebase_Application::getInstance()->getApplicationByName($_application), 
                    $_tableName,
                    $_version
                );
            } else {
                throw new Setup_Exception_NotFound('Table ' . $_tableName . ' not found in application tables or previous version number invalid.');
            }
        } else {
            $applicationsTables = new Tinebase_Db_Table(array('name' =>  SQL_TABLE_PREFIX . 'application_tables'));
            $where  = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', $_tableName),
            );
            $result = $applicationsTables->update(array('version' => $_version), $where);
        }
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
    public function createTable($_tableName, Setup_Backend_Schema_Table_Abstract $_table, $_application = 'Tinebase', $_version = 1)
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName($_application);
        Tinebase_Application::getInstance()->removeApplicationTable($app, $_tableName);
        
        $this->_backend->createTable($_table);
        
        Tinebase_Application::getInstance()->addApplicationTable($app, $_tableName, $_version);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Created new table ' . $_tableName);
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
     * @param string $_application
     */  
    public function dropTable($_tableName, $_application = 'Tinebase')
    {
        Tinebase_Application::getInstance()->removeApplicationTable(Tinebase_Application::getInstance()->getApplicationByName($_application), $_tableName);
        $result = $this->_backend->dropTable($_tableName);
    }
}
