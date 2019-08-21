<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  TransactionManager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Transaction Manger for Tine 2.0
 * 
 * This is the central class, all transactions within Tine 2.0 must be handled with.
 * For each supported transactionable (backend) this class start a real transaction on 
 * the first startTransaction request.
 * 
 * Transactions of all transactionable will be commited at once when all requested transactions
 * are being commited using this class.
 * 
 * Transactions of all transactionable will be roll back when one rollBack is requested
 * using this class.
 * 
 * @package     Tinebase
 * @subpackage  TransactionManager
 */
class Tinebase_TransactionManager
{
    /**
     * @var array holds all transactionables with open transactions
     */
    protected $_openTransactionables = array();
    
    /**
     * @var array list of all open (not commited) transactions
     */
    protected $_openTransactions = array();

    /**
     * @var array list of callbacks to call just before really committing
     */
    protected $_onCommitCallbacks = array();

    /**
     * @var array list of callbacks to call just after really committing
     */
    protected $_afterCommitCallbacks = array();

    /**
     * @var array list of callbacks to call just before rollback
     */
    protected $_onRollbackCallbacks = array();

    /**
     * @var bool allow unittest to skip a roll back
     */
    protected $_unitTestForceSkipRollBack = false;

    /**
     * @var Tinebase_TransactionManager
     */
    private static $_instance = NULL;

    /**
     * @var bool
     */
    private static $_insideCallBack = false;

    /**
     * this is a state flag for the callbacks to be used internally only, do not implement a getter for it!
     * after rollback callbacks for example could reset the flag ... and why use it? code that issues a rollback
     * should always throw an exception!
     * 
     * @var bool
     */
    private static $_rollBackOccurred = false;
    
    /**
     * don't clone. Use the singleton.
     */
    private function __clone()
    {
        
    }
    
    /**
     * constructor
     */
    private function __construct()
    {
        
    }
    
    /**
     * @return Tinebase_TransactionManager
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_TransactionManager;
        }
        
        return self::$_instance;
    }
    
    /**
     * starts a transaction
     *
     * @param   mixed $_transactionable
     * @return  string transactionId
     * @throws  Tinebase_Exception_UnexpectedValue
     */
    public function startTransaction($_transactionable)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . "  startTransaction request");
        if (! in_array($_transactionable, $this->_openTransactionables)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . "  new transactionable. Starting transaction on this resource");
            if ($_transactionable instanceof Zend_Db_Adapter_Abstract) {
                $_transactionable->beginTransaction();
            } else {
                $this->rollBack();
                throw new Tinebase_Exception_UnexpectedValue('Unsupported transactionable!');
            }
            array_push($this->_openTransactionables, $_transactionable);
        }
        
        $transactionId = Tinebase_Record_Abstract::generateUID();
        array_push($this->_openTransactions, $transactionId);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . "  queued transaction with id $transactionId");
        
        return $transactionId;
    }
    
    /**
     * commits a transaction
     *
     * @param  string $_transactionId
     * @return void
     */
    public function commitTransaction($_transactionId)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . "  commitTransaction request for $_transactionId");
         $transactionIdx = array_search($_transactionId, $this->_openTransactions);
         if ($transactionIdx !== false) {
             unset($this->_openTransactions[$transactionIdx]);
         }

         // inside a pre commit callback we don't want to really commit, as this will happen after and outside
         // the callback
         if (static::$_insideCallBack) {
             return;
         }

         $numOpenTransactions = count($this->_openTransactions);
         if ($numOpenTransactions === 0) {
             if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . "  no more open transactions in queue commiting all transactionables");

             // avoid loop backs. The callback may trigger a new transaction + commit/rollback...
             $callbacks = $this->_onCommitCallbacks;
             $afterCallbacks = $this->_afterCommitCallbacks;
             $this->_onCommitCallbacks = array();
             $this->_afterCommitCallbacks = array();

             static::$_rollBackOccurred = false;
             try {
                 static::$_insideCallBack = true;
                 foreach ($callbacks as $callable) {
                     call_user_func_array($callable[0], $callable[1]);
                     // if a rollback happened we don't want to continue (the rollback method cleanup already)
                     // it would be better if the code issuing a rollback throws an exception anyway
                     if (static::$_rollBackOccurred) {
                         return;
                     }
                 }
             } finally {
                 static::$_insideCallBack = false;
             }

             foreach ($this->_openTransactionables as $transactionable) {
                 if ($transactionable instanceof Zend_Db_Adapter_Abstract) {
                     $transactionable->commit();
                 }
             }
             // prevent call back issues. The callback may start and commit/rollback more transactions
             $this->_openTransactionables = array();
             $this->_onRollbackCallbacks = array();

             foreach($afterCallbacks as $callable) {
                 try {
                     call_user_func_array($callable[0], $callable[1]);
                 } catch (Exception $e) {
                     // we don't want to fail after we commited. Otherwise a rollback maybe triggered outside which
                     // actually can't rollback anything anymore as we already commited.
                     // So afterCommitCallbacks will fail "silently", they only log and go to sentry
                     Tinebase_Exception::log($e, false);
                 }
             }


         } else {
             if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . "  commiting defered, as there are still $numOpenTransactions in the queue");
         }
    }
    
    /**
     * perform rollBack on all transactionables with open transactions
     * 
     * @return void
     */
    public function rollBack()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . "  rollBack request, rollBack all transactionables");

        if ($this->_unitTestForceSkipRollBack) {
            return;
        }
        // if we are inside a precommit callback we need to break this as we are doing a rollback now
        // the rollbacks post callback may start new transactions, so we need to reset the flag
        static::$_insideCallBack = false;

        try {
            foreach ($this->_openTransactionables as $transactionable) {
                if ($transactionable instanceof Zend_Db_Adapter_Abstract) {
                    $transactionable->rollBack();
                }
            }

            // avoid loop backs. The callback may trigger a new transaction + commit/rollback...
            $callbacks = $this->_onRollbackCallbacks;
            $this->_onCommitCallbacks = array();
            $this->_afterCommitCallbacks = array();
            $this->_onRollbackCallbacks = array();
            $this->_openTransactionables = array();
            $this->_openTransactions = array();

            foreach ($callbacks as $callable) {
                call_user_func_array($callable[0], $callable[1]);
            }

            // don't mess with this finally, the ->rollBack() may throw. The callback may start / commit => reset the
            // flag. The finally is well thought of. Be aware that the callback has the flag not yet set... but why
            // would the callback need it, it is the "onRollBack" callback anyway right?
        } finally {
            static::$_rollBackOccurred = true;
        }
    }

    /**
     * register a callable to call just before the real commit happens
     *
     * @param array $callable
     * @param array $param
     */
    public function registerOnCommitCallback(array $callable, array $param = array())
    {
        $this->_onCommitCallbacks[] = array($callable, $param);
    }

    /**
     * register a callable to call just after the real commit happens
     *
     * @param array|callable $callable
     * @param array $param
     */
    public function registerAfterCommitCallback($callable, array $param = array())
    {
        $this->_afterCommitCallbacks[] = array($callable, $param);
    }

    /**
     * register a callable to call just before the rollback happens
     *
     * @param array $callable
     * @param array $param
     */
    public function registerOnRollbackCallback(array $callable, array $param = array())
    {
        $this->_onRollbackCallbacks[] = array($callable, $param);
    }

    /**
     * returns true if there are transactions started
     *
     * @return bool
     */
    public function hasOpenTransactions()
    {
        return count($this->_openTransactions) > 0;
    }

    /**
     * @param $bool
     */
    public function unitTestForceSkipRollBack($bool)
    {
        $this->_unitTestForceSkipRollBack = $bool;
    }

    public function unitTestRemoveTransactionable($_transactionable)
    {
        if (false !== ($pos = array_search($_transactionable, $this->_openTransactionables, true))) {
            unset($this->_openTransactionables[$pos]);
        }
    }
}
