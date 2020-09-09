<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Account controller mock for Felamimail
 *
 * this will force commit accounts to the imap / smtp db connection
 * it will also remember which (email user) accounts have been created and delete them again on request
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_AccountMock extends Felamimail_Controller_Account
{
    use Tinebase_Controller_SingletonTrait;

    protected $createdAccounts = [];

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        parent::__construct();
    }

    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        parent::_inspectAfterCreate($_createdRecord, $_record);

        if ($_createdRecord->type === Felamimail_Model_Account::TYPE_SYSTEM) {
            $imapDb = Tinebase_EmailUser::getInstance(Tinebase_Config::IMAP)->getDb();
            $smtpDb = Tinebase_EmailUser::getInstance(Tinebase_Config::SMTP)->getDb();
            if (Tinebase_Core::getDb() !== $imapDb) {
                try {
                    $imapDb->commit();
                    Tinebase_TransactionManager::getInstance()->unitTestRemoveTransactionable($imapDb);
                } catch (PDOException $e) {}
            }
            if (Tinebase_Core::getDb() !== $smtpDb) {
                try {
                    $smtpDb->commit();
                    Tinebase_TransactionManager::getInstance()->unitTestRemoveTransactionable($smtpDb);
                } catch (PDOException $e) {}
            }
            $this->createdAccounts[] = $_createdRecord;
        }
    }

    public function cleanUp()
    {
        if (empty($this->createdAccounts)) {
            return;
        }

        /** @var Felamimail_Model_Account $account */
        foreach ($this->createdAccounts as $account) {
            Tinebase_EmailUser_XpropsFacade::deleteEmailUsers($account);
        }
    }
}
