<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  TransactionManager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @var Tinebase_TransactionManager
     */
    private static $_instance = NULL;
    
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
         
         $numOpenTransactions = count($this->_openTransactions);
         if ($numOpenTransactions === 0) {
             if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . "  no more open transactions in queue commiting all transactionables");
             foreach ($this->_openTransactionables as $transactionableIdx => $transactionable) {
                 if ($transactionable instanceof Zend_Db_Adapter_Abstract) {
                     $transactionable->commit();
                 }
             }
             $this->_openTransactionables = array();
             $this->_openTransactions = array();
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
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . "  rollBack request, rollBack all transactionables");
        foreach ($this->_openTransactionables as $transactionable) {
            if ($transactionable instanceof Zend_Db_Adapter_Abstract) {
                $transactionable->rollBack();
            }
        }
        $this->_openTransactionables = array();
        $this->_openTransactions = array();
    }
}
