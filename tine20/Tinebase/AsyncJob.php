<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  AsyncJob
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @var Tinebase_Backend_Sql
     */
    protected $_backend;
    
    /**
     * default seconds till job is declared 'failed'
     *
     */    
    const SECONDS_TILL_FAILURE = 300;
    
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
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_AsyncJob', 
            'tableName' => 'async_job',
        ));
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
     * check if job is running and returns next sequence / FALSE if a job is running atm
     *
     * @param string $_name
     * @return boolean|integer
     */
    public function getNextSequence($_name)
    {
        // get last job of this name
        $filter = new Tinebase_Model_AsyncJobFilter(array(array(
            'field'     => 'name',
            'operator'  => 'equals',
            'value'     => $_name
        )));
        $pagination = new Tinebase_Model_Pagination(array(
            'sort'		=> 'start_time',
            'dir'		=> 'DESC',
            'limit'		=> 1,
        ));
        $jobs = $this->_backend->search($filter, $pagination);
        $lastJob = $jobs->getFirstRecord();
        
        if ($lastJob) {
            if ($lastJob->status === Tinebase_Model_AsyncJob::STATUS_RUNNING) {
                if (Tinebase_DateTime::now()->isLater($lastJob->end_time)) {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Old ' . $_name . ' job is running too long. Finishing it now.');
                    $this->finishJob($lastJob, Tinebase_Model_AsyncJob::STATUS_FAILURE);
                }
                $result = FALSE;
            } else {
                $result = $lastJob->seq + 1;
            }
        } else {
            // begin new sequence
            $result = 1;
        }
        
        return $result;
    }

    /**
     * start new job / check fencing
     *
     * @param string $_name
     * @param int $timeout
     * @return NULL|Tinebase_Model_AsyncJob
     */
    public function startJob($_name, $_timeout = self::SECONDS_TILL_FAILURE)
    {
        $result = NULL;
        
        $nextSequence = $this->getNextSequence($_name);
        if ($nextSequence === FALSE) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Job ' . $_name . ' is already running. Skipping ...');
            return $result;
        }
        
        try {
            $result = $this->_createNewJob($_name, $nextSequence, $_timeout);
        } catch (Zend_Db_Statement_Exception $zdse) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Could not start job ' . $_name . ': ' . $zdse->getMessage());
        }
        
        return $result;
    }
    
    /**
     * create new job
     * 
     * @param string $_name
     * @param integer $_maxSeq
     * @param int $timeout
     * @return Tinebase_Model_AsyncJob
     */
    protected function _createNewJob($_name, $_maxSeq, $_timeout)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Creating new Job ' . $_name);
                
        $date = new Tinebase_DateTime();
        $date->addSecond($_timeout);            
        
        $job = new Tinebase_Model_AsyncJob(array(
            'name'              => $_name,
            'start_time'        => new Tinebase_DateTime(),
            'end_time'          => $date,
            'status'            => Tinebase_Model_AsyncJob::STATUS_RUNNING,
            'seq'               => $_maxSeq + 1
        ));
        
        return $this->_backend->create($job);
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
            
            $_asyncJob->end_time = Tinebase_DateTime::now();
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
