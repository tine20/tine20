<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  AsyncJob
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
        $lastJob = $this->getLastJob($_name);
        if ($lastJob) {
            if ($lastJob->status === Tinebase_Model_AsyncJob::STATUS_RUNNING) {
                if (Tinebase_DateTime::now()->isLater($lastJob->end_time)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Old ' . $_name . ' job is running too long. Finishing it now.');
                    $this->finishJob($lastJob, Tinebase_Model_AsyncJob::STATUS_FAILURE);
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Current job runs till ' . $lastJob->end_time->toString());
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
     * get last job of this name
     * 
     * @param string $name
     * @return Tinebase_Model_AsyncJob
     */
    public function getLastJob($name)
    {
        $filter = new Tinebase_Model_AsyncJobFilter(array(array(
            'field'     => 'name',
            'operator'  => 'equals',
            'value'     => $name
        )));
        $pagination = new Tinebase_Model_Pagination(array(
            'sort'        => 'seq',
            'dir'         => 'DESC',
            'limit'       => 1,
        ));
        $jobs = $this->_backend->search($filter, $pagination);
        $lastJob = $jobs->getFirstRecord();
        return $lastJob;
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
                . ' Could not start job ' . $_name . ': ' . $zdse);
        }
        
        return $result;
    }
    
    /**
     * create new job
     * 
     * @param string $_name
     * @param integer $_sequence
     * @param int $timeout
     * @return Tinebase_Model_AsyncJob
     */
    protected function _createNewJob($_name, $_sequence, $_timeout)
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
            'seq'               => $_sequence
        ));
        
        return $this->_backend->create($job);
    }

    /**
     * only keep the last 60 jobs and purge all other
     * 
     * @param Tinebase_Model_AsyncJob $job
     */
    protected function _purgeOldJobs(Tinebase_Model_AsyncJob $job)
    {
        $deleteBefore = $job->seq - 60;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Purging old Jobs before sequence: ' . $deleteBefore);
        
        // avoid overloading by deleting old jobs in batches only
        $idsToDelete = $this->_backend->search(new Tinebase_Model_AsyncJobFilter(array(
            array(
                'field'    => 'seq',
                'operator' => 'less',
                'value'    => $deleteBefore
            )
        )), new Tinebase_Model_Pagination(array('limit' => 10000)), true);
        
        $this->_backend->delete($idsToDelete);
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
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Finishing job ' . $_asyncJob->name . ' with status ' . $_status);
        
        $this->_purgeOldJobs($_asyncJob);
        
        $_asyncJob->end_time = Tinebase_DateTime::now();
        $_asyncJob->status = $_status;
        if ($_message !== NULL) {
            $_asyncJob->message = $_message;
        }
        
        $result = $this->_backend->update($_asyncJob);
        
        return $result;
    }
}
