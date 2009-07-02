<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Preference.php 7161 2009-03-04 14:27:07Z p.schuele@metaways.de $
 * 
 * @todo        make this a real controller + singleton (create extra sql backend)
 */

/**
 * backend for async event management
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
class Tinebase_AsyncJob extends Tinebase_Backend_Sql_Abstract
{
    /**************************** backend settings *********************************/
    
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'async_job';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_AsyncJob';
    
    /**************************** public functions *********************************/
    
    /**
     * check if job is running
     *
     * @param string $_name
     * @return boolean
     * 
     * @todo check if job is running for a long time -> set status to Tinebase_Model_AsyncJob::STATUS_FAILURE
     */
    public function jobIsRunning($_name)
    {
        $select = $this->_db->select();
        $select->from(array($this->_tableName => $this->_tablePrefix . $this->_tableName))
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('name') . ' = ?', $_name))
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('status') . ' = ?', Tinebase_Model_AsyncJob::STATUS_RUNNING));
        
        $stmt = $this->_db->query($select);
        $rows = (array)$stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $result = (count($rows) > 0);
        return $result;
    }

    /**
     * start new job
     *
     * @param string $_name
     * @return Tinebase_Model_AsyncJob
     */
    public function startJob($_name)
    {
        try {
            $db = $this->_db;
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            $job = new Tinebase_Model_AsyncJob(array(
                'name'              => $_name,
                'start_time'        => Zend_Date::now(),
                'status'            => Tinebase_Model_AsyncJob::STATUS_RUNNING
            ));
            $result = $this->create($job);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
        
        return $result;
    }

    /**
     * finish job
     *
     * @param Tinebase_Model_AsyncJob $_asyncJob
     * @return Tinebase_Model_AsyncJob
     */
    public function finishJob(Tinebase_Model_AsyncJob $_asyncJob)
    {
        try {
            $db = $this->_db;
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            $_asyncJob->end_time = Zend_Date::now();
            $_asyncJob->status = Tinebase_Model_AsyncJob::STATUS_SUCCESS;
            $result = $this->update($_asyncJob);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
        
        return $result;
    }
}
