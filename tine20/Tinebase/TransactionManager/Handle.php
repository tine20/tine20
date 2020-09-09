<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  TransactionManager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Transaction Manger Handle
 *
 * On creation this objects creates a transaction. It can be committed anytime.
 * On destruction commit status will be checked and a rollback initiated if not yet committed
 *
 * @package     Tinebase
 * @subpackage  TransactionManager
 */
class Tinebase_TransactionManager_Handle
{
    protected $_isCommitted = false;
    protected $_transactionId = null;

    public function __construct()
    {
        $this->_transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
    }

    public function commit()
    {
        if ($this->_isCommitted) {
            throw new Tinebase_Exception(self::class . ' must not get committed multiple times');
        }

        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
        $this->_isCommitted = true;
    }

    public function __destruct()
    {
        if (!$this->_isCommitted) {
            // transaction manager will log info, we don't need to log here
            Tinebase_TransactionManager::getInstance()->rollBack();
        }
    }
}