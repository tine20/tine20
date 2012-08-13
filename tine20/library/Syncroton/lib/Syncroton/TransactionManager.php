<?php
/**
 * Syncroton
 * 
 * @package     Syncroton
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Transaction Manger for Syncroton
 * 
 * This is the central class, all transactions within Syncroton must be handled with.
 * For each supported transactionable (backend) this class start a real transaction on 
 * the first startTransaction request.
 * 
 * Transactions of all transactionable will be commited at once when all requested transactions
 * are being commited using this class.
 * 
 * Transactions of all transactionable will be roll back when one rollBack is requested
 * using this class.
 * 
 * @package     Syncroton
 */
class Syncroton_TransactionManager implements Syncroton_TransactionManagerInterface
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
     * @var Syncroton_TransactionManager
     */
    private static $_instance = NULL;
    
    /**
     * @var Zend_Log
     */
    protected $_logger;
    
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
        if (Syncroton_Registry::isRegistered('loggerBackend')) {
            $this->_logger = Syncroton_Registry::get('loggerBackend');
        }
    }
    
    /**
     * @return Tinebase_TransactionManager
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Syncroton_TransactionManager;
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
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . "  startTransaction request");
        
        if (! in_array($_transactionable, $this->_openTransactionables)) {
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . "  new transactionable. Starting transaction on this resource");
            
            if ($_transactionable instanceof Zend_Db_Adapter_Abstract) {
                #Tinebase_Backend_Sql_Command::setAutocommit($_transactionable,false);
                $_transactionable->beginTransaction();
            } else {
                $this->rollBack();
                throw new Syncroton_Exception_UnexpectedValue('Unsupported transactionable!');
            }
            
            array_push($this->_openTransactionables, $_transactionable);
        }
        
        $transactionId = sha1(mt_rand(). microtime());
        array_push($this->_openTransactions, $transactionId);
        
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . "  queued transaction with id $transactionId");
        
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
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . "  commitTransaction request for $_transactionId");
        
        $transactionIdx = array_search($_transactionId, $this->_openTransactions);
        if ($transactionIdx !== false) {
            unset($this->_openTransactions[$transactionIdx]);
        }
        
        $numOpenTransactions = count($this->_openTransactions);
        
        if ($numOpenTransactions === 0) {
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . "  no more open transactions in queue commiting all transactionables");
            foreach ($this->_openTransactionables as $transactionableIdx => $transactionable) {
                if ($transactionable instanceof Zend_Db_Adapter_Abstract) {
                    $transactionable->commit();
                    #Tinebase_Backend_Sql_Command::setAutocommit($transactionable,true);
                }
            }
            $this->_openTransactionables = array();
            $this->_openTransactions = array();
        } else {
            if ($this->_logger instanceof Zend_Log) 
                $this->_logger->debug(__METHOD__ . '::' . __LINE__ . "  commiting defered, as there are still $numOpenTransactions in the queue");
        }
    }
    
    /**
     * perform rollBack on all transactionables with open transactions
     * 
     * @return void
     */
    public function rollBack()
    {
        if ($this->_logger instanceof Zend_Log) 
            $this->_logger->debug(__METHOD__ . '::' . __LINE__ . "  rollBack request, rollBack all transactionables");
        
        foreach ($this->_openTransactionables as $transactionable) {
            if ($transactionable instanceof Zend_Db_Adapter_Abstract) {
                $transactionable->rollBack();
                #Tinebase_Backend_Sql_Command::setAutocommit($transactionable,true);
            }
        }
        
        $this->_openTransactionables = array();
        $this->_openTransactions = array();
    }
}
