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
interface Syncroton_TransactionManagerInterface
{
    /**
     * @return Tinebase_TransactionManager
     */
    public static function getInstance();
    
    /**
     * starts a transaction
     *
     * @param   mixed $_transactionable
     * @return  string transactionId
     * @throws  Tinebase_Exception_UnexpectedValue
     */
    public function startTransaction($_transactionable);
    
    /**
     * commits a transaction
     *
     * @param  string $_transactionId
     * @return void
     */
    public function commitTransaction($_transactionId);
    
    /**
     * perform rollBack on all transactionables with open transactions
     * 
     * @return void
     */
    public function rollBack();
}
