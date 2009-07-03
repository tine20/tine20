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
     * minutes till job is declared 'failed'
     *
     */
    const MINUTES_TILL_FAILURE = 10;
    
    /**
     * holds the instance of the singleton
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
     */
    public function jobIsRunning($_name)
    {
        // get all pending alarms
        $filter = new Tinebase_Model_AsyncJobFilter(array(
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
        $jobs = $this->_backend->search($filter);
        
        $result = (count($jobs) > 0);
        
        // check if job is running for a long time -> set status to Tinebase_Model_AsyncJob::STATUS_FAILURE
        if ($result) {
            $job = $jobs->getFirstRecord();
            
            if ($job->start_time->compare(Zend_Date::now()->subMinute(self::MINUTES_TILL_FAILURE)) == -1) {
                // it seems that the old job ended (start time is older than MINUTES_TILL_FAILURE mins) -> start a new one
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Old ' . $_name . ' job is running too long. Finishing it now.');
                
                $this->finishJob($job, Tinebase_Model_AsyncJob::STATUS_FAILURE);
                $result = FALSE;
            }
        }
        
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
     * @param string $_status
     * @param string $_message
     * @return Tinebase_Model_AsyncJob
     */
    public function finishJob(Tinebase_Model_AsyncJob $_asyncJob, $_status = Tinebase_Model_AsyncJob::STATUS_SUCCESS, $_message = NULL)
    {
        try {
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            $_asyncJob->end_time = Zend_Date::now();
            $_asyncJob->status = $_status;
            if ($_message !== NULL) {
                $_asyncJob->message = $_message;
            }
            
            $result = $this->_backend->update($_asyncJob);
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            Tinebase_TransactionManager::getInstance()->rollBack();
            throw $e;
        }
        
        return $result;
    }
}
