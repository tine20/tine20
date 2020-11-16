<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Test class for Felamimail_Model_MessageTest
 */
class Felamimail_Model_MessagePipeTest extends Felamimail_TestCase
{
    /**
     * test message pipe spam/ham with copy mail
     * - copy mail to spam/ham folder of shared account
     *
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Exception
     */
    public function testMessagePipeCopyToAnotherAccount()
    {
        $pipe = 'spam'; // both 'spam/ham pipe implement the same strategy
        $targetFolder = 'spam'; // 'spam': folder called spam , '#spam': user configured spam folder
        $account = $this->_createSharedAccount(); // create shared account
        
        $config = [
            'spam' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'accountid' => $account['id'],
                        'folder' => 'spam'
                    ]
                ]
            ],
            'ham' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'accountid' => $account['id'],
                        'folder' => 'ham'
                    ]
                ]
            ]
        ];
        
        // send message and copy to other folder of shared aacount
        $message = $this->_messagePipeTestHelper($config[$pipe], $targetFolder, $account);
        
        $this->_assertMessageInFolder('INBOX', $message['subject']);
        $this->_assertMessageInFolder($targetFolder, $message['subject'], $account);
    }

    /**
     * test message pipe spam/ham with copy mail to local directory
     *
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Exception
     */
    public function testMessagePipeCopyToLocalDir()
    {
        $this->_testNeedsTransaction();
        $tmp = Tinebase_Core::getTempDir();

        $config = [
            'spam' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'local_directory' => $tmp . '/spam'
                    ]
                ]
            ],
            'ham' => [
                'strategy' => 'copy',
                'config' => [
                    'target' => [
                        'local_directory' => $tmp . '/ham'
                    ]
                ]
            ]
        ];

        // send message and copy to spam dir
        $message = $this->_messagePipeTestHelper($config['spam'], 'spam');
        $this->_assertMessageInFolder('INBOX', $message['subject']);
        // assert eml in $tmp . '/spam'
        self::assertTrue(is_dir($tmp . '/spam'), 'no spam dir found');
        $filename = $tmp . '/spam/' . $message->headers['message-id'] . '.eml';
        self::assertTrue(file_exists($filename), 'eml file not found: ' . $filename);

        // send message and copy to ham dir
        $message = $this->_messagePipeTestHelper($config['ham'], 'ham');
        $this->_assertMessageInFolder('INBOX', $message['subject']);
        // assert eml in $tmp . '/ham'
        self::assertTrue(is_dir($tmp . '/ham'), 'no ham dir found');
        $filename = $tmp . '/ham/' . $message->headers['message-id'] . '.eml';
        self::assertTrue(file_exists($filename), 'eml file not found: ' . $filename);
    }

    /**
     * test message pipe spam with move mail
     * - move mail configured trash folder of current user
     * - delete original message
     *
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Exception
     */
    public function testMessagePipeMove()
    {
        $pipe = 'spam';
        $targetFolder = 'trash';
        $config = [
            'spam' => [
                'strategy' => 'move',
                'config' => [
                    'target' => [
                        'folder' => '#trash' ,
                    ]
                ]
            ]
        ];

        $inbox = $this->_getFolder('INBOX');
        $inboxBefore = $this->_json->updateMessageCache($inbox['id'], 30);

        $message = $this->_messagePipeTestHelper($config[$pipe], $targetFolder);
        $inboxAfter = $this->_getFolder('INBOX');

        $this->assertEquals($inboxBefore['cache_unreadcount'], $inboxAfter['cache_unreadcount']);
        $this->assertEquals($inboxBefore['cache_totalcount'], $inboxAfter['cache_totalcount']);

        $this->_assertMessageInFolder($targetFolder, $message['subject']);
    }

    /**
     * test message pipe ham with rewrite subject
     *
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Exception
     */
    public function testMessagePipeRewriteSubject()
    {
        $pipe = 'ham';
        $config = [
            'ham' => [
                'strategy' => 'rewrite_subject',
                'config' => [
                    'pattern' => '/^SPAM\? \(.+\) \*\*\* /',
                    'replacement' => '',
                ]
            ]
        ];

        $this->_messagePipeTestHelper($config[$pipe], 'INBOX');
        $this->_assertMessageInFolder('INBOX', 'test messagePipe');
    }

    /**
     * message pipe helper function
     * - set spam strategy config
     * - appends message from created messages
     * - adds appended message to cache for move/copy strategy
     * - execute pipeLine
     *
     * @param array $_config
     * @param string $_folderName
     * @param Felamimail_Model_Account $_account
     *
     * @return Felamimail_Model_Message|NULL
     *
     * @throws Felamimail_Exception_IMAPServiceUnavailable
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_Record_DefinitionFailure
     * @throws Tinebase_Exception_Record_NotAllowed
     * @throws Tinebase_Exception_Record_Validation
     * @throws Zend_Mail_Transport_Exception
     */
    public function _messagePipeTestHelper($_config, $_folderName, Felamimail_Model_Account $_account = null)
    {
        // set spam strategy config
        $this->_setFeatureForTest(Felamimail_Config::getInstance(), Felamimail_Config::FEATURE_SPAM_SUSPICION_STRATEGY);
        Felamimail_Config::getInstance()->set(Felamimail_Config::SPAM_SUSPICION_STRATEGY, 'subject');
        $config = [
            'pattern' => '/^SPAM\? \(.+\) \*\*\* /',
        ];
        Felamimail_Config::getInstance()->set(Felamimail_Config::SPAM_SUSPICION_STRATEGY_CONFIG, $config);

        $this->_foldersToClear[] = ['INBOX', 'Sent', 'Trash'];
        $this->_getFolder($_folderName, true, $_account);
        
        $subject = 'SPAM? (15) *** test messagePipe';
        
        $message = $this->_sendMessage(
            'INBOX',
            [],
            '',
            $subject);

        $message = Felamimail_Controller_Message::getInstance()->getCompleteMessage($message['id']);
        
        // create and execute pipeLine
        $pipeLineRecord = Felamimail_Model_MessagePipeConfig::factory($_config);
        $rs = new Tinebase_Record_RecordSet(Felamimail_Model_MessagePipeConfig::class);
        $rs->addRecord(new Felamimail_Model_MessagePipeConfig([
            Felamimail_Model_MessagePipeConfig::FLDS_CLASSNAME => get_class($pipeLineRecord),
            Felamimail_Model_MessagePipeConfig::FLDS_CONFIG_RECORD => $pipeLineRecord]));

        $pipeLine = new Tinebase_BL_Pipe($rs);
        $pipeLine->execute($message);

        return $message;
    }
}
