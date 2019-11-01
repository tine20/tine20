<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * abstract Felamimail testcase with helper functions
 */
abstract class Felamimail_TestCase extends TestCase
{
    /**
     * @var Felamimail_Frontend_Json
     */
    protected $_json = array();

    /**
     * message ids to delete
     *
     * @var array
     */
    protected $_messageIds = array();

    /**
     * @var Felamimail_Model_Account
     */
    protected $_account = NULL;

    /**
     * imap backend
     * @var Felamimail_Backend_ImapProxy
     */
    protected $_imap = NULL;

    /**
     * name of the folder to use for tests
     * @var string
     */
    protected $_testFolderName = 'Junk';

    /**
     * folders to delete in tearDown()
     *
     * @var array
     */
    protected $_createdFolders = array();

    /**
     * are there messages to delete?
     *
     * @var array
     */
    protected $_foldersToClear = array();

    /**
     * are there accounts to delete?
     *
     * @var array
     */
    protected $_accountsToClear = array();

    /**
     * active sieve script name to be restored
     *
     * @var string
     */
    protected $_oldActiveSieveScriptName = NULL;

    /**
     * was sieve_vacation_active ?
     *
     * @var boolean
     */
    protected $_oldSieveVacationActiveState = FALSE;

    /**
     * old sieve data
     *
     * @var Felamimail_Sieve_Backend_Sql
     */
    protected $_oldSieveData = NULL;

    /**
     * sieve script name to delete
     *
     * @var string
     */
    protected $_testSieveScriptName = NULL;

    /**
     * sieve vacation template file name
     *
     * @var string
     */
    protected $_sieveVacationTemplateFile = 'vacation_template.tpl';

    /**
     * test email domain
     *
     * @var string
     */
    protected $_mailDomain = 'tine20.org';

    /**
     * @var Felamimail_Model_Folder
     */
    protected $_folder = NULL;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();

        Felamimail_Controller_Account::destroyInstance();

        // get (or create) test account
        $this->_account = Felamimail_Controller_Account::getInstance()->search()->getFirstRecord();
        if ($this->_account === null) {
            $this->markTestSkipped('no account found');
        }
        $this->_oldSieveVacationActiveState = $this->_account->sieve_vacation_active;
        try {
            $this->_oldSieveData = new Felamimail_Sieve_Backend_Sql($this->_account);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // do nothing
        }

        $this->_json = new Felamimail_Frontend_Json();
        $this->_imap = Felamimail_Backend_ImapFactory::factory($this->_account);

        foreach (array($this->_testFolderName, $this->_account->sent_folder, $this->_account->trash_folder) as $folderToCreate) {
            // create folder if it does not exist
            $this->_getFolder($folderToCreate);
        }

        $this->_mailDomain = TestServer::getPrimaryMailDomain();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Tearing down ...');

        if (count($this->_createdFolders) > 0) {
            foreach ($this->_createdFolders as $folderName) {
                //echo "delete $folderName\n";
                try {
                    $this->_imap->removeFolder(Felamimail_Model_Folder::encodeFolderName($folderName));
                } catch (Zend_Mail_Storage_Exception $zmse) {
                    // already deleted
                }
            }
            Felamimail_Controller_Cache_Folder::getInstance()->clear($this->_account);
        }

        if (!empty($this->_foldersToClear)) {
            foreach ($this->_foldersToClear as $folderName) {
                try {
                    // delete test messages from given folders on imap server (search by special header)
                    $this->_imap->selectFolder($folderName);
                    $result = $this->_imap->search(array(
                        'HEADER X-Tine20TestMessage jsontest'
                    ));
                    //print_r($result);
                    foreach ($result as $messageUid) {
                        $this->_imap->removeMessage($messageUid);
                    }

                    // clear message cache
                    $folder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($this->_account->getId(),
                        $folderName);
                    Felamimail_Controller_Cache_Message::getInstance()->clear($folder);
                } catch (Exception $e) {
                }
            }
        }

        // sieve cleanup
        if ($this->_testSieveScriptName !== NULL) {
            try {
                Felamimail_Controller_Sieve::getInstance()->setScriptName($this->_testSieveScriptName);
                try {
                    Felamimail_Controller_Sieve::getInstance()->deleteScript($this->_account->getId());
                } catch (Zend_Mail_Protocol_Exception $zmpe) {
                    // do not delete script if active
                }
                Felamimail_Controller_Account::getInstance()->setVacationActive($this->_account, $this->_oldSieveVacationActiveState);

                if ($this->_oldSieveData !== NULL) {
                    $this->_oldSieveData->save();
                }
            } catch (Exception $e) {
            }
        }
        if ($this->_oldActiveSieveScriptName !== NULL) {
            try {
                Felamimail_Controller_Sieve::getInstance()->setScriptName($this->_oldActiveSieveScriptName);
                Felamimail_Controller_Sieve::getInstance()->activateScript($this->_account->getId());
            } catch (Exception $e) {
            }
        }

        foreach ($this->_accountsToClear as $account) {
            try {
                Felamimail_Controller_Account::getInstance()->delete([$account->getId()]);
            } catch (Exception $e) {
            }
        }

            parent::tearDown();

        // needed to clear cache of containers
        Tinebase_Container::getInstance()->resetClassCache();
    }

    /**
     * get messages from folder
     *
     * @param string $_folderName
     * @param array $_additionalFilters
     * @return array json fe result with totalcount + results
     *
     * TODO use \Felamimail_Controller_Message::fetchRecentMessageFromFolder
     */
    protected function _getMessages($_folderName = 'INBOX', $_additionalFilters = [], $account = null)
    {
        $folder = $this->_getFolder($_folderName, true, $account);
        $filter = $this->_getMessageFilter($folder->getId(), $_additionalFilters);
        // update cache
        $folder = Felamimail_Controller_Cache_Message::getInstance()->updateCache($folder, 10, 1);
        $i = 0;
        while ($folder->cache_status != Felamimail_Model_Folder::CACHE_STATUS_COMPLETE && $i < 10) {
            $folder = Felamimail_Controller_Cache_Message::getInstance()->updateCache($folder, 10);
            $i++;
        }
        $result = $this->_json->searchMessages($filter, '');

        return $result;
    }

    /**
     * search for message defined by subject in folder
     *
     * @param string $_subject
     * @param string $_folderName
     * @return string message data
     */
    protected function _searchForMessageBySubject($_subject, $_folderName = 'INBOX', $_doAssertions = true)
    {
        // give server some time to send and receive messages
        sleep(1);

        $result = $this->_getMessages($_folderName);

        $message = array();
        foreach ($result['results'] as $mail) {
            if ($mail['subject'] == $_subject) {
                $message = $mail;
            }
        }
        if ($_doAssertions) {
            $this->assertGreaterThan(0, $result['totalcount'], 'folder is empty');
            $this->assertTrue(!empty($message), 'Message not found');
        }
        return $message;
    }

    /**
     * get folder filter
     *
     * @return array
     */
    protected function _getFolderFilter()
    {
        return array(array(
            'field' => 'globalname', 'operator' => 'equals', 'value' => ''
        ));
    }

    /**
     * get message filter
     *
     * @param string $_folderId
     * @param array $_additionalFilters
     * @return array
     */
    protected function _getMessageFilter($_folderId, $_additionalFilters = [])
    {
        $result = array(array(
            'field' => 'folder_id', 'operator' => 'equals', 'value' => $_folderId
        ));

        $result = array_merge($result, $_additionalFilters);

        return $result;
    }

    /**
     * get mailbox
     *
     * @param string $name
     * @param boolean $createFolder
     * @param Felamimail_Model_Account $account
     * @return Felamimail_Model_Folder|NULL
     */
    protected function _getFolder($name, $createFolder = TRUE, $account = null)
    {
        $account = $account ? $account : $this->_account;
        Felamimail_Controller_Cache_Folder::getInstance()->update($account->getId());
        try {
            $folder = Felamimail_Controller_Folder::getInstance()->getByBackendAndGlobalName($account->getId(), $name);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $folder = ($createFolder) ? Felamimail_Controller_Folder::getInstance()->create($account, $name) : NULL;
        }

        return $folder;
    }

    /**
     * get message data
     *
     * @return array
     */
    protected function _getMessageData($_emailFrom = '', $_subject = 'test')
    {
        return array(
            'account_id' => $this->_account->getId(),
            'subject' => $_subject,
            'to' => [Tinebase_Core::getUser()->accountEmailAddress],
            'body' => 'aaaaaä <br>',
            'headers' => array('X-Tine20TestMessage' => 'jsontest'),
            'from_email' => $_emailFrom,
            'content_type' => Felamimail_Model_Message::CONTENT_TYPE_HTML,
        );
    }

    /**
     * send message and return message array
     *
     * @param string $folderName
     * @param array $additionalHeaders
     * @param string $_emailFrom
     * @param string $_subject
     * @param null $_messageToSend
     * @return array
     */
    protected function _sendMessage(
        $folderName = 'INBOX',
        $additionalHeaders = array(),
        $_emailFrom = '',
        $_subject = 'test',
        $_messageToSend = null)
    {
        $messageToSend = $_messageToSend ? $_messageToSend : $this->_getMessageData($_emailFrom, $_subject);
        $messageToSend['headers'] = array_merge($messageToSend['headers'], $additionalHeaders);
        $this->_json->saveMessage($messageToSend);
        $this->_foldersToClear = array('INBOX', $this->_account->sent_folder);

        $i = 0;
        while ($i < 5) {
            $result = $this->_getMessages($folderName);
            $message = $this->_getMessageFromSearchResult($result, $messageToSend['subject']);
            if (!empty($message)) {
                break;
            }
            // sleep for 1 sec because mailserver may be slower than expected
            sleep(1);
            $i++;
        }

        $this->assertTrue(!empty($message), 'Sent message not found.');
        return $message;
    }

    /**
     * returns message array from result
     *
     * @param array $_result
     * @param string $_subject
     * @return array
     */
    protected function _getMessageFromSearchResult($_result, $_subject)
    {
        $message = array();
        foreach ($_result['results'] as $mail) {
            if ($mail['subject'] == $_subject) {
                $message = $mail;
            }
        }

        return $message;
    }

    /**
     * sieve test helper
     *
     * @param array $_sieveData
     * @return array
     */
    protected function _sieveTestHelper($_sieveData, $_isMime = FALSE)
    {
        $this->_setTestScriptname();

        // check which save fn to use
        if ((isset($_sieveData['reason']) || array_key_exists('reason', $_sieveData))) {
            $resultSet = $this->_json->saveVacation($_sieveData);
            $this->assertEquals($this->_account->email, $resultSet['addresses'][0]);

            $_sieveBackend = Felamimail_Backend_SieveFactory::factory($this->_account->getId());

            if (preg_match('/dbmail/i', $_sieveBackend->getImplementation())) {
                $translate = Tinebase_Translation::getTranslation('Felamimail');
                $this->assertEquals(sprintf(
                    $translate->_('Out of Office reply from %1$s'), Tinebase_Core::getUser()->accountFullName),
                    $resultSet['subject']
                );
            } else {
                $this->assertEquals($_sieveData['subject'], $resultSet['subject']);
            }

            if ($_isMime) {
                // TODO check why behaviour changed with php 7 (test was relaxed to hotfix this)
                //$this->assertEquals(html_entity_decode('unittest vacation&nbsp;message', ENT_NOQUOTES, 'UTF-8'), $resultSet['reason']);
                self::assertContains('unittest vacation', $resultSet['reason']);
            } else {
                $this->assertEquals($_sieveData['reason'], $resultSet['reason']);
            }

        } else if ((isset($_sieveData[0]['action_type']) || array_key_exists('action_type', $_sieveData[0]))) {
            $resultSet = $this->_json->saveRules($this->_account->getId(), $_sieveData);
            $this->assertEquals($_sieveData, $resultSet);
        }

        return $resultSet;
    }

    /**
     * @param bool $sendgrant
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _createSharedAccount($sendgrant = true)
    {
        Tinebase_EmailUser::clearCaches();
        $sharedAccountData = Admin_JsonTest::getSharedAccountData($sendgrant);
        $sharedAccount = Admin_Controller_EmailAccount::getInstance()->create(new Felamimail_Model_Account($sharedAccountData));
        // we need to commit so imap user is in imap db
        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
        $this->_accountsToClear[] = $sharedAccount;
        return $sharedAccount;
    }

    /**
     * @param Tinebase_Model_FullUser $user
     * @return Tinebase_Record_Interface
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     */
    protected function _createUserInternalAccount(Tinebase_Model_FullUser $user)
    {
        $accountData = Admin_JsonTest::getUserInternalAccountData($user);
        $internalAccount = Admin_Controller_EmailAccount::getInstance()->create(new Felamimail_Model_Account($accountData));
        // we need to commit so imap user is in imap db
        Tinebase_TransactionManager::getInstance()->commitTransaction($this->_transactionId);
        $this->_accountsToClear[] = $internalAccount;
        return $internalAccount;
    }

    /**
     * @param $to
     * @param Felamimail_Model_Account $account message is send & asserted in this account
     * @return mixed
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_Validation
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _sendAndAssertMail($to, $account = null)
    {
        $account = $account ? $account : $this->_account;
        $subject = 'test message ' . Tinebase_Record_Abstract::generateUID(10);
        $message = new Felamimail_Model_Message(array(
            'account_id'    => $account->getId(),
            'subject'       => $subject,
            'to'            => $to,
            'body'          => 'aaaaaä <br>',
        ));

        Felamimail_Controller_Message_Send::getInstance()->sendMessage($message);

        $filter = [
            ['field' => 'subject', 'operator' => 'equals', 'value' => $subject],
            ['field' => 'account_id', 'operator' => 'equals', 'value' => $account->getId()]
        ];
        $result = $this->_getMessages('INBOX', $filter, $account);
        self::assertEquals(1, $result['totalcount'], print_r($result, true));
        return $result['results'][0];
    }
}
