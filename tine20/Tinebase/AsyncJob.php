<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  AsyncJob
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Preference.php 7161 2009-03-04 14:27:07Z p.schuele@metaways.de $
 * 
 */

/**
 * controller for async event management
 *
 * @package     Tinebase
 * @subpackage  AsyncJob
 */
class Tinebase_AsyncJob
{
    /**
     * @var Tinebase_AsyncJob_Backend
     */
    protected $_backend;
    
    /**
     * holdes the instance of the singleton
     *
     * @var Tinebase_AsyncJob
     */
    private static $instance = NULL;
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
        $this->_backend = new Tinebase_AsyncJob_Backend();
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_AsyncJob
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_AsyncJob();
        }
        return self::$instance;
    }
    
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
        // get all pending alarms
        $filter = new Tinebase_Model_AlarmFilter(array(
            array(
                'field'     => 'name', 
                'operator'  => 'equals', 
                'value'     => $_name
            ),
            array(
                'field'     => 'status', 
                'operator'  => 'equals', 
                'value'     => Tinebase_Model_AsyncJob::STATUS_RUNNING
            ),
        ));
        $jobCount = $this->_backend->searchCount($filter);
        
        $result = ($jobCount > 0);
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
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            $job = new Tinebase_Model_AsyncJob(array(
                'name'              => $_name,
                'start_time'        => Zend_Date::now(),
                'status'            => Tinebase_Model_AsyncJob::STATUS_RUNNING
            ));
            $result = $this->_backend->create($job);
            
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
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            $_asyncJob->end_time = Zend_Date::now();
            $_asyncJob->status = Tinebase_Model_AsyncJob::STATUS_SUCCESS;
            $result = $this->_backend->update($_asyncJob);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
        
        return $result;
    }
}
